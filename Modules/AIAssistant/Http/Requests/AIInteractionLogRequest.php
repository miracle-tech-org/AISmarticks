<?php

namespace Modules\AIAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIInteractionLogRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'ai_agent_id' => 'required|exists:ai_agents,id',
            'mailbox_id' => 'nullable|exists:mailboxes,id',
            'conversation_id' => 'nullable|exists:conversations,id',
            'input_text' => 'required|string|max:2048',
            'output_text' => 'required|string|max:2048',
            'tokens_used' => 'required|integer|min:1',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
