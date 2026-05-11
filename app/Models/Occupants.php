<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Occupants extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'age',
        'nationality',
        'occupant_type', // [Member, Spouse, Dependent, Guest , Infant (to be confirmed)]
        'status',
        'discount_type',
    ];

    protected $casts = [
        'birthday' => 'date',
        'arrival_date' => 'date',
        'departure_date' => 'date',
    ];
    public $timestamps = true;

    public function reservations()
    {
        return $this->hasMany(Reservations::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute()
    {
        if (!$this->birthday) {
            return null;
        }

        $now = Carbon::now();
        return $now->diffInYears($this->birthday);
    }
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
