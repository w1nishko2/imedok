<?php

namespace App\Console\Commands;

use App\Services\RecipeListParserService;
use App\Services\RecipeParserService;
use Illuminate\Console\Command;

class ParseRecipesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipes:parse 
                            {--pages=1 : Количество страниц для парсинга} 
                            {--scrolls=3 : Количество скроллов (подгрузок) на каждой странице}
                            {--limit=10 : Максимальное количество рецептов}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Парсинг рецептов с сайта 1000.menu с поддержкой динамической подгрузки';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pages = (int) $this->option('pages');
        $scrolls = (int) $this->option('scrolls');
        $limit = (int) $this->option('limit');

        $this->info("🚀 Начинаем парсинг рецептов с динамической подгрузкой...");
        $this->info("📄 Страниц для парсинга: {$pages}");
        $this->info("🔄 Скроллов на каждой странице: {$scrolls}");
        $this->info("🎯 Максимум рецептов: {$limit}");
        $this->info("✅ Уже существующие рецепты будут автоматически пропущены");
        $this->newLine();

        // Шаг 1: Получаем список URL рецептов
        $listParser = new RecipeListParserService();
        $this->info("🔍 Получение списка рецептов...");
        
        // Автоматически пропускаем существующие рецепты
        $recipeUrls = $listParser->parseMultiplePages($pages, $scrolls);
        
        $this->info("✅ Найдено новых рецептов для обработки: " . count($recipeUrls));
        $this->newLine();

        if (empty($recipeUrls)) {
            $this->error("❌ Не найдено ни одного рецепта!");
            return 1;
        }

        // Ограничиваем количество рецептов
        $recipeUrls = array_slice($recipeUrls, 0, $limit);
        
        $this->info("📝 Будет обработано рецептов: " . count($recipeUrls));
        $this->newLine();

        // Шаг 2: Парсим каждый рецепт
        $recipeParser = new RecipeParserService();
        $progressBar = $this->output->createProgressBar(count($recipeUrls));
        $progressBar->start();

        $successful = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($recipeUrls as $url) {
            try {
                $recipe = $recipeParser->parseRecipe($url);
                
                if ($recipe) {
                    $successful++;
                } else {
                    $skipped++;
                }

            } catch (\Exception $e) {
                $errors++;
                $this->error("\nОшибка при парсинге {$url}: " . $e->getMessage());
            }

            $progressBar->advance();
            
            // Небольшая задержка между запросами
            sleep(2);
        }

        $progressBar->finish();
        $this->newLine(2);

        // Итоги
        $this->info("✨ Парсинг завершен!");
        $this->table(
            ['Статус', 'Количество'],
            [
                ['✅ Успешно добавлено', $successful],
                ['⏭️ Пропущено (уже существуют)', $skipped],
                ['❌ Ошибки', $errors],
                ['📊 Всего обработано', count($recipeUrls)],
            ]
        );

        return 0;
    }
}
