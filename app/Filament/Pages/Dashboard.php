<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PenjualanTerakhirWidget;
use App\Filament\Widgets\PosButton;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';
    public function getWidgets(): array
    {

        return [
            PosButton::class,
            PenjualanTerakhirWidget::class,
        ];
    }
}

