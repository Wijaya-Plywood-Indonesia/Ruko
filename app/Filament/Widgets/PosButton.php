<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class PosButton extends Widget
{
    protected static ?int $sort = 1;
    protected string $view = 'filament.widgets.pos-button';

    protected int|string|array $columnSpan = 'full';

}
