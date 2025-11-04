<?php

namespace App\Console\Commands;

use App\Services\AjaxScrollParserService;
use App\Services\RecipeParserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ParseInfiniteScrollRecipes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipes:parse-infinite-scroll 
                            {--count=50 : Количество новых рецептов для сбора}
                            {--url= : Дополнительный URL для парсинга}
                            {--parse-now : Сразу парсить найденные рецепты (долго!)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Парсинг рецептов с нескольких источников (эмуляция infinite scroll без браузера)';

    protected AjaxScrollParserService $scrollParser;
    protected RecipeParserService $recipeParser;

    public function __construct(
        AjaxScrollParserService $scrollParser,
        RecipeParserService $recipeParser
    ) {
        parent::__construct();
        $this->scrollParser = $scrollParser;
        $this->recipeParser = $recipeParser;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetCount = (int) $this->option('count');
        $customUrl = $this->option('url');
        $parseNow = $this->option('parse-now');

        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║   🚀 Парсинг рецептов с нескольких источников         ║");
        $this->info("╚════════════════════════════════════════════════════════╝");
        $this->newLine();

        // Настройка парсера
        if ($customUrl) {
            $this->scrollParser->addTargetUrl($customUrl);
            $this->info("🔗 Добавлен URL: {$customUrl}");
        }

        $this->info("🎯 Цель: {$targetCount} новых рецептов");
        $this->info("� Источников: по умолчанию 3 (cooking/all-new, cooking, catalog)");
        $this->newLine();

        // Запускаем парсинг
        $this->info("🌐 Запуск парсинга...");
        $this->newLine();

        try {
            $recipeUrls = $this->scrollParser->parseMultipleSources($targetCount);

            if (empty($recipeUrls)) {
                $this->error("❌ Не удалось найти новые рецепты");
                return self::FAILURE;
            }

            $this->newLine();
            $this->info("╔════════════════════════════════════════════════════════╗");
            $this->info("║   ✅ Сбор URL завершен успешно!                       ║");
            $this->info("╚════════════════════════════════════════════════════════╝");
            $this->newLine();
            $this->info("📊 Найдено новых рецептов: " . count($recipeUrls));
            $this->newLine();

            // Показываем первые 10 URL
            $this->info("📋 Примеры найденных URL:");
            foreach (array_slice($recipeUrls, 0, 10) as $index => $url) {
                $this->line("   " . ($index + 1) . ". {$url}");
            }
            if (count($recipeUrls) > 10) {
                $this->line("   ... и еще " . (count($recipeUrls) - 10) . " рецептов");
            }
            $this->newLine();

            // Парсинг найденных рецептов
            if ($parseNow) {
                $this->info("🔍 Начинаем парсинг найденных рецептов...");
                $this->info("⚠️  Это займет много времени!");
                $this->newLine();

                $parsed = 0;
                $failed = 0;
                $progressBar = $this->output->createProgressBar(count($recipeUrls));
                $progressBar->start();

                foreach ($recipeUrls as $url) {
                    try {
                        $recipe = $this->recipeParser->parseRecipe($url);
                        if ($recipe) {
                            $parsed++;
                        } else {
                            $failed++;
                        }
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error("Ошибка парсинга {$url}: " . $e->getMessage());
                    }
                    
                    $progressBar->advance();
                    sleep(2); // Пауза между запросами
                }

                $progressBar->finish();
                $this->newLine(2);

                $this->info("╔════════════════════════════════════════════════════════╗");
                $this->info("║   🎉 Парсинг завершен!                                ║");
                $this->info("╚════════════════════════════════════════════════════════╝");
                $this->newLine();
                $this->info("✅ Успешно спарсено: {$parsed}");
                $this->info("❌ Ошибок: {$failed}");
                
            } else {
                $this->newLine();
                $this->info("💡 Совет: Используйте флаг --parse-now для немедленного парсинга");
                $this->info("   или запустите существующую команду парсинга отдельно");
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Критическая ошибка: " . $e->getMessage());
            Log::error("Ошибка парсинга: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
