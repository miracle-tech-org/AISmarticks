<?php

namespace Modules\MondayIntegration\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\MondayIntegration\Services\SecurityService;

class MondaySettings extends Model
{
    protected $fillable = ['key', 'value', 'is_encrypted'];

    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        if ($setting->is_encrypted) {
            $security = app(SecurityService::class);
            return $security->decrypt($setting->value);
        }

        return $setting->value;
    }

    public static function setValue($key, $value, $encrypt = false)
    {
        if ($encrypt) {
            $security = app(SecurityService::class);
            $value = $security->encrypt($value);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'is_encrypted' => $encrypt
            ]
        );
    }
}
