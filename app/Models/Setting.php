<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Setting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            $setting = static::where('key', $key)->first();
            return ($setting && !is_null($setting->value) && $setting->value !== '') ? $setting->value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
