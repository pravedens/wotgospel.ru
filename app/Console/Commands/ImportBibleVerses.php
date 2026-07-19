<?php
// app/Console/Commands/ImportBibleVerses.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ImportBibleVerses extends Command
{
    protected $signature = 'bible:import';
    protected $description = 'Import Bible verses from getbible.net';

    public function handle()
    {
        $this->info('Starting Bible import...');

        $books = [
            'GEN' => 'Бытие', 'EXO' => 'Исход', 'LEV' => 'Левит', 'NUM' => 'Числа',
            'DEU' => 'Второзаконие', 'JOS' => 'Иисус Навин', 'JDG' => 'Судьи',
            'RUT' => 'Руфь', '1SA' => '1 Царств', '2SA' => '2 Царств',
            '1KI' => '3 Царств', '2KI' => '4 Царств', '1CH' => '1 Паралипоменон',
            '2CH' => '2 Паралипоменон', 'EZR' => 'Ездра', 'NEH' => 'Неемия',
            'EST' => 'Есфирь', 'JOB' => 'Иов', 'PSA' => 'Псалтирь',
            'PRO' => 'Притчи', 'ECC' => 'Екклесиаст', 'SNG' => 'Песня Песней',
            'ISA' => 'Исаия', 'JER' => 'Иеремия', 'LAM' => 'Плач Иеремии',
            'EZK' => 'Иезекииль', 'DAN' => 'Даниил', 'HOS' => 'Осия',
            'JOL' => 'Иоиль', 'AMO' => 'Амос', 'OBA' => 'Авдий',
            'JON' => 'Иона', 'MIC' => 'Михей', 'NAM' => 'Наум',
            'HAB' => 'Аввакум', 'ZEP' => 'Софония', 'HAG' => 'Аггей',
            'ZEC' => 'Захария', 'MAL' => 'Малахия', 'MAT' => 'Матфея',
            'MRK' => 'Марка', 'LUK' => 'Луки', 'JHN' => 'Иоанна',
            'ACT' => 'Деяния', 'ROM' => 'Римлянам', '1CO' => '1 Коринфянам',
            '2CO' => '2 Коринфянам', 'GAL' => 'Галатам', 'EPH' => 'Ефесянам',
            'PHP' => 'Филиппийцам', 'COL' => 'Колоссянам', '1TH' => '1 Фессалоникийцам',
            '2TH' => '2 Фессалоникийцам', '1TI' => '1 Тимофею', '2TI' => '2 Тимофею',
            'TIT' => 'Титу', 'PHM' => 'Филимону', 'HEB' => 'Евреям',
            'JAS' => 'Иакова', '1PE' => '1 Петра', '2PE' => '2 Петра',
            '1JN' => '1 Иоанна', '2JN' => '2 Иоанна', '3JN' => '3 Иоанна',
            'JUD' => 'Иуды', 'REV' => 'Откровение'
        ];

        $totalVerses = 0;

        foreach ($books as $bookCode => $bookName) {
            $this->info("Processing: $bookName");
            
            $chapter = 1;
            $hasMoreChapters = true;
            
            while ($hasMoreChapters) {
                $url = "https://getbible.net/json?book=$bookCode&chapter=$chapter";
                
                try {
                    $response = Http::timeout(30)->get($url);
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        
                        if (isset($data['chapter']['verses'])) {
                            foreach ($data['chapter']['verses'] as $verseNum => $verseText) {
                                DB::table('bible_verses')->insert([
                                    'book' => $bookName,
                                    'book_abbr' => $this->getAbbr($bookName),
                                    'chapter' => $chapter,
                                    'verse' => $verseNum,
                                    'text' => trim($verseText),
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                $totalVerses++;
                            }
                            $this->info("  Chapter $chapter: " . count($data['chapter']['verses']) . " verses");
                            $chapter++;
                        } else {
                            $hasMoreChapters = false;
                        }
                    } else {
                        $hasMoreChapters = false;
                    }
                } catch (\Exception $e) {
                    $hasMoreChapters = false;
                }
            }
        }

        $this->info("Import completed! Total verses: $totalVerses");
    }

    private function getAbbr(string $bookName): string
    {
        $abbr = [
            'Бытие' => 'Быт.', 'Исход' => 'Исх.', 'Левит' => 'Лев.', 'Числа' => 'Чис.',
            'Второзаконие' => 'Втор.', 'Иисус Навин' => 'Ис. Нав.', 'Судьи' => 'Суд.',
            'Руфь' => 'Руф.', '1 Царств' => '1 Цар.', '2 Царств' => '2 Цар.',
            '3 Царств' => '3 Цар.', '4 Царств' => '4 Цар.', '1 Паралипоменон' => '1 Пар.',
            '2 Паралипоменон' => '2 Пар.', 'Ездра' => 'Езд.', 'Неемия' => 'Неем.',
            'Есфирь' => 'Есф.', 'Иов' => 'Иов', 'Псалтирь' => 'Пс.', 'Притчи' => 'Прит.',
            'Екклесиаст' => 'Еккл.', 'Песня Песней' => 'Песн.', 'Исаия' => 'Ис.',
            'Иеремия' => 'Иер.', 'Плач Иеремии' => 'Плач', 'Иезекииль' => 'Иез.',
            'Даниил' => 'Дан.', 'Осия' => 'Ос.', 'Иоиль' => 'Иоил.', 'Амос' => 'Ам.',
            'Авдий' => 'Авд.', 'Иона' => 'Иона', 'Михей' => 'Мих.', 'Наум' => 'Наум',
            'Аввакум' => 'Авв.', 'Софония' => 'Соф.', 'Аггей' => 'Агг.', 'Захария' => 'Зах.',
            'Малахия' => 'Мал.', 'Матфея' => 'Мф.', 'Марка' => 'Мк.', 'Луки' => 'Лк.',
            'Иоанна' => 'Ин.', 'Деяния' => 'Деян.', 'Римлянам' => 'Рим.',
            '1 Коринфянам' => '1 Кор.', '2 Коринфянам' => '2 Кор.', 'Галатам' => 'Гал.',
            'Ефесянам' => 'Еф.', 'Филиппийцам' => 'Флп.', 'Колоссянам' => 'Кол.',
            '1 Фессалоникийцам' => '1 Фес.', '2 Фессалоникийцам' => '2 Фес.',
            '1 Тимофею' => '1 Тим.', '2 Тимофею' => '2 Тим.', 'Титу' => 'Тит.',
            'Филимону' => 'Флм.', 'Евреям' => 'Евр.', 'Иакова' => 'Иак.',
            '1 Петра' => '1 Пет.', '2 Петра' => '2 Пет.', '1 Иоанна' => '1 Ин.',
            '2 Иоанна' => '2 Ин.', '3 Иоанна' => '3 Ин.', 'Иуды' => 'Иуд.',
            'Откровение' => 'Откр.',
        ];
        return $abbr[$bookName] ?? substr($bookName, 0, 3);
    }
}