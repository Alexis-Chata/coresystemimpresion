<?php

namespace App\Filament\Resources\ImpresoraResource\Pages;

use App\Filament\Resources\ImpresoraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImpresoras extends ListRecords
{
    protected static string $resource = ImpresoraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
