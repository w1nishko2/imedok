<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Парсинг каждые 30 минут по 32 рецепта = ~1536 рецептов в день
        $schedule->command('recipes:parse --pages=1 --scrolls=4 --limit=32')
            ->everyThirtyMinutes()
            ->appendOutputTo(storage_path('logs/parser.log'));

        // Обновление sitemap каждый час
        $schedule->command('sitemap:generate')
            ->hourly()
            ->appendOutputTo(storage_path('logs/sitemap.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
