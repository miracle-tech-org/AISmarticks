# Getting Started with Monday.com Integration

Welcome to the Monday.com integration for FreeScout! This guide will help you set up and configure the integration between your FreeScout helpdesk and Monday.com workspace.

## Prerequisites

Before you begin, make sure you have:

1. A Monday.com account with admin access
2. Your Monday.com API token (v2 API)
3. Admin access to your FreeScout installation

## Quick Start Guide

### Step 1: Get Your Monday.com API Token

1. Log in to your Monday.com account
2. Go to your avatar → Admin → API
3. Generate a new API token with the following permissions:
   * Boards:Read
   * Items:Read & Write
   * Updates:Read & Write
   * Webhooks:Read & Write

### Step 2: Configure the Integration

1. In FreeScout, go to Monday.com → Settings
2. Enter your API token
3. Select the board you want to sync with
4. Configure sync settings:
   * Sync frequency
   * Sync direction
   * Error notifications

### Step 3: Set Up Field Mappings

1. Go to Monday.com → Field Mappings
2. Map FreeScout fields to Monday.com columns
3. Configure any necessary field transformations
4. Save your mappings

### Step 4: Configure Webhooks

1. Go to Monday.com → Webhooks
2. Add webhooks for the events you want to track
3. Test the webhook connections

## What's Next?

* Read the [Configuration Guide](configuration) for detailed settings information
* Learn about [Field Mapping](field-mapping) options
* Set up [Webhooks](webhooks) for real-time updates
* Check the [Troubleshooting Guide](troubleshooting) if you encounter any issues

## Need Help?

If you need assistance:

1. Check our [Troubleshooting Guide](troubleshooting)
2. Contact your system administrator
3. Visit the FreeScout community forums
