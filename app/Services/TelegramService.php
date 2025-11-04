<?php

namespace App\Services;

use App\Models\Recipe;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\InputMedia\InputMediaPhoto;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramService
{
    protected BotApi $bot;
    protected string $channelId;

    public function __construct()
    {
        $this->bot = new BotApi(config('services.telegram.bot_token'));
        $this->channelId = config('services.telegram.channel_id');
    }

    /**
     * Публикация рецепта в Telegram канал
     */
    public function publishRecipe(Recipe $recipe): bool
    {
        try {
            $message = $this->formatRecipeMessage($recipe);
            $recipeUrl = route('recipe.show', $recipe->slug);

            // Создаем клавиатуру с кнопкой
            $keyboard = new InlineKeyboardMarkup([
                [
                    ['text' => '👨‍🍳 Смотреть рецепт', 'url' => $recipeUrl]
                ]
            ]);

            // Если есть изображение - отправляем с фото
            if ($recipe->image_path && file_exists(storage_path('app/public/' . $recipe->image_path))) {
                $photoPath = storage_path('app/public/' . $recipe->image_path);
                
                $this->bot->sendPhoto(
                    $this->channelId,
                    new \CURLFile($photoPath),
                    $message,
                    null,
                    $keyboard,
                    false,
                    'HTML'
                );
            } else {
                // Отправляем только текст, если нет фото
                $this->bot->sendMessage(
                    $this->channelId,
                    $message,
                    'HTML',
                    false,
                    null,
                    $keyboard
                );
            }

            Log::info('Recipe published to Telegram', [
                'recipe_id' => $recipe->id,
                'recipe_title' => $recipe->title
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to publish recipe to Telegram', [
                'recipe_id' => $recipe->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Форматирование сообщения для Telegram
     */
    protected function formatRecipeMessage(Recipe $recipe): string
    {
        $message = "";
        
        // Заголовок с эмодзи
        $emoji = $this->getCategoryEmoji($recipe->category);
        $message .= "{$emoji} <b>" . htmlspecialchars($recipe->title) . "</b>\n\n";

        // Описание (обрезаем если слишком длинное)
        if ($recipe->description) {
            $description = Str::limit(strip_tags($recipe->description), 300);
            $message .= "📝 " . htmlspecialchars($description) . "\n\n";
        }

        // Информация о времени и порциях
        $info = [];
        
        if ($recipe->prep_time) {
            $info[] = "⏱ Подготовка: {$recipe->prep_time} мин";
        }
        
        if ($recipe->cook_time) {
            $info[] = "🔥 Приготовление: {$recipe->cook_time} мин";
        }
        
        if ($recipe->total_time) {
            $info[] = "⏰ Всего: {$recipe->total_time} мин";
        }
        
        if ($recipe->servings) {
            $info[] = "🍽 Порций: {$recipe->servings}";
        }

        if (!empty($info)) {
            $message .= implode("\n", $info) . "\n\n";
        }

        // Категория
        if ($recipe->category) {
            $message .= "📂 Категория: " . htmlspecialchars($recipe->category->name) . "\n";
        }

        // Калорийность
        if ($recipe->calories) {
            $message .= "🔥 Калорийность: {$recipe->calories} ккал\n";
        }

        $message .= "\n";
        
        // Хештеги
        $message .= $this->generateHashtags($recipe);

        return $message;
    }

    /**
     * Получить эмодзи для категории
     */
    protected function getCategoryEmoji(?object $category): string
    {
        if (!$category || !$category->name) {
            return '🍴';
        }

        $emojiMap = [
            'супы' => '🍲',
            'салаты' => '🥗',
            'закуски' => '🥙',
            'горячие блюда' => '🍛',
            'мясные блюда' => '🥩',
            'рыбные блюда' => '🐟',
            'десерты' => '🍰',
            'торты' => '🎂',
            'выпечка' => '🥐',
            'пироги' => '🥧',
            'напитки' => '🍹',
            'коктейли' => '🍸',
            'паста' => '🍝',
            'пицца' => '🍕',
            'суши' => '🍣',
            'завтраки' => '🍳',
            'каши' => '🥣',
            'соусы' => '🥫',
            'консервация' => '🫙',
        ];

        $categoryName = mb_strtolower($category->name);
        
        foreach ($emojiMap as $key => $emoji) {
            if (Str::contains($categoryName, $key)) {
                return $emoji;
            }
        }

        return '🍴';
    }

    /**
     * Генерация хештегов для рецепта
     */
    protected function generateHashtags(Recipe $recipe): string
    {
        $hashtags = ['#рецепт', '#кулинария', '#яедок'];

        // Хештег категории
        if ($recipe->category) {
            $categoryTag = Str::slug($recipe->category->name, '');
            $hashtags[] = '#' . $categoryTag;
        }

        // Хештеги из названия (первые 2-3 значимых слова)
        $titleWords = explode(' ', $recipe->title);
        $meaningfulWords = array_filter($titleWords, function($word) {
            return mb_strlen($word) > 3 && !in_array(mb_strtolower($word), ['блюда', 'рецепт', 'вкусный']);
        });
        
        $count = 0;
        foreach (array_slice($meaningfulWords, 0, 2) as $word) {
            if ($count >= 2) break;
            $tag = Str::slug($word, '');
            if (!empty($tag)) {
                $hashtags[] = '#' . $tag;
                $count++;
            }
        }

        return implode(' ', $hashtags);
    }

    /**
     * Тест соединения с ботом
     */
    public function testConnection(): bool
    {
        try {
            $me = $this->bot->getMe();
            Log::info('Telegram bot connected successfully', [
                'bot_username' => $me->getUsername(),
                'bot_name' => $me->getFirstName()
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Telegram bot connection failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
