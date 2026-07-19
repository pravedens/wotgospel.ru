<?php
// app/Services/CensorService.php

namespace App\Services;

class CensorService
{
    protected array $profanity = [
        'хуй', 'пизда', 'ебать', 'залупа', 'блядь', 'мудак', 'сука',
        'гандон', 'пидор', 'хуесос', 'долбоеб', 'хуйло', 'сволочь',
        'тварь', 'ублюдок', 'курва', 'шлюха', 'бля', 'нахер', 'нахуй',
        'охуел', 'охуеть', 'пиздец', 'ебучий', 'хуета', 'расчлененка',
        'дебил', 'идиот', 'кретин', 'урод', 'выродок', 'недоносок',
        'проститутка', 'блядина', 'шмара', 'шалава'
    ];
    
    public function containsProfanity(string $text): bool
    {
        $text = mb_strtolower($text);
        
        foreach ($this->profanity as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function censor(string $text): string
    {
        $textLower = mb_strtolower($text);
        
        foreach ($this->profanity as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/ui';
            $replacement = str_repeat('*', mb_strlen($word));
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        return $text;
    }
}