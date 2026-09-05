<?php
namespace App\Filament\Resources\BlogCategoriaResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\BlogCategoriaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListBlogCategoria extends ListRecords {
    protected static string $resource = BlogCategoriaResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
