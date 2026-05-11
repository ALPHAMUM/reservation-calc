<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guests extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'member_id'
    ];

    public $timestamps = true;

    public function Occupants()
    {
        return $this->belongsTo(Occupants::class, 'id');
    }
}
