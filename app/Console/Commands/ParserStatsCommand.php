<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ParserStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parser:stats {--detailed : Показать детальную статистику}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Статистика парсера рецептов';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("📊 Статистика парсера рецептов");
        $this->newLine();

        // Общая статистика
        $totalRecipes = Recipe::count();
        $todayRecipes = Recipe::whereDate('created_at', today())->count();
        $yesterdayRecipes = Recipe::whereDate('created_at', today()->subDay())->count();
        $thisWeekRecipes = Recipe::whereBetween('created_at', [now()->startOfWeek(), now()])->count();
        $thisMonthRecipes = Recipe::whereBetween('created_at', [now()->startOfMonth(), now()])->count();

        $this->table(
            ['Период', 'Количество рецептов'],
            [
                ['📚 Всего в базе', $totalRecipes],
                ['📅 Сегодня', $todayRecipes],
                ['📆 Вчера', $yesterdayRecipes],
                ['📖 За неделю', $thisWeekRecipes],
                ['📕 За месяц', $thisMonthRecipes],
            ]
        );

        $this->newLine();

        // Средняя скорость
        $firstRecipe = Recipe::oldest()->first();
        if ($firstRecipe) {
            $daysActive = max(1, now()->diffInDays($firstRecipe->created_at));
            $avgPerDay = round($totalRecipes / $daysActive, 1);
            
            $this->info("⚡ Средняя скорость парсинга: {$avgPerDay} рецептов/день");
            $this->info("📈 Дней в работе: {$daysActive}");
            $this->newLine();
        }

        // Прогноз до 1500/день
        if ($todayRecipes > 0) {
            $hoursLeft = 24 - now()->hour;
            $projection = round($todayRecipes / (24 - $hoursLeft) * 24);
            
            $this->info("🎯 Текущая скорость сегодня: {$todayRecipes} рецептов за " . (24 - $hoursLeft) . " часов");
            $this->info("📊 Прогноз на конец дня: ~{$projection} рецептов");
            
            if ($projection >= 1500) {
                $this->info("✅ Цель 1500+ рецептов/день будет достигнута!");
            } else {
                $need = 1500 - $projection;
                $this->warn("⚠️ Для достижения цели нужно еще ~{$need} рецептов");
            }
            $this->newLine();
        }

        // Детальная статистика
        if ($this->option('detailed')) {
            $this->info("📈 Детальная статистика по дням:");
            $this->newLine();

            $last7Days = Recipe::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            $tableData = [];
            foreach ($last7Days as $day) {
                $emoji = $day->count >= 1500 ? '✅' : ($day->count >= 1000 ? '⚠️' : '❌');
                $tableData[] = [
                    $emoji,
                    $day->date,
                    $day->count,
                    round(($day->count / 1500) * 100, 1) . '%'
                ];
            }

            $this->table(
                ['', 'Дата', 'Рецептов', 'От цели (1500)'],
                $tableData
            );

            $this->newLine();

            // Топ категорий
            $this->info("🏆 Топ-10 категорий:");
            $topCategories = DB::table('categories')
                ->join('recipe_category', 'categories.id', '=', 'recipe_category.category_id')
                ->select('categories.name', DB::raw('COUNT(*) as count'))
                ->groupBy('categories.id', 'categories.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            $categoryData = [];
            foreach ($topCategories as $index => $category) {
                $categoryData[] = [
                    $index + 1,
                    $category->name,
                    $category->count
                ];
            }

            $this->table(['#', 'Категория', 'Рецептов'], $categoryData);
        }

        return 0;
    }
}
