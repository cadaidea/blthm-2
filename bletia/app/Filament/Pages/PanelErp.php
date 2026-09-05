<?php

namespace App\Filament\Pages;


use App\Support\Acl;
use App\Filament\Widgets\ErpPorEstado;
use App\Filament\Widgets\ErpStats;
use Filament\Pages\Page;

class PanelErp extends Page
{

    public static function canAccess(): bool { return static::canViewAny(); }

    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?string $title = 'Panel de gerencia';
    protected static ?string $navigationLabel = 'Panel de gerencia';
    protected static ?int $navigationSort = 0;
    protected string $view = 'filament.pages.panel-erp';

    protected function getHeaderWidgets(): array
    {
        return [ErpStats::class, ErpPorEstado::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
