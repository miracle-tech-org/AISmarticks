<?php

// Defines the available notification types that users can manage.
// The key is used programmatically (e.g., in the database),
// and the value is a human-readable label for use in GUIs.
// Descriptions and default enabled statuses can be managed by controller/view logic
// when displaying preferences, potentially using these labels or separate lang files.

return [
    'customer_replied' => 'New Customer Reply',
    'conversation_assigned' => 'Conversation Assigned to You',
    // Add future notification types here, for example:
    // 'mention_in_note' => 'Mentioned in a Note',
    // 'new_internal_message' => 'New Internal Message',
];
