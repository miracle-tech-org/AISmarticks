<?php

namespace Modules\MondayIntegration\Services;

use Modules\MondayIntegration\Models\MondayFieldMapping;

class DataTransformer
{
    protected $fieldMappings;

    public function __construct()
    {
        $this->fieldMappings = $this->loadFieldMappings();
    }

    protected function loadFieldMappings()
    {
        return MondayFieldMapping::orderBy('order')
            ->get()
            ->keyBy('smarticks_field')
            ->toArray();
    }

    public function conversationToMondayItem($conversation)
    {
        $columnValues = [];

        foreach ($this->fieldMappings as $field => $mapping) {
            $value = $this->getConversationValue($conversation, $field);
            
            if ($value || $mapping['is_required']) {
                $transformedValue = $this->transformValue(
                    $value ?? $mapping['default_value'],
                    $mapping['transform_rule']
                );
                
                $columnValues[$mapping['monday_column']] = $transformedValue;
            }
        }

        return [
            'name' => $conversation->subject,
            'column_values' => $columnValues
        ];
    }

    public function mondayItemToConversationData($item)
    {
        $data = [];
        $reverseMappings = array_flip($this->fieldMappings);

        foreach ($item['column_values'] as $column) {
            if (isset($reverseMappings[$column['column']['id']])) {
                $field = $reverseMappings[$column['column']['id']];
                $data[$field] = $this->reverseTransform(
                    $column['value'],
                    $this->fieldMappings[$field]['transform_rule']
                );
            }
        }

        return $data;
    }

    protected function getConversationValue($conversation, $field)
    {
        $parts = explode('.', $field);
        
        switch ($parts[0]) {
            case 'conversation':
                return $conversation->{$parts[1]} ?? null;
            
            case 'customer':
                return $conversation->customer->{$parts[1]} ?? null;
            
            case 'assignee':
                return $conversation->user->{$parts[1]} ?? null;
            
            case 'custom_fields':
                return $conversation->getMeta($parts[1]);
            
            default:
                return null;
        }
    }

    protected function transformValue($value, $rule)
    {
        if (!$rule) {
            return $value;
        }

        switch ($rule) {
            case 'uppercase':
                return strtoupper($value);
            
            case 'lowercase':
                return strtolower($value);
            
            case 'date_format':
                return date('Y-m-d', strtotime($value));
            
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            
            case 'json':
                return json_encode($value);
            
            default:
                if (is_callable($rule)) {
                    return call_user_func($rule, $value);
                }
                return $value;
        }
    }

    protected function reverseTransform($value, $rule)
    {
        if (!$rule) {
            return $value;
        }

        switch ($rule) {
            case 'json':
                return json_decode($value, true);
            
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            
            case 'date_format':
                return date('Y-m-d H:i:s', strtotime($value));
            
            default:
                return $value;
        }
    }
}
