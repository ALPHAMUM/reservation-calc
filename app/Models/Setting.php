<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key, decoded from JSON.
     */
    public static function getValue(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();
        if (!$row) return $default;
        return json_decode($row->value, true) ?? $default;
    }

    /**
     * Store a setting value by key, encoded as JSON.
     */
    public static function setValue(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value, JSON_PRETTY_PRINT)]
        );
    }
}
