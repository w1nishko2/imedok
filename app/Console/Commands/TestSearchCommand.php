<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use Illuminate\Console\Command;

class TestSearchCommand extends Command
{
    protected $signature = 'test:search {query}';
    protected $description = 'Тестирование поиска рецептов';

    public function handle()
    {
        $query = $this->argument('query');
        $searchTerm = mb_strtolower(trim($query));
        
        $this->info("Поиск по запросу: {$query}");
        $this->newLine();
        
        // Тестируем поиск
        $recipes = Recipe::with('categories')
            ->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereRaw('LOWER(JSON_EXTRACT(ingredients, "$[*].name")) LIKE ?', ["%{$searchTerm}%"]);
            })
            ->orWhereHas('categories', function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
            })
            ->get();
        
        $this->info("Найдено рецептов: " . $recipes->count());
        $this->newLine();
        
        foreach ($recipes as $recipe) {
            $this->line("📋 " . $recipe->title);
            
            if ($recipe->categories->count() > 0) {
                $this->line("   Категории: " . $recipe->categories->pluck('name')->implode(', '));
            }
            
            if (is_array($recipe->ingredients) && count($recipe->ingredients) > 0) {
                $this->line("   Ингредиенты: " . implode(', ', array_slice(array_column($recipe->ingredients, 'name'), 0, 3)));
            }
            
            $this->newLine();
        }
    }
}
