<?php

namespace Modules\MondayIntegration\Models;

use Illuminate\Database\Eloquent\Model;

class MondayFieldMapping extends Model
{
    protected $fillable = [
        'freescout_field',
        'monday_column',
        'transform_rule',
        'is_overwrite',
        'search_key',
        'order'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'order' => 'integer'
    ];

    public function transform($value)
    {
        if (empty($this->transform_rule)) {
            return $value;
        }

        switch ($this->transform_rule) {
            case 'uppercase':
                return strtoupper($value);
            case 'lowercase':
                return strtolower($value);
            case 'capitalize':
                return ucfirst($value);
            case 'boolean':
                return (bool)$value;
            case 'integer':
                return (int)$value;
            default:
                return $value;
        }
    }

    public static function getFieldMappings()
    {
        return static::orderBy('order')->get()->mapWithKeys(function ($mapping) {
            return [$mapping->smarticks_field => $mapping->monday_column];
        })->toArray();
    }
}
