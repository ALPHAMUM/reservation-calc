<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationScTarget extends Model
{
    protected $fillable = [
        'property',
        'res_no',
        'occupant_idx',
        'mode',
    ];
}
