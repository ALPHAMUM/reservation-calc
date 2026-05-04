<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReservationExport implements FromArray, WithHeadings, WithMapping
{
    protected $reservations;

    public function __construct(array $reservations)
    {
        $this->reservations = $reservations;
    }

    public function array(): array
    {
        return $this->reservations;
    }

    public function headings(): array
    {
        return [
            'RSVN#',
            'GUEST NAME',
            'ROOM TYPE',
            'ARRIVAL',
            'DEPARTURE'
        ];
    }

    public function map($reservation): array
    {
        return [
            $reservation['resNo'] ?? $reservation['conf'] ?? 'N/A',
            $reservation['gstName'] ?? $reservation['guestName'] ?? 'N/A',
            $reservation['roomtyp'] ?? $reservation['roomType'] ?? 'N/A',
            $reservation['arrdt'] ?? $reservation['arrDt'] ?? 'N/A',
            $reservation['depdt'] ?? $reservation['depDt'] ?? 'N/A',
        ];
    }
}
