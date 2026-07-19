<?php
// app/Models/BibleTestQuestion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibleTestQuestion extends Model
{
    protected $table = 'bible_test_questions';

    protected $fillable = [
        'lesson_id',
        'theme_id',
        'type',
        'question',
        'config',
        'points',
        'order',
    ];

    protected $casts = [
        'config' => 'array',
        'points' => 'integer',
        'order' => 'integer',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(BibleLesson::class, 'lesson_id');
    }

    public function essays(): HasMany
    {
        return $this->hasMany(BibleEssay::class, 'question_id');
    }

    public function validateAnswer($answer): array
    {
        return match ($this->type) {
            'single_choice' => $this->validateSingleChoice($answer),
            'multiple_choice' => $this->validateMultipleChoice($answer),
            'matching' => $this->validateMatching($answer),
            'ordering' => $this->validateOrdering($answer),
            'odd_one_out' => $this->validateOddOneOut($answer),
            'verse_reference' => $this->validateVerseReference($answer),
            'select_verse' => $this->validateSelectVerse($answer),
            'true_false' => $this->validateTrueFalse($answer),
            'fill_blank' => $this->validateFillBlank($answer),
            default => [
                'correct' => false,
                'score' => 0,
                'feedback' => null,
            ],
        };
    }

    protected function validateSingleChoice($answer): array
    {
        $config = $this->config ?? [];

        $correct = isset($config['correct'])
            && (string) $answer === (string) $config['correct'];

        return [
            'correct' => $correct,
            'score' => $correct ? $this->points : 0,
            'feedback' => $correct ? null : ($config['explanation'] ?? 'Неправильный ответ'),
        ];
    }

    protected function validateMultipleChoice($answer): array
    {
        $config = $this->config ?? [];

        $correctAnswers = $config['correct'] ?? [];

        if (! is_array($correctAnswers)) {
            $correctAnswers = [$correctAnswers];
        }

        if (! is_array($answer)) {
            $answer = [$answer];
        }

        $answer = array_map('strval', $answer);
        $correctAnswers = array_map('strval', $correctAnswers);

        sort($answer);
        sort($correctAnswers);

        $correct = $answer === $correctAnswers;

        return [
            'correct' => $correct,
            'score' => $correct ? $this->points : 0,
            'feedback' => $correct ? null : ($config['explanation'] ?? 'Не все ответы верны'),
        ];
    }

    protected function validateMatching($answer): array
    {
        $config = $this->config ?? [];
        $matches = $config['matches'] ?? [];

        if (! is_array($answer)) {
            return [
                'correct' => false,
                'score' => 0,
                'feedback' => 'Неверный формат ответа',
            ];
        }

        $isCorrect = true;

        foreach ($matches as $match) {
            $leftId = $match['left'] ?? null;
            $rightId = $match['right'] ?? null;

            if ($leftId === null || $rightId === null) {
                $isCorrect = false;
                break;
            }

            if (! array_key_exists($leftId, $answer) || (string) $answer[$leftId] !== (string) $rightId) {
                $isCorrect = false;
                break;
            }
        }

        return [
            'correct' => $isCorrect,
            'score' => $isCorrect ? $this->points : 0,
            'feedback' => $isCorrect ? null : 'Соответствия неверны',
        ];
    }

    protected function validateOrdering($answer): array
{
    $config = $this->config ?? [];
    
    // ✅ Если config строка — декодируем в массив
    if (is_string($config)) {
        $config = json_decode($config, true);
    }
    
    if (!is_array($config)) {
        $config = [];
    }
    
    $items = $config['items'] ?? [];

    if (!is_array($answer)) {
        $answer = [$answer];
    }

    $correctOrder = collect($items)
        ->sortBy('correct_order')
        ->pluck('text')
        ->map(fn ($value) => trim((string) $value))
        ->values()
        ->toArray();

    $userOrder = collect($answer)
        ->map(fn ($value) => trim((string) $value))
        ->values()
        ->toArray();

    $correct = $userOrder === $correctOrder;

    return [
        'correct' => $correct,
        'score' => $correct ? $this->points : 0,
        'feedback' => $correct ? null : 'Порядок элементов неверен',
    ];
}

    protected function validateOddOneOut($answer): array
    {
        $config = $this->config ?? [];

        $correct = isset($config['correct_odd'])
            && (string) $answer === (string) $config['correct_odd'];

        return [
            'correct' => $correct,
            'score' => $correct ? $this->points : 0,
            'feedback' => $correct ? null : ($config['explanation'] ?? 'Выбран неверный элемент'),
        ];
    }

    protected function validateVerseReference($answer): array
{
    \Log::info('=== validateVerseReference ===', [
        'answer' => $answer,
        'config' => $this->config
    ]);

    $parsed = $this->parseVerseReference((string) $answer);
    
    \Log::info('Parsed result:', $parsed ?? ['null']);
    
    if (! $parsed) {
        return [
            'correct' => false,
            'score' => 0,
            'feedback' => 'Введите ссылку в формате "Книга глава:стих" (например, Ин. 3:16)',
        ];
    }

    $expectedBook = (string) ($this->config['expected_book'] ?? '');
    $expectedChapter = $this->config['expected_chapter'] ?? null;
    $expectedVerse = $this->config['expected_verse'] ?? null;
    
    \Log::info('Expected:', [
        'book' => $expectedBook,
        'chapter' => $expectedChapter,
        'verse' => $expectedVerse
    ]);

    $acceptAlternativeNotations = (bool) ($this->config['accept_alternative_notations'] ?? true);

    if ($acceptAlternativeNotations) {
        $normalizedParsedBook = $this->normalizeBibleBookName($parsed['book']);
        $normalizedExpectedBook = $this->normalizeBibleBookName($expectedBook);
        
        \Log::info('Normalized books:', [
            'parsed' => $normalizedParsedBook,
            'expected' => $normalizedExpectedBook
        ]);
        
        $bookMatch = $normalizedParsedBook === $normalizedExpectedBook;
    } else {
        $bookMatch = mb_strtolower(trim($parsed['book'])) === mb_strtolower(trim($expectedBook));
    }

    $chapterMatch = (int) $parsed['chapter'] === (int) $expectedChapter;
    $verseMatch = (int) $parsed['verse'] === (int) $expectedVerse;
    
    \Log::info('Matches:', [
        'bookMatch' => $bookMatch,
        'chapterMatch' => $chapterMatch,
        'verseMatch' => $verseMatch
    ]);

    $correct = $bookMatch && $chapterMatch && $verseMatch;

    return [
        'correct' => $correct,
        'score' => $correct ? $this->points : 0,
        'feedback' => $correct ? null : "Ожидалось: {$expectedBook} {$expectedChapter}:{$expectedVerse}",
    ];
}

    protected function parseVerseReference(string $reference): ?array
{
    \Log::info('Parsing reference:', ['original' => $reference]);
    
    $reference = trim($reference);
    $reference = preg_replace('/\s+/u', ' ', $reference);
    
    // Паттерн для форматов: Ин. 3:16, Иоанна 3:16, 1 Кор. 13:4
    $pattern = '/^([\pL\s\.0-9]+?)\s*(\d+):(\d+)$/u';
    
    if (preg_match($pattern, $reference, $matches)) {
        \Log::info('Pattern matched:', $matches);
        return [
            'book' => trim($matches[1]),
            'chapter' => (int) $matches[2],
            'verse' => (int) $matches[3],
        ];
    }
    
    // Альтернативный паттерн для форматов без пробела: 1Ин. 3:16
    $pattern2 = '/^([\pL\.0-9]+?)(\d+):(\d+)$/u';
    if (preg_match($pattern2, $reference, $matches)) {
        \Log::info('Pattern2 matched:', $matches);
        return [
            'book' => trim($matches[1]),
            'chapter' => (int) $matches[2],
            'verse' => (int) $matches[3],
        ];
    }
    
    \Log::warning('No pattern matched');
    return null;
}

    protected function normalizeBibleBookName(string $book): string
{
    $original = $book;
    $book = mb_strtolower(trim($book));
    $book = str_replace('.', '', $book);
    $book = preg_replace('/\s+/u', ' ', $book);
    
    \Log::info('Normalizing book:', ['original' => $original, 'after' => $book]);

    $aliases = [
        'ин' => 'иоанна',
        'иоан' => 'иоанна',
        'иоанна' => 'иоанна',
        '1 ин' => '1 иоанна',
        '1ин' => '1 иоанна',  // ← добавить для поддержки без пробела
        '2 ин' => '2 иоанна',
        '2ин' => '2 иоанна',
        '3 ин' => '3 иоанна',
        '3ин' => '3 иоанна',
        'мф' => 'матфея',
        'матф' => 'матфея',
        'матфея' => 'матфея',
        'мк' => 'марка',
        'мар' => 'марка',
        'марка' => 'марка',
        'лк' => 'луки',
        'лук' => 'луки',
        'луки' => 'луки',
        '1 кор' => '1 коринфянам',
        '1кор' => '1 коринфянам',
        '2 кор' => '2 коринфянам',
        '2кор' => '2 коринфянам',
        '1 тим' => '1 тимофею',
        '1тим' => '1 тимофею',
        '2 тим' => '2 тимофею',
        '2тим' => '2 тимофею',
        '1 пет' => '1 петра',
        '1пет' => '1 петра',
        '2 пет' => '2 петра',
        '2пет' => '2 петра',
    ];

    $result = $aliases[$book] ?? $book;
    \Log::info('Normalized result:', ['result' => $result]);
    
    return $result;
}

    protected function validateSelectVerse($answer): array
    {
        $config = $this->config ?? [];

        $correct = isset($config['correct'])
            && (string) $answer === (string) $config['correct'];

        return [
            'correct' => $correct,
            'score' => $correct ? $this->points : 0,
            'feedback' => $correct ? null : ($config['explanation'] ?? 'Выбран неверный стих'),
        ];
    }

    protected function validateTrueFalse($answer): array
{
    $config = $this->config ?? [];
    
    // ✅ Если config строка — декодируем в массив
    if (is_string($config)) {
        $config = json_decode($config, true);
    }
    
    if (!is_array($config)) {
        $config = [];
    }

    $correct = array_key_exists('correct', $config)
        && (bool) $answer === (bool) ($config['correct'] ?? false);

    return [
        'correct' => $correct,
        'score' => $correct ? $this->points : 0,
        'feedback' => $correct ? null : ($config['explanation'] ?? 'Неверно'),
    ];
}

    protected function validateFillBlank($answer): array
    {
        $config = $this->config ?? [];

        /*
         * Поддерживает оба формата:
         *
         * 1) Новый/Filament repeater:
         * [
         *     ['answer' => 'Бог'],
         *     ['answer' => 'Господь'],
         * ]
         *
         * 2) Простой:
         * [
         *     'Бог',
         *     'Господь',
         * ]
         */
        $correctAnswers = collect($config['answers'] ?? [])
            ->map(function ($item) {
                if (is_array($item)) {
                    return $item['answer'] ?? null;
                }

                return $item;
            })
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => trim((string) $value))
            ->values()
            ->toArray();

        $caseSensitive = (bool) ($config['case_sensitive'] ?? false);

        $userAnswer = trim((string) $answer);

        if (! $caseSensitive) {
            $userAnswer = mb_strtolower($userAnswer);

            $correctAnswers = array_map(
                fn ($value) => mb_strtolower($value),
                $correctAnswers
            );
        }

        $correct = in_array($userAnswer, $correctAnswers, true);

        return [
            'correct' => $correct,
            'score' => $correct ? $this->points : 0,
            'feedback' => $correct ? null : 'Неправильный ответ',
        ];
    }
}
