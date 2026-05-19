<?php

namespace App\Exports;

use App\Services\NeracaService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class NeracaExport implements WithMultipleSheets
{
    public function __construct(
        protected array $periodeList,
        protected bool $tampilkanSaldoNol = false,
    ) {}

    public function sheets(): array
    {
        $neracaMulti = app(NeracaService::class)->hitungMulti($this->periodeList);

        $sheets = [];
        foreach ($neracaMulti as $key => $neraca) {
            $sheets[] = new NeracaSheetExport($neraca, $this->tampilkanSaldoNol);
        }

        return $sheets;
    }
}