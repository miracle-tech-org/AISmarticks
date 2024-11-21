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
            'openai_api_key' => 'required|string|min:32|max:64',
            'model' => 'required|string|in:gpt-3.5-turbo,gpt-4',
            'system_prompt' => 'nullable|string|max:2048',
            'domain_specific_responses' => 'nullable|array', // New field for domain-specific responses
            'domain_specific_responses.*.domain' => 'equired|string',
            'domain_specific_responses.*.responses' => 'equired|array',
            'domain_specific_responses.*.responses.*' => 'equired|string',
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
            'model.in' => 'Only gpt-3.5-turbo and gpt-4 models are supported.',
            'domain_specific_responses.*.domain.required' => 'Domain is required for domain-specific responses.',
            'domain_specific_responses.*.responses.required' => 'Responses are required for domain-specific responses.',
            'domain_specific_responses.*.responses.*.required' => 'Each response must be a string.',
        ];
    }

    public function sanitizedArray()
    {
        $sanitized = [];
        $input = $this->all();

        $sanitized['name'] = trim($input['name']);
        $sanitized['openai_api_key'] = trim($input['openai_api_key']);
        $sanitized['model'] = trim($input['model']);
        $sanitized['system_prompt'] = trim($input['system_prompt']?? '');
        $sanitized['domain_specific_responses'] = [];

        foreach ($input['domain_specific_responses']?? [] as $domainResponse) {
            $sanitizedDomain = [
                'domain' => trim($domainResponse['domain']),
                'esponses' => array_map('trim', $domainResponse['responses']),
            ];
            $sanitized['domain_specific_responses'][] = $sanitizedDomain;
        }

        return $sanitized;
    }
}
