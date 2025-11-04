<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use Illuminate\Console\Command;

class CheckDatabaseCommand extends Command
{
    protected $signature = 'db:check';
    protected $description = 'Быстрая проверка базы данных';

    public function handle()
    {
        $this->info("🔍 Проверка базы данных...");
        $this->newLine();

        $total = Recipe::count();
        $this->info("📊 Всего рецептов в базе: {$total}");
        
        if ($total > 0) {
            $first = Recipe::orderBy('id')->first();
            $last = Recipe::orderBy('id', 'desc')->first();
            
            $this->info("🔢 ID первого рецепта: {$first->id}");
            $this->info("🔢 ID последнего рецепта: {$last->id}");
            $this->newLine();
            
            $this->info("📝 Первый рецепт: {$first->title}");
            $this->info("📝 Последний рецепт: {$last->title}");
        }

        return 0;
    }
}
