<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accomodations extends Model
{
    use HasFactory;

    protected $fillable = [
        'accomodation_code',
        'accomodation_name',
        'capacity',
        'status',
        'base_rate'
    ];

    public $timestamps = true;
}
