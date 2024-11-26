<?php

namespace App;

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

    // public function setOpenaiApiKeyAttribute($value)
    // {
    //     $this->attributes['openai_api_key'] = Crypt::encryptString($value);
    // }

    // public function getOpenaiApiKeyAttribute($value)
    // {
    //     return Crypt::decryptString($value);
    // }

    protected $hidden = ['openai_api_key'];

    public function assignments()
    {
        // return $this->hasMany(AIAgentAssignment::class);
    }
}
