<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetMinisterFieldVisibilities extends Command
{
    protected $signature = 'ministers:reset-visibilities {--user= : ID конкретного пользователя}';
    protected $description = 'Сбросить настройки видимости полей у служителей до значений по умолчанию';

    public function handle()
    {
        $userId = $this->option('user');
        
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("Пользователь с ID {$userId} не найден");
                return 1;
            }
            
            if (!$user->isMinister()) {
                $this->error("Пользователь {$user->name} не имеет роли 'minister'");
                return 1;
            }
            
            $user->initializeFieldVisibilities();
            $this->info("✅ Настройки сброшены для служителя: {$user->name}");
        } else {
            // Сброс для всех служителей
            $ministers = User::role('minister')->get();
            $count = 0;
            
            foreach ($ministers as $minister) {
                $minister->initializeFieldVisibilities();
                $count++;
                $this->line("Сброшено: {$minister->name}");
            }
            
            $this->info("✅ Настройки сброшены для {$count} служителей");
        }
        
        return 0;
    }
}