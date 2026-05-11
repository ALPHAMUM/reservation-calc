<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservations extends Model
{
    use HasFactory;

    protected $fillable = [
        'occupant_id',
        'accomodation_id',
        'rate_code',
        'amount',
        'availability'
    ];

    protected $casts = [
        'acc_date' => 'date',
    ];
    public $timestamps = true;

    public function accomodation()
    {
        return $this->belongsTo(Accomodations::class, 'accomodation_id');
    }

    public function occupant()
    {
        return $this->belongsTo(Occupants::class, 'occupant_id');
    }
}
