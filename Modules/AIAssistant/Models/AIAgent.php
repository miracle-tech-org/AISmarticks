<?php

namespace Modules\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class AIAgent extends Model
{
    protected $table = "ai_agents";
    
    protected $fillable = [
        'name',
        'openai_api_key',
        'model',
        'system_prompt',
        'mailbox_id',
        'domain_specific_responses', // New field for domain-specific responses
    ];

    /**
     * Validate AI Agent Inputs
     *
     * @param array $inputs
     *
     * @return array
     */
    public static function validateInputs(array $inputs)
    {
        $validator = Validator::make($inputs, [
            'name' => 'required|string|max:255',
            'openai_api_key' => 'required|string',
            'model' => 'required|string',
            'system_prompt' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid input: ' . $validator->getMessageBag()->first());
        }

        return $inputs;
    }

    /**
     * Mutator to encrypt the API key
     *
     * @param string $value
     */
    // public function setOpenaiApiKeyAttribute($value)
    // {
    //     $this->attributes['openai_api_key'] = Crypt::encryptString($value);
    // }

    /**
     * Accessor to decrypt the API key
     *
     * @param string $value
     *
     * @return string
     */
    // public function getOpenaiApiKeyAttribute($value)
    // {
    //     return Crypt::decryptString($value);
    // }

    protected $hidden = ['openai_api_key']; // Hide API key in JSON responses

    /**
     * Relationship: AI Agent Assignments
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignments()
    {
        return $this->hasMany(AIAgentAssignment::class);
    }

    protected $casts = [
        'domain_specific_responses' => 'array',
    ];
}
