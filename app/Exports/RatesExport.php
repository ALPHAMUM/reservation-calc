<?php

namespace App\Exports;

use App\Models\Rate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RatesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Rate::orderBy('rate_code')->get();
    }

    public function headings(): array
    {
        return [
            'RATE CODE',
            'RATE VALUE',
            'RATE EXTRA',
            'TYPE',
            'DESCRIPTION',
        ];
    }

    public function map($rate): array
    {
        return [
            $rate->rate_code,
            $rate->rate_value,
            $rate->rate_extra,
            $rate->type,
            $rate->description,
        ];
    }
}
