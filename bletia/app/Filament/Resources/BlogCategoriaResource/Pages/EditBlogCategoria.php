<?php
namespace App\Filament\Resources\BlogCategoriaResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\BlogCategoriaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditBlogCategoria extends EditRecord {
    protected static string $resource = BlogCategoriaResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
