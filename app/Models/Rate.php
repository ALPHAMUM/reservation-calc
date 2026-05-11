<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    protected $table = 'tbl_rates';
    
    protected $fillable = [
        'rate_code',
        'rate_value',
        'type',
        'rate_extra',
        'description'
    ];
}
