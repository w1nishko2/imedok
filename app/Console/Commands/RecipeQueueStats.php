<?php

namespace App\Console\Commands;

use App\Models\RecipeQueue;
use Illuminate\Console\Command;

class RecipeQueueStats extends Command
{
    protected $signature = 'recipes:queue-stats';

    protected $description = 'Показать статистику очереди рецептов';

    public function handle(): int
    {
        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║   📊 Статистика очереди рецептов                     ║");
        $this->info("╚════════════════════════════════════════════════════════╝");
        $this->newLine();

        $pending = RecipeQueue::where('status', RecipeQueue::STATUS_PENDING)->count();
        $processing = RecipeQueue::where('status', RecipeQueue::STATUS_PROCESSING)->count();
        $completed = RecipeQueue::where('status', RecipeQueue::STATUS_COMPLETED)->count();
        $failed = RecipeQueue::where('status', RecipeQueue::STATUS_FAILED)->count();
        $total = RecipeQueue::count();

        $this->table(
            ['Статус', 'Количество', 'Процент'],
            [
                ['⏳ Ожидают', $pending, $this->percentage($pending, $total)],
                ['⚙️ Обрабатываются', $processing, $this->percentage($processing, $total)],
                ['✅ Выполнено', $completed, $this->percentage($completed, $total)],
                ['❌ Провалено', $failed, $this->percentage($failed, $total)],
                ['━━━━━━━━━━━━━━━', '━━━━━━━━━', '━━━━━━━━━'],
                ['📊 Всего', $total, '100%'],
            ]
        );

        // Последние добавленные
        $this->newLine();
        $this->info("🕐 Последние добавленные задачи:");
        $recent = RecipeQueue::orderBy('created_at', 'desc')->limit(5)->get();
        
        if ($recent->isEmpty()) {
            $this->warn("   Нет задач в очереди");
        } else {
            foreach ($recent as $task) {
                $status = $this->getStatusEmoji($task->status);
                $this->line("   {$status} {$task->url} ({$task->created_at->diffForHumans()})");
            }
        }

        // Проваленные с ошибками
        if ($failed > 0) {
            $this->newLine();
            $this->warn("⚠️ Последние ошибки:");
            $failedTasks = RecipeQueue::where('status', RecipeQueue::STATUS_FAILED)
                ->whereNotNull('error_message')
                ->orderBy('processed_at', 'desc')
                ->limit(3)
                ->get();
            
            foreach ($failedTasks as $task) {
                $this->error("   ❌ {$task->url}");
                $this->line("      Ошибка: {$task->error_message}");
            }
        }

        return self::SUCCESS;
    }

    protected function percentage(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }
        return round(($value / $total) * 100, 1) . '%';
    }

    protected function getStatusEmoji(string $status): string
    {
        return match($status) {
            RecipeQueue::STATUS_PENDING => '⏳',
            RecipeQueue::STATUS_PROCESSING => '⚙️',
            RecipeQueue::STATUS_COMPLETED => '✅',
            RecipeQueue::STATUS_FAILED => '❌',
            default => '❓',
        };
    }
}
