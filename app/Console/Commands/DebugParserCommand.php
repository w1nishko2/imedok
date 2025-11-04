<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class DebugParserCommand extends Command
{
    protected $signature = 'parser:debug';
    protected $description = 'Отладка парсера - показывает что происходит';

    public function handle()
    {
        $this->info("🔍 Отладка парсера");
        $this->newLine();

        // Проверяем первую страницу сайта
        $this->info("1️⃣ Проверяем первую страницу сайта...");
        
        $client = new Client([
            'verify' => false,
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]
        ]);

        try {
            $response = $client->get('https://1000.menu/cooking/new');
            $html = $response->getBody()->getContents();
            
            // Ищем ссылки на рецепты
            preg_match_all('/<a[^>]*href=["\']([^"\']+)["\'][^>]*>/', $html, $matches);
            
            $recipeUrls = [];
            foreach ($matches[1] as $href) {
                if (preg_match('/\/cooking\/(\d+)/', $href, $idMatch)) {
                    $href = preg_replace('/[#?].*$/', '', $href);
                    $fullUrl = 'https://1000.menu' . $href;
                    if (!in_array($fullUrl, $recipeUrls)) {
                        $recipeUrls[] = $fullUrl;
                    }
                }
            }
            
            $this->info("✅ Найдено URL на первой странице: " . count($recipeUrls));
            $this->newLine();
            
            // Показываем первые 10 URL
            $this->info("📋 Первые 10 URL с сайта:");
            foreach (array_slice($recipeUrls, 0, 10) as $i => $url) {
                $this->line("  " . ($i + 1) . ". " . $url);
            }
            $this->newLine();
            
            // Проверяем какие из них уже в базе
            $existingUrls = Recipe::whereIn('source_url', $recipeUrls)
                ->pluck('source_url')
                ->toArray();
            
            $newUrls = array_diff($recipeUrls, $existingUrls);
            
            $this->info("📊 Статистика:");
            $this->table(
                ['Показатель', 'Значение'],
                [
                    ['URL с первой страницы', count($recipeUrls)],
                    ['Уже в базе', count($existingUrls)],
                    ['Новых (можно добавить)', count($newUrls)],
                ]
            );
            $this->newLine();
            
            if (count($newUrls) > 0) {
                $this->info("✅ Есть новые рецепты для добавления!");
                $this->info("🎯 Первые 5 новых URL:");
                foreach (array_slice(array_values($newUrls), 0, 5) as $i => $url) {
                    $this->line("  " . ($i + 1) . ". " . $url);
                }
                $this->newLine();
                $this->info("💡 Запустите: php artisan recipes:parse --count=5");
            } else {
                $this->warn("⚠️ Все рецепты с первой страницы уже в базе!");
                $this->info("💡 Это нормально если вы недавно запускали парсер.");
                $this->info("💡 Новые рецепты появляются на сайте постепенно.");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Ошибка: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
