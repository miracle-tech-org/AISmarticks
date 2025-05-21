<?php

namespace Modules\MondayIntegration\Services;

class FieldMappingAssistantService
{
    /**
     * Get all available FreeScout fields for mapping
     * 
     * @return array Hierarchical array of fields grouped by category
     */
    public function getAllAvailableFields()
    {
        $fields = [
            'User Data' => $this->getUserFields(),
            'Customer Data' => $this->getCustomerData(),
            'Conversation Data' => $this->getConversationFields(),
            'Thread Data' => $this->getThreadFields(),
            'Customer Fields' => $this->getCustomerFields(),
        ];

        return $fields;
    }

    /**
     * Get available user fields
     * 
     * @return array
     */
    public function getUserFields()
    {
        return [
            'user.id' => 'User ID',
            'user.first_name' => 'First Name',
            'user.last_name' => 'Last Name',
            'user.email' => 'Email Address',
            'user.phone' => 'Phone Number',
            'user.job_title' => 'Job Title',
            'user.timezone' => 'Timezone',
            'user.photo_url' => 'Profile Picture URL',
            'user.role' => 'User Role',
            'user.status' => 'User Status',
            'user.created_at' => 'Created Date',
            'user.updated_at' => 'Last Updated Date',
        ];
    }

    /**
     * Get available customer fields
     * 
     * @return array
     */
    public function getCustomerData()
    {
        return [
            'customer.id' => 'Customer ID',
            'customer.first_name' => 'First Name',
            'customer.last_name' => 'Last Name',
            'customer.email' => 'Email Address',
            'customer.emails' => 'All Email Addresses',
            'customer.phone' => 'Phone Number',
            'customer.company' => 'Company',
            'customer.job_title' => 'Job Title',
            'customer.address' => 'Address',
            'customer.city' => 'City',
            'customer.state' => 'State',
            'customer.zip' => 'Zip/Postal Code',
            'customer.country' => 'Country',
            'customer.website' => 'Website',
            'customer.social_profiles' => 'Social Profiles',
            'customer.photo_url' => 'Profile Picture URL',
            'customer.created_at' => 'Created Date',
            'customer.updated_at' => 'Last Updated Date',
        ];
    }

    /**
     * Get available conversation fields
     * 
     * @return array
     */
    public function getConversationFields()
    {
        return [
            'conversation.id' => 'Conversation ID',
            'conversation.number' => 'Conversation Number',
            'conversation.subject' => 'Subject',
            'conversation.customer_id' => 'Customer ID',
            'conversation.mailbox_id' => 'Mailbox ID',
            'conversation.folder_id' => 'Folder ID',
            'conversation.status' => 'Status',
            'conversation.state' => 'State',
            'conversation.preview' => 'Preview',
            'conversation.source_via' => 'Source Via',
            'conversation.source_type' => 'Source Type',
            'conversation.created_at' => 'Created Date',
            'conversation.updated_at' => 'Last Updated Date',
            'conversation.user_updated_at' => 'Last Updated by User Date',
            'conversation.closed_at' => 'Closed Date',
            'conversation.assigned_to' => 'Assigned User ID',
            'conversation.last_reply_at' => 'Last Reply Date',
            'conversation.last_reply_from' => 'Last Reply From',
            'conversation.has_attachments' => 'Has Attachments',
        ];
    }

    /**
     * Get available thread fields
     * 
     * @return array
     */
    protected function getThreadFields()
    {
        return [
            'thread.id' => 'Thread ID',
            'thread.conversation_id' => 'Conversation ID',
            'thread.user_id' => 'User ID',
            'thread.customer_id' => 'Customer ID',
            'thread.type' => 'Type',
            'thread.state' => 'State',
            'thread.status' => 'Status',
            'thread.body' => 'Body',
            'thread.headers' => 'Headers',
            'thread.has_attachments' => 'Has Attachments',
            'thread.created_at' => 'Created Date',
            'thread.updated_at' => 'Last Updated Date',
            'thread.source_via' => 'Source Via',
            'thread.source_type' => 'Source Type',
        ];
    }

    /**
     * Get custom fields
     * 
     * @return array
     */
    protected function getCustomerFields()
    {
        try {
            // Try to get custom fields from the database
            $customFields = [];

            // Check if the CustomField model exists in FreeScout
            // if (class_exists('\App\CustomField')) {
            //     $fields = \App\CustomField::all();
            //     foreach ($fields as $field) {
            //         $key = 'custom_fields.' . $field->id;
            //         $customFields[$key] = $field->name;
            //     }
            // }
            
            // If there's a module for custom fields, try to get fields from there too
            if (class_exists('\Modules\Crm\Entities\CustomerField')) {
                $fields = \Modules\Crm\Entities\CustomerField::all();
                foreach ($fields as $field) {
                    $key = 'customer_fields_' . $field->id;
                    $customFields[$key] = $field->name;
                }
            }
            
            return $customFields;
        } catch (\Exception $e) {
            \Log::error('Error fetching custom fields: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the appropriate Monday.com column type for a FreeScout field
     * 
     * @param string $fieldKey Field key
     * @return string Monday.com column type
     */
    public function getColumnTypeForField($fieldKey)
    {
        // Map field keys to Monday.com column types
        $typeMap = [
            // Email fields
            'user.email' => 'email',
            'customer.email' => 'email',
            
            // Phone fields
            'user.phone' => 'phone',
            'customer.phone' => 'phone',
            
            // Date fields
            'user.created_at' => 'date',
            'user.updated_at' => 'date',
            'customer.created_at' => 'date',
            'customer.updated_at' => 'date',
            'conversation.created_at' => 'date',
            'conversation.updated_at' => 'date',
            'conversation.closed_at' => 'date',
            'conversation.last_reply_at' => 'date',
            'thread.created_at' => 'date',
            'thread.updated_at' => 'date',
            
            // Status fields
            'conversation.status' => 'status',
            'thread.status' => 'status',
            'user.status' => 'status',
            
            // Long text fields
            'thread.body' => 'long-text',
            
            // People fields
            'conversation.assigned_to' => 'people',
        ];

        // Return mapped type or default to text
        return $typeMap[$fieldKey] ?? 'text';
    }
}
