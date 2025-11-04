<?php

namespace App\Services;

use App\Models\Recipe;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Парсер для страниц с infinite scroll через анализ AJAX запросов
 * Работает БЕЗ headless браузера - быстрее и легче!
 */
class AjaxScrollParserService
{
    protected Client $client;
    protected string $baseUrl = 'https://1000.menu';
    
    // Список URL для парсинга (разные разделы)
    protected array $targetUrls = [
        'https://1000.menu/cooking/all-new',
        'https://1000.menu/cooking',
        'https://1000.menu/catalog',
    ];

    public function __construct()
    {
        $this->client = new Client([
            'verify' => false,
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ]
        ]);
    }

    /**
     * Парсинг рецептов с нескольких URL (эмуляция infinite scroll)
     * 
     * @param int $targetCount Целевое количество НОВЫХ рецептов
     * @return array Массив URL новых рецептов
     */
    public function parseMultipleSources(int $targetCount = 50): array
    {
        Log::info("🎯 Запуск AJAX парсинга: цель {$targetCount} НОВЫХ рецептов");
        Log::info("🔗 Источников для парсинга: " . count($this->targetUrls));

        $allRecipeUrls = [];
        $sourceIndex = 0;
        $maxPages = 20; // Макс страниц на один источник
        $totalChecked = 0;

        foreach ($this->targetUrls as $sourceUrl) {
            $sourceIndex++;
            Log::info("📂 [{$sourceIndex}/" . count($this->targetUrls) . "] Парсинг источника: {$sourceUrl}");

            // Парсим несколько страниц из этого источника
            for ($page = 1; $page <= $maxPages; $page++) {
                try {
                    $pageUrl = $this->buildPageUrl($sourceUrl, $page);
                    $recipes = $this->fetchRecipesFromUrl($pageUrl);
                    
                    if (empty($recipes)) {
                        Log::info("   ⚠️ Страница {$page} пустая - переход к следующему источнику");
                        break;
                    }

                    $totalChecked += count($recipes);
                    Log::info("   ✅ Страница {$page}: найдено " . count($recipes) . " рецептов");

                    // Объединяем с общим списком
                    $allRecipeUrls = array_unique(array_merge($allRecipeUrls, $recipes));

                    // Проверяем, сколько новых рецептов
                    $newRecipes = $this->filterExistingRecipes($allRecipeUrls);
                    $newCount = count($newRecipes);

                    Log::info("   📊 Всего собрано: " . count($allRecipeUrls) . ", новых: {$newCount}/{$targetCount}");

                    // Если достигли цели - выходим
                    if ($newCount >= $targetCount) {
                        Log::info("   🎉 Цель достигнута!");
                        break 2; // Выход из обоих циклов
                    }

                    sleep(1); // Пауза между запросами

                } catch (\Exception $e) {
                    Log::warning("   ⚠️ Ошибка на странице {$page}: " . $e->getMessage());
                    break;
                }
            }
        }

        // Фильтруем только новые
        $newRecipes = $this->filterExistingRecipes($allRecipeUrls);

        Log::info("🏁 Парсинг завершен:");
        Log::info("   📊 Всего URL собрано: " . count($allRecipeUrls));
        Log::info("   ✨ Новых рецептов (не в БД): " . count($newRecipes));
        Log::info("   🔍 Проверено URL: {$totalChecked}");

        // Возвращаем только нужное количество
        return array_slice($newRecipes, 0, $targetCount);
    }

    /**
     * Построить URL страницы с учетом пагинации
     */
    protected function buildPageUrl(string $baseUrl, int $page): string
    {
        if ($page === 1) {
            return $baseUrl;
        }

        // Если в URL уже есть параметры
        if (strpos($baseUrl, '?') !== false) {
            return $baseUrl . '&page=' . $page;
        }

        return $baseUrl . '?page=' . $page;
    }

    /**
     * Получить рецепты из конкретного URL
     */
    protected function fetchRecipesFromUrl(string $url): array
    {
        try {
            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();
            
            $recipeUrls = [];

            // Ищем ссылки на рецепты через регулярные выражения
            preg_match_all('/<a[^>]*href=["\']([^"\']+)["\'][^>]*>/', $html, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $href) {
                    // Фильтруем только ссылки на рецепты (с цифрами)
                    if (preg_match('/\/cooking\/(\d+)/', $href, $idMatch)) {
                        // Очищаем URL от фрагмента и параметров
                        $href = preg_replace('/[#?].*$/', '', $href);
                        
                        // Формируем полный URL
                        if (strpos($href, 'http') !== 0) {
                            $fullUrl = $this->baseUrl . $href;
                        } else {
                            $fullUrl = $href;
                        }
                        
                        if (!in_array($fullUrl, $recipeUrls)) {
                            $recipeUrls[] = $fullUrl;
                        }
                    }
                }
            }

            return $recipeUrls;

        } catch (\Exception $e) {
            Log::warning("⚠️ Ошибка получения рецептов с {$url}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Фильтрует список URL, оставляя только те, которых нет в базе данных
     */
    protected function filterExistingRecipes(array $urls): array
    {
        if (empty($urls)) {
            return [];
        }

        // Получаем все существующие URL из базы данных
        $existingUrls = Recipe::whereIn('source_url', $urls)
            ->pluck('source_url')
            ->toArray();

        // Возвращаем только новые URL
        $newUrls = array_diff($urls, $existingUrls);

        return array_values($newUrls);
    }

    /**
     * Установить список URL для парсинга
     */
    public function setTargetUrls(array $urls): self
    {
        $this->targetUrls = $urls;
        return $this;
    }

    /**
     * Добавить URL к списку
     */
    public function addTargetUrl(string $url): self
    {
        if (!in_array($url, $this->targetUrls)) {
            $this->targetUrls[] = $url;
        }
        return $this;
    }
}
