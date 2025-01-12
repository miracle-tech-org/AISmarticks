<?php

namespace Modules\AIMiddleware\Entities;

use Illuminate\Database\Eloquent\Model;

class AIAgent extends Model
{
    protected $fillable = [
        'name',
        'model',
        'system_prompt',
        'default_tags',
        'active'
    ];

    protected $casts = [
        'default_tags' => 'array',
        'active' => 'boolean'
    ];

    /**
     * Get all interactions for this agent
     */
    public function interactions()
    {
        return $this->hasMany(AIInteractionLog::class, 'agent_id');
    }

    /**
     * Convert tags array to string
     */
    public function getDefaultTagsAsString(): string
    {
        return is_array($this->default_tags) 
            ? implode(', ', $this->default_tags) 
            : '';
    }

    /**
     * Set tags from comma-separated string
     */
    public function setDefaultTagsFromString(string $tags): void
    {
        $this->default_tags = array_map(
            'trim',
            explode(',', $tags)
        );
    }
}
