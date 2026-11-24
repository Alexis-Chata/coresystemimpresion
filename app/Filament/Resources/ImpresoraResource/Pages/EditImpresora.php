<?php

namespace App\Filament\Resources\ImpresoraResource\Pages;

use App\Filament\Resources\ImpresoraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImpresora extends EditRecord
{
    protected static string $resource = ImpresoraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
