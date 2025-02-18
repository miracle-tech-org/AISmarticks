# Monday.com Integration Configuration Guide

This guide provides detailed information about configuring the Monday.com integration for FreeScout.

## API Configuration

### API Token
* Required for connecting to Monday.com
* Must have appropriate permissions:
  * Boards:Read
  * Items:Read & Write
  * Updates:Read & Write
  * Webhooks:Read & Write
* Token is encrypted before storage
* Can be rotated at any time

### Board Selection
* Choose which Monday.com board to sync with
* Only one board can be active at a time
* Board must have appropriate columns for mapping

## Sync Settings

### Sync Frequency
* Every 5 minutes (recommended)
* Every 15 minutes
* Every 30 minutes
* Every hour

### Sync Direction
* **Bidirectional**: Changes sync both ways
* **FreeScout to Monday.com**: Only push changes to Monday.com
* **Monday.com to FreeScout**: Only pull changes from Monday.com

### Attachment Sync
* Enable/disable file attachment sync
* Supported file types:
  * Images (jpg, png, gif)
  * Documents (pdf, doc, docx)
  * Archives (zip, rar)
* Maximum file size: 10MB

## Notification Settings

### Error Notifications
* **Disabled**: No notifications
* **Immediate**: Get notified as soon as errors occur
* **Daily Summary**: Receive a daily digest of errors

### Notification Email
* Where to send error notifications
* Can be different from system email
* Multiple emails supported (comma-separated)

## Advanced Settings

### API Rate Limit
* Default: 100 requests per minute
* Adjustable based on your Monday.com plan
* Higher limits available for Enterprise plans

### Retry Attempts
* Number of times to retry failed operations
* Default: 3 attempts
* Exponential backoff between retries

## Security Considerations

### Data Encryption
* API tokens are encrypted at rest
* Webhook secrets are encrypted
* Uses industry-standard encryption

### Access Control
* Only admin users can access settings
* Audit logging of configuration changes
* IP-based access restrictions available

## Best Practices

1. **API Token Security**
   * Rotate tokens regularly
   * Use minimum required permissions
   * Never share tokens

2. **Sync Configuration**
   * Start with smaller sync intervals
   * Monitor system performance
   * Adjust based on volume

3. **Error Handling**
   * Configure error notifications
   * Monitor sync logs regularly
   * Address errors promptly

4. **Performance Optimization**
   * Use selective field mapping
   * Enable only necessary webhooks
   * Configure appropriate batch sizes
