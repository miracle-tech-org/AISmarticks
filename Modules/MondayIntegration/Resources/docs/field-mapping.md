# Field Mapping Guide

Learn how to map fields between FreeScout and Monday.com to ensure proper data synchronization.

## Understanding Field Mapping

Field mapping determines how information flows between FreeScout and Monday.com. Each mapping connects a FreeScout field to a Monday.com column.

## Available Fields

### FreeScout Fields

#### Conversation Fields
* `conversation.number` - Conversation ID
* `conversation.subject` - Email subject
* `conversation.status` - Current status
* `conversation.created_at` - Creation date
* `conversation.updated_at` - Last update date

#### Customer Fields
* `customer.first_name` - Customer's first name
* `customer.last_name` - Customer's last name
* `customer.email` - Customer's email address
* `customer.phone` - Customer's phone number
* `customer.company` - Customer's company name

#### Assignee Fields
* `assignee.first_name` - Agent's first name
* `assignee.last_name` - Agent's last name
* `assignee.email` - Agent's email address

#### Custom Fields
* `custom_fields.*` - Any custom fields you've defined

### Monday.com Columns

* Text columns
* Number columns
* Status columns
* Date columns
* People columns
* Timeline columns
* Tags columns

## Transform Rules

Transform rules modify data as it's synchronized:

* **Uppercase**: Convert text to uppercase
* **Lowercase**: Convert text to lowercase
* **Date Format**: Format dates consistently
* **Boolean**: Convert to Yes/No values
* **JSON**: Convert complex data to JSON

## Setting Up Mappings

1. **Access Field Mapping**
   * Go to Monday.com → Field Mappings
   * You'll see available fields on both sides

2. **Create Mappings**
   * Drag fields from FreeScout to Monday.com columns
   * Configure transform rules if needed
   * Set default values for required fields

3. **Configure Options**
   * Mark fields as required
   * Set default values
   * Choose transform rules

## Best Practices

### 1. Required Fields
* Always map essential fields
* Provide default values for required fields
* Verify field types match

### 2. Data Types
* Match appropriate field types
* Use transform rules for compatibility
* Test with sample data

### 3. Performance
* Map only necessary fields
* Avoid redundant mappings
* Consider sync frequency

### 4. Testing
* Test mappings with sample data
* Verify data accuracy
* Check both sync directions

## Common Scenarios

### Customer Information
```
FreeScout         Monday.com
first_name    →   Person Name
last_name     →   Person Name
email         →   Email
company       →   Company
```

### Conversation Details
```
FreeScout         Monday.com
subject       →   Item Name
status        →   Status
created_at    →   Date
```

### Custom Fields
```
FreeScout         Monday.com
priority      →   Priority
category      →   Type
due_date      →   Timeline
```

## Troubleshooting

### Common Issues

1. **Type Mismatch**
   * Ensure field types are compatible
   * Use appropriate transform rules
   * Check for data truncation

2. **Missing Data**
   * Verify required fields
   * Check default values
   * Confirm field permissions

3. **Sync Errors**
   * Check error logs
   * Verify field existence
   * Test transform rules
