# Webhooks Guide

Learn how to set up and manage webhooks for real-time synchronization between FreeScout and Monday.com.

## Understanding Webhooks

Webhooks enable real-time updates by notifying FreeScout when changes occur in Monday.com. This ensures your data stays synchronized without waiting for the next scheduled sync.

## Available Event Types

### Item Events
* **Item Created**: When a new item is added
* **Item Updated**: When any item field changes
* **Item Deleted**: When an item is removed

### Status Events
* **Status Changed**: When an item's status changes
* **Status Changed to Specific**: When status changes to a particular value

### Update Events
* **Update Added**: When a new update is posted
* **Update Changed**: When an update is edited
* **Update Deleted**: When an update is removed

## Setting Up Webhooks

### 1. Access Webhook Settings
* Go to Monday.com → Webhooks
* Click "Add Webhook"

### 2. Configure Webhook
* Select the board
* Choose event type
* Save the webhook

### 3. Test Connection
* Use the "Test" button
* Check webhook logs
* Verify event processing

## Security

### Webhook Authentication
* Each webhook has a unique secret
* Signatures are verified
* Failed verification triggers alerts

### Best Practices
* Rotate webhook secrets regularly
* Monitor failed authentications
* Limit webhook access

## Managing Webhooks

### Monitoring
* View recent events
* Check error rates
* Monitor response times

### Troubleshooting
* View detailed logs
* Check event payload
* Verify endpoint availability

### Maintenance
* Disable unused webhooks
* Update webhook configurations
* Clean up old webhooks

## Common Use Cases

### 1. Status Sync
```json
{
  "event": "status_changed",
  "boardId": "12345",
  "itemId": "67890",
  "value": {
    "from": "In Progress",
    "to": "Done"
  }
}
```

### 2. New Item Creation
```json
{
  "event": "item_created",
  "boardId": "12345",
  "itemId": "67890",
  "item": {
    "name": "New Support Ticket",
    "column_values": [...]
  }
}
```

### 3. Update Notifications
```json
{
  "event": "update_added",
  "boardId": "12345",
  "itemId": "67890",
  "update": {
    "text": "Customer response received",
    "creator": {
      "id": "12345",
      "name": "John Doe"
    }
  }
}
```

## Error Handling

### Common Issues

1. **Authentication Failures**
   * Check webhook secret
   * Verify signature calculation
   * Update webhook configuration

2. **Delivery Failures**
   * Check network connectivity
   * Verify endpoint availability
   * Monitor timeout settings

3. **Processing Errors**
   * Check payload format
   * Verify event handler logic
   * Monitor error logs

### Recovery Procedures

1. **Automatic Retry**
   * Failed deliveries are retried
   * Exponential backoff
   * Maximum retry attempts

2. **Manual Intervention**
   * Review error logs
   * Resend failed webhooks
   * Update configurations

3. **Monitoring**
   * Set up alerts
   * Monitor error rates
   * Track response times

## Best Practices

### 1. Implementation
* Start with critical events
* Add events gradually
* Test thoroughly

### 2. Monitoring
* Set up logging
* Monitor performance
* Track error rates

### 3. Maintenance
* Regular cleanup
* Update configurations
* Rotate secrets

### 4. Security
* Use HTTPS endpoints
* Verify signatures
* Monitor access
