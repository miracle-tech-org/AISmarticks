<?php

namespace Modules\AIAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIAgentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'openai_api_key' => 'required|string',
            'model' => 'required|string',
            'system_prompt' => 'nullable|string',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'AI Agent name is required.',
            'openai_api_key.required' => 'OpenAI API Key is required.',
            'model.required' => 'Model is required.',
        ];
    }

    public function sanitizedArray()
    {
        $sanitized = [];
        $input = $this->all();

        $sanitized['name'] = trim($input['name']);
        $sanitized['openai_api_key'] = trim($input['openai_api_key']);
        $sanitized['model'] = trim($input['model']);
        $sanitized['system_prompt'] = trim($input['system_prompt'] ?? '');

        return $sanitized;
    }
}
