<?php
// app/Filament/Resources/BiblePartyResource/Pages/CreateBibleParty.php

namespace App\Filament\Resources\BiblePartyResource\Pages;

use App\Filament\Resources\BiblePartyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBibleParty extends CreateRecord
{
    protected static string $resource = BiblePartyResource::class;
}