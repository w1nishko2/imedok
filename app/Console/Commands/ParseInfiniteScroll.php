<?php

namespace App\Console\Commands;

use App\Services\InfiniteScrollParserService;
use Illuminate\Console\Command;

class ParseInfiniteScroll extends Command
{
    protected $signature = 'recipes:parse-infinite 
                            {--max=0 : Максимальное количество рецептов (0 = бесконечно)}
                            {--batch=5 : Размер партии для записи в БД}
                            {--offset=0 : Начальный offset для пагинации}';

    protected $description = 'Бесконечный парсинг рецептов с автоматической пагинацией';

    protected InfiniteScrollParserService $parser;

    public function __construct(InfiniteScrollParserService $parser)
    {
        parent::__construct();
        $this->parser = $parser;
    }

    public function handle(): int
    {
        $maxRecipes = (int) $this->option('max');
        $batchSize = (int) $this->option('batch');
        $startOffset = (int) $this->option('offset');

        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║   🔄 Бесконечный парсер рецептов                      ║");
        $this->info("╚════════════════════════════════════════════════════════╝");
        $this->newLine();

        if ($maxRecipes === 0) {
            $this->warn("⚠️  РЕЖИМ: Бесконечный парсинг (пока не закончатся рецепты)");
        } else {
            $this->info("🎯 Цель: {$maxRecipes} новых рецептов");
        }

        $this->info("📦 Размер партии: {$batchSize} рецептов");
        $this->info("📍 Начальный offset: {$startOffset}");
        $this->newLine();

        // Настраиваем парсер
        $this->parser->setBatchSize($batchSize);

        // Запускаем
        $startTime = microtime(true);
        
        try {
            $stats = $this->parser->parseInfinitely($maxRecipes, $startOffset);
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->newLine();
            $this->info("╔════════════════════════════════════════════════════════╗");
            $this->info("║   ✅ Парсинг завершен                                 ║");
            $this->info("╚════════════════════════════════════════════════════════╝");
            $this->newLine();

            $this->table(
                ['Метрика', 'Значение'],
                [
                    ['⏱️  Время выполнения', "{$duration} сек"],
                    ['📄 Страниц обработано', $stats['pages_processed']],
                    ['🔍 URL проверено', $stats['urls_checked']],
                    ['🆕 Найдено новых', $stats['total_new']],
                    ['✅ Добавлено в БД', $stats['total_added']],
                    ['❌ Ошибок парсинга', $stats['total_failed']],
                    ['📊 Процент успеха', $stats['total_new'] > 0 ? round(($stats['total_added'] / $stats['total_new']) * 100, 2) . '%' : '0%'],
                ]
            );

            if ($stats['total_added'] > 0) {
                $this->info("🎉 Успешно! Добавлено {$stats['total_added']} рецептов");
            } else {
                $this->warn("⚠️  Не удалось добавить ни одного нового рецепта");
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Критическая ошибка: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
