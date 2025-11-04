<?php

namespace App\Console\Commands;

use App\Services\RecipeParserService;
use Illuminate\Console\Command;

class TestRecipeParseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipes:test {url : URL рецепта для тестирования}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестовый парсинг одного рецепта с подробным выводом';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');

        $this->info("🔍 Тестовый парсинг рецепта:");
        $this->info("URL: {$url}");
        $this->newLine();

        try {
            $parser = new RecipeParserService();
            
            $this->info("⏳ Начинаем парсинг...");
            $recipe = $parser->parseRecipe($url);

            if ($recipe) {
                $this->newLine();
                $this->info("✅ Рецепт успешно спарсен!");
                $this->newLine();
                
                $this->table(
                    ['Поле', 'Значение'],
                    [
                        ['ID', $recipe->id],
                        ['Название', $recipe->title],
                        ['Slug', $recipe->slug],
                        ['Описание', mb_substr($recipe->description ?? 'нет', 0, 100)],
                        ['Изображение', $recipe->image_path ?? 'нет'],
                        ['Ингредиенты', count($recipe->ingredients ?? [])],
                        ['Шаги', count($recipe->steps ?? [])],
                        ['Время приготовления', $recipe->total_time ?? 'нет'],
                        ['Сложность', $recipe->difficulty ?? 'нет'],
                        ['Порций', $recipe->servings ?? 'нет'],
                    ]
                );

                // Показываем категории
                $categories = $recipe->categories;
                
                if ($categories->count() > 0) {
                    $this->newLine();
                    $this->info("📁 Категории ({$categories->count()}):");
                    
                    $categoryData = [];
                    foreach ($categories as $category) {
                        $categoryData[] = [
                            $category->id,
                            $category->name,
                            $category->slug,
                            $category->parent ? $category->parent->name : '-',
                            $category->recipe_count,
                        ];
                    }
                    
                    $this->table(
                        ['ID', 'Название', 'Slug', 'Родитель', 'Рецептов'],
                        $categoryData
                    );
                } else {
                    $this->newLine();
                    $this->warn("⚠️ Категории не найдены!");
                    $this->info("💡 Проверьте логи в storage/logs/laravel.log для подробностей");
                }

                $this->newLine();
                $this->info("📊 Проверьте подробные логи в: storage/logs/laravel.log");
                
                return 0;

            } else {
                $this->error("❌ Не удалось спарсить рецепт!");
                $this->warn("Возможные причины:");
                $this->warn("  • Рецепт уже существует в базе");
                $this->warn("  • Ошибка при получении HTML");
                $this->warn("  • Неверная структура страницы");
                $this->newLine();
                $this->info("📊 Проверьте логи в: storage/logs/laravel.log");
                
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Ошибка: " . $e->getMessage());
            $this->error("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
