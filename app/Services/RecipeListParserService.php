<?php

namespace App\Services;

use App\Models\Recipe;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class RecipeListParserService
{
    protected Client $client;
    protected string $baseUrl = 'https://1000.menu';
    protected int $recipesPerScroll = 20; // Количество рецептов, загружаемых за один "скролл"

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
                'Upgrade-Insecure-Requests' => '1',
            ]
        ]);
    }

    /**
     * Парсинг списка рецептов со страницы с поддержкой множественных "скроллов"
     *
     * @param int $page Номер страницы
     * @param int $scrolls Количество "скроллов" (подгрузок) на одной странице
     * @return array Массив URL рецептов
     */
    public function parseRecipesList(int $page = 1, int $scrolls = 3): array
    {
        $allRecipeUrls = [];

        try {
            // Парсим основную страницу
            $mainUrl = $this->baseUrl . '/cooking/new';
            
            if ($page > 1) {
                $mainUrl .= '?page=' . $page;
            }

            Log::info("🔍 Парсинг основной страницы: {$mainUrl}");

            // Первая загрузка - получаем начальный контент
            $recipes = $this->fetchRecipesFromUrl($mainUrl);
            $allRecipeUrls = array_merge($allRecipeUrls, $recipes);
            
            Log::info("✅ Первая загрузка: найдено " . count($recipes) . " рецептов");

            // Эмулируем скроллы - пробуем разные варианты пагинации
            for ($scroll = 1; $scroll < $scrolls; $scroll++) {
                sleep(2); // Задержка между запросами, чтобы не нагружать сервер
                
                // Пробуем разные варианты URL для динамической подгрузки
                $scrollUrls = [
                    // Вариант 1: offset параметр
                    $mainUrl . (strpos($mainUrl, '?') !== false ? '&' : '?') . 'offset=' . ($scroll * $this->recipesPerScroll),
                    // Вариант 2: start параметр
                    $mainUrl . (strpos($mainUrl, '?') !== false ? '&' : '?') . 'start=' . ($scroll * $this->recipesPerScroll),
                    // Вариант 3: from параметр
                    $mainUrl . (strpos($mainUrl, '?') !== false ? '&' : '?') . 'from=' . ($scroll * $this->recipesPerScroll),
                    // Вариант 4: виртуальная страница
                    $this->baseUrl . '/cooking/new?page=' . (($page - 1) * $scrolls + $scroll + 1),
                ];

                foreach ($scrollUrls as $scrollUrl) {
                    Log::info("🔄 Скролл #{$scroll}, пробуем: {$scrollUrl}");
                    
                    $scrollRecipes = $this->fetchRecipesFromUrl($scrollUrl);
                    
                    if (!empty($scrollRecipes)) {
                        // Проверяем, что это новые рецепты
                        $newRecipes = array_diff($scrollRecipes, $allRecipeUrls);
                        
                        if (!empty($newRecipes)) {
                            $allRecipeUrls = array_merge($allRecipeUrls, $newRecipes);
                            Log::info("✅ Скролл #{$scroll}: добавлено " . count($newRecipes) . " новых рецептов");
                            break; // Нашли рабочий вариант, переходим к следующему скроллу
                        } else {
                            Log::info("⚠️ Скролл #{$scroll}: дубликаты, пробуем следующий вариант");
                        }
                    }
                }
            }

            $allRecipeUrls = array_unique($allRecipeUrls);
            Log::info("🎉 Итого найдено уникальных рецептов: " . count($allRecipeUrls));

            return $allRecipeUrls;

        } catch (\Exception $e) {
            Log::error("❌ Ошибка парсинга списка рецептов: " . $e->getMessage());
            return $allRecipeUrls;
        }
    }

    /**
     * Получить рецепты из конкретного URL
     *
     * @param string $url URL для парсинга
     * @return array Массив URL рецептов
     */
    protected function fetchRecipesFromUrl(string $url): array
    {
        try {
            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();
            
            $recipeUrls = [];

            // Используем регулярные выражения для поиска ссылок на рецепты
            // Ищем ссылки типа /cooking/ЧИСЛО через теги <a>
            preg_match_all('/<a[^>]*href=["\']([^"\']+)["\'][^>]*>/', $html, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $href) {
                    // Фильтруем только ссылки на рецепты (с цифрами)
                    if (preg_match('/\/cooking\/(\d+)/', $href, $idMatch)) {
                        // Очищаем URL от фрагмента и параметров
                        $href = preg_replace('/[#?].*$/', '', $href);
                        $fullUrl = $this->baseUrl . $href;
                        
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
     * Получить рецепты с нескольких страниц с поддержкой скроллов
     * Автоматически фильтрует уже существующие в базе рецепты
     *
     * @param int $pagesCount Количество страниц для парсинга
     * @param int $scrollsPerPage Количество "скроллов" на каждой странице
     * @return array Массив URL рецептов (только новые)
     */
    public function parseMultiplePages(int $pagesCount = 1, int $scrollsPerPage = 3): array
    {
        $allRecipes = [];

        for ($page = 1; $page <= $pagesCount; $page++) {
            Log::info("📄 Обработка страницы {$page} из {$pagesCount}");
            
            $recipes = $this->parseRecipesList($page, $scrollsPerPage);
            $allRecipes = array_merge($allRecipes, $recipes);
            
            Log::info("📊 Страница {$page}: всего собрано " . count($allRecipes) . " рецептов");
            
            // Задержка между страницами
            if ($page < $pagesCount) {
                sleep(2);
            }
        }

        $uniqueRecipes = array_unique($allRecipes);
        Log::info("🏁 Найдено уникальных URL: " . count($uniqueRecipes));

        // Автоматически фильтруем уже существующие рецепты
        $newRecipes = $this->filterExistingRecipes($uniqueRecipes);
        Log::info("✅ Новых рецептов (еще не в БД): " . count($newRecipes));
        Log::info("⏭️ Пропущено (уже в БД): " . (count($uniqueRecipes) - count($newRecipes)));

        return $newRecipes;
    }

    /**
     * Фильтрует список URL, оставляя только те, которых нет в базе данных
     *
     * @param array $urls Массив URL для проверки
     * @return array Массив URL, которых нет в базе
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

        return array_values($newUrls); // Переиндексируем массив
    }

    /**
     * Установить количество рецептов на один "скролл"
     *
     * @param int $count Количество рецептов
     * @return self
     */
    public function setRecipesPerScroll(int $count): self
    {
        $this->recipesPerScroll = $count;
        return $this;
    }
}
