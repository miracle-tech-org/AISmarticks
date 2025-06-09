# Mobile Notifications Module for FreeScout

This module integrates Firebase Cloud Messaging (FCM) to enable mobile push notifications for agents in FreeScout. It provides features for administrators to manage registered devices and monitor notification logs, and for agents to manage their own devices.

## Features

### Admin Features
*   **Firebase Configuration:** Set up Firebase credentials (service account JSON) and Project ID via *Settings → Mobile Notifications*.
*   **Test Notification:** Send a test notification to the currently logged-in admin user from the settings page to verify setup (simulates sending if not fully configured or if SDK issues).
*   **Device Management:** View a list of all registered mobile devices across all users at *Mobile Notifications → Registered Devices*. Admins can remove any registered device.
*   **Notification Logs:** View a log of all notification attempts (prepared, sent, failed, no tokens, etc.) at *Mobile Notifications → Notification Logs*. This helps in monitoring and troubleshooting.
*   **Rate Limiting:** The device registration endpoint is rate-limited to prevent abuse.

### Agent Features
*   **Device Registration:** Agents receive a signed link (sent by an admin from their user profile's "Mobile Registration" section) to register their mobile device. The registration page allows selecting the platform (Android, iOS, Web) and auto-detects it.
*   **Manage Own Devices:** Agents can view and remove their own registered mobile devices via a dedicated page in their user profile area ("My Mobile Devices").
*   **Notification Preferences:** On the "My Mobile Devices" page, agents can also manage their preferences for different types of notifications. They can enable or disable specific categories (e.g., "New Customer Reply," "Conversation Assigned to You"). By default, all notification types are enabled.

### Notification Triggers
The module currently sends notifications for the following events (configurable by agents):
*   **New Customer Reply:** Notifies the assigned agent when a customer replies to a conversation.
*   **Conversation Assigned to Agent:** Notifies an agent when a conversation is assigned to them.
*(More triggers can be added in the future.)*

## Installation

1.  Copy the `MobileNotifications` module directory into your FreeScout `Modules/` directory.
2.  **Dependency Installation:**
    *   This module requires `kreait/firebase-php` (version 5.x, 6.x or 7.x). Add it to your main FreeScout `composer.json` or ensure it's installed via the module's `composer.json`.
        ```json
        // In your root composer.json
        "require": {
            // ... other requirements
            "kreait/firebase-php": "^5.0 || ^6.0 || ^7.0"
        }
        ```
    *   Then run `composer update kreait/firebase-php` from your FreeScout root directory.
    *   **Important Note on Dependencies:** The `kreait/firebase-php` library is modern and requires a compatible PHP version (typically PHP 7.2+ for v5, higher for v6/v7) and dependencies. If your FreeScout installation runs on an older PHP version or has conflicting older package versions locked in `composer.lock`, you might encounter issues installing this dependency. Resolving such conflicts may require updating other parts of your FreeScout environment or finding a compatible older version of `kreait/firebase-php` if available (though v4 and below have a different API).
3.  Run `php artisan module:enable MobileNotifications` (or activate via the GUI if that's how your FreeScout handles it).
4.  Run database migrations: `php artisan migrate`. This will create the necessary tables (`device_tokens`, `notification_logs`, `agent_mobile_notification_preferences`).

## Configuration

1.  Navigate to **Settings → Mobile Notifications** in the FreeScout admin area.
2.  **Project ID:** Enter your Firebase Project ID.
3.  **Firebase Credentials:** Upload your Firebase Admin SDK service account JSON file. This file is securely stored in the `storage/app/mobilenotifications` directory, which should not be publicly accessible.
4.  **Enable Push Notifications:** Toggle the switch to enable or disable sending notifications.
5.  Save the settings.

## Device Registration Process for Agents

1.  **Admin Action:** An administrator navigates to a user's profile in FreeScout, goes to the "Mobile Registration" section (this tab/section would be part of the core user profile, extended by this module conceptually).
2.  The admin clicks a "Send Registration Link" button. This emails a unique, signed, and short-lived registration link to the agent.
3.  **Agent Action:** The agent opens this link on the mobile device they wish to register.
4.  The page allows them to submit their FCM device token. The platform (Android, iOS, Web) can be selected, with an attempt to auto-detect.
5.  Upon successful registration, the device will appear in their "My Registered Mobile Devices" list in their profile and in the admin's central device management list.

## Troubleshooting

*   **Dependency Installation Issues:** See the "Important Note on Dependencies" under Installation. Compatibility issues with older PHP versions or conflicting packages in your main FreeScout `composer.lock` file are the most common cause.
*   **Notifications Not Sending:**
    *   Check that "Enable Push Notifications" is active in settings.
    *   Verify `storage/logs/laravel.log` for any errors from the `FirebaseNotificationService` during initialization or sending.
    *   Check the "Notification Logs" in the admin area for specific error messages related to individual sends (e.g., 'failure', 'no_tokens_found', 'service_not_configured', or if skipped due to agent preference).
    *   Ensure the uploaded Firebase service account JSON is valid and has the necessary permissions for FCM.
    *   Ensure the Firebase Project ID is correctly entered.
    *   Confirm the device has an active internet connection and has granted notification permissions to the (future) client app.

## For Developers

*   **Events Handled:** `App\Events\CustomerReplied`, `App\Events\ConversationUserChanged`.
*   **Listeners:** Located in `Modules/MobileNotifications/Listeners/`. These listeners now check agent preferences before sending notifications.
*   **Core Service:** `Modules/MobileNotifications/Services/FirebaseNotificationService.php` handles Firebase interaction and logging.
*   **Notification Types Configuration:** Available notification types are defined in `Modules/MobileNotifications/Config/notification_types.php`. This file can be extended to add new configurable notification types.
*   **Testing:** See `Modules/MobileNotifications/docs/TESTING_GUIDELINES.md` for an outline of recommended tests.
