<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ParserStatusCommand extends Command
{
    protected $signature = 'parser:status';
    protected $description = 'Показать статус парсера и статистику рецептов';

    public function handle()
    {
        $this->info("📊 Статус парсера и статистика рецептов");
        $this->newLine();

        // Общая статистика
        $total = Recipe::count();
        $today = Recipe::whereDate('created_at', today())->count();
        $yesterday = Recipe::whereDate('created_at', today()->subDay())->count();
        $thisWeek = Recipe::where('created_at', '>=', now()->startOfWeek())->count();

        $this->table(
            ['Показатель', 'Значение'],
            [
                ['Всего рецептов в БД', $total],
                ['Добавлено сегодня', $today],
                ['Добавлено вчера', $yesterday],
                ['Добавлено за неделю', $thisWeek],
            ]
        );

        $this->newLine();

        // Последние 5 рецептов
        $this->info("🔥 Последние 5 добавленных рецептов:");
        $latest = Recipe::orderBy('id', 'desc')->limit(5)->get();
        
        $latestData = [];
        foreach ($latest as $recipe) {
            $latestData[] = [
                $recipe->id,
                mb_substr($recipe->title, 0, 50),
                $recipe->created_at->format('d.m.Y H:i'),
                mb_substr($recipe->source_url, 0, 40) . '...'
            ];
        }

        $this->table(
            ['ID', 'Название', 'Добавлено', 'URL'],
            $latestData
        );

        $this->newLine();

        // Проверка расписания
        $this->info("⏰ Текущее расписание парсинга:");
        $this->line("  • Каждые 30 минут по 32 рецепта");
        $this->line("  • Примерно 1536 рецептов в сутки");
        $this->newLine();

        // Рекомендации
        if ($today === 0) {
            $this->warn("⚠️ Сегодня еще не добавлено ни одного рецепта!");
            $this->info("💡 Запустите: php artisan recipes:parse --count=32");
        } elseif ($today < 10) {
            $this->info("✅ Парсер работает, но можно добавить больше рецептов");
        } else {
            $this->info("🎉 Отлично! Парсер активно работает!");
        }

        return 0;
    }
}
