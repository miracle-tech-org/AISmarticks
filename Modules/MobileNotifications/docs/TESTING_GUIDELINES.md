# Testing Guidelines for MobileNotifications Module

This document outlines key areas and specific tests that should be implemented to ensure the reliability and correctness of the MobileNotifications module.

## Unit Tests

### 1. `FirebaseNotificationService.php`

*   **`testFirebaseSdkInitializationSuccess`**:
    *   **Setup:** Mock `config()` to return valid paths/IDs. Mock `file_exists()` to return true. Mock `Firebase\Factory` to return a mock Firebase app and messaging client.
    *   **Action:** Instantiate `FirebaseNotificationService`.
    *   **Assert:** `isConfigured` flag is true. `Log::info` for successful initialization was called. No error logs.
*   **`testFirebaseSdkInitializationFailureBadPath`**:
    *   **Setup:** Mock `config()` but `file_exists()` returns false for credentials.
    *   **Action:** Instantiate `FirebaseNotificationService`.
    *   **Assert:** `isConfigured` flag is false. `Log::warning` for missing credentials file was called.
*   **`testFirebaseSdkInitializationFailureSdkException`**:
    *   **Setup:** Mock `config()` and `file_exists()`. Make `Firebase\Factory->create()` throw a `FirebaseException`.
    *   **Action:** Instantiate `FirebaseNotificationService`.
    *   **Assert:** `isConfigured` flag is false. `Log::error` for SDK exception was called.
*   **`testPrepareAndLogNotificationSendsSuccessfully`**:
    *   **Setup:** Instantiate service with mocked `messaging` client. Ensure `isConfigured` is true and module is enabled. Mock `DeviceToken::where()->get()` to return a collection with a mock device token. Mock `NotificationLog::create()`.
    *   **Action:** Call `prepareAndLogNotification()` with a user, title, body, data.
    *   **Assert:** `$this->messaging->send()` was called once with a correctly structured `CloudMessage`. `NotificationLog::create()` was called with status 'success' and correct payload.
*   **`testPrepareAndLogNotificationSendFailsWithMessagingException`**:
    *   **Setup:** Mock `messaging` client's `send()` method to throw `MessagingException`. Mock `NotificationLog::create()`.
    *   **Action:** Call `prepareAndLogNotification()`.
    *   **Assert:** `NotificationLog::create()` was called with status 'failure' and includes the exception message. `Log::error` for `MessagingException` was called.
*   **`testPrepareAndLogNotificationSendFailsWithFirebaseException`**:
    *   **Setup:** Mock `messaging` client's `send()` method to throw `FirebaseException`. Mock `NotificationLog::create()`.
    *   **Action:** Call `prepareAndLogNotification()`.
    *   **Assert:** `NotificationLog::create()` was called with status 'failure'. `Log::error` for `FirebaseException` was called.
*   **`testPrepareAndLogNotificationWhenServiceNotConfigured`**:
    *   **Setup:** Ensure `isConfigured` is false in the service. Mock `NotificationLog::create()`.
    *   **Action:** Call `prepareAndLogNotification()`.
    *   **Assert:** No attempt to send via `$this->messaging->send()`. `NotificationLog::create()` called with status 'service_not_configured'. `Log::warning` for skipping was called.
*   **`testPrepareAndLogNotificationWhenModuleDisabled`**:
    *   **Setup:** Ensure `isConfigured` is true but `config('mobilenotifications.enabled')` is false. Mock `NotificationLog::create()`.
    *   **Action:** Call `prepareAndLogNotification()`.
    *   **Assert:** No attempt to send. `NotificationLog::create()` called with status 'disabled_in_settings'. `Log::warning` for skipping was called.
*   **`testPrepareAndLogNotificationNoDeviceTokensForUser`**:
    *   **Setup:** Mock `DeviceToken::where()->get()` to return an empty collection. Mock `NotificationLog::create()`.
    *   **Action:** Call `prepareAndLogNotification()`.
    *   **Assert:** No attempt to send. `NotificationLog::create()` called with status 'no_tokens_found'.

### 2. Models (`DeviceToken.php`, `NotificationLog.php`)

*   **`testDeviceTokenUserRelationship`**:
    *   **Setup:** Create a User and a DeviceToken associated with that user.
    *   **Action:** Access `$deviceToken->user`.
    *   **Assert:** The correct User object is returned.
*   **`testNotificationLogUserRelationship`**:
    *   **Setup:** Create a User and a NotificationLog associated.
    *   **Action:** Access `$notificationLog->user`.
    *   **Assert:** The correct User object is returned.
*   **`testNotificationLogDeviceTokenRelationship`**:
    *   **Setup:** Create a DeviceToken and a NotificationLog associated.
    *   **Action:** Access `$notificationLog->deviceToken`.
    *   **Assert:** The correct DeviceToken object is returned.
*   **`AgentMobileNotificationPreference` Model (`AgentMobileNotificationPreference.php`)**:
    *   **`testUserRelationship`**: Test the `user()` relationship.
    *   **`testWantsNotificationStaticMethod`**:
        *   Test with a user who has the specific notification type explicitly enabled (`is_enabled = true`). Assert returns `true`.
        *   Test with a user who has the specific notification type explicitly disabled (`is_enabled = false`). Assert returns `false`.
        *   Test with a user who has no preference set for a specific notification type. Assert returns `true` (default behavior).

## Integration Tests (Laravel HTTP Tests or Full Event Dispatch)

### 1. Event Listeners

*   **`testNotifyAgentOnCustomerReplyListenerIntegration`**:
    *   **Setup:** Create a User (agent) with a registered DeviceToken. Create a Conversation assigned to this agent. Mock `FirebaseNotificationService` in the service container (or use a real one if testing actual sends with a test Firebase project).
    *   **Scenarios for Preference Checks:**
        *   Agent has the relevant notification type enabled: Dispatch event. Assert `FirebaseNotificationService->prepareAndLogNotification` IS called.
        *   Agent has the relevant notification type disabled: Dispatch event. Assert `FirebaseNotificationService->prepareAndLogNotification` IS NOT called.
        *   Agent has no preference set (default): Dispatch event. Assert `FirebaseNotificationService->prepareAndLogNotification` IS called.
    *   **Action:** Dispatch `App\Events\CustomerReplied` event with the conversation.
    *   **Assert:** Based on preference scenario, check if `FirebaseNotificationService->prepareAndLogNotification` was called appropriately. Check `notification_logs` table.
*   **`testNotifyAgentOnAssignmentListenerIntegration`**:
    *   **Setup:** Similar to above, create User, DeviceToken, Conversation. Mock or use real `FirebaseNotificationService`.
    *   **Scenarios for Preference Checks:** Test with preference enabled, disabled, and default.
    *   **Action:** Dispatch `App\Events\ConversationUserChanged` event.
    *   **Assert:** Based on preference scenario, check `FirebaseNotificationService` calls and `notification_logs`.

### 2. Controllers (HTTP Tests using Laravel's testing tools)

*   **`RegistrationController@register`**:
    *   Test successful registration with valid token and platform. Assert 200/201 response and database record.
    *   Test validation errors: missing token, missing platform, invalid platform value, token too long. Assert 422 response and error messages.
    *   Test rate limiting: Simulate multiple requests to exceed limit. Assert 429 response.
*   **`RegistrationController@delete`**:
    *   Test agent deleting their own device. Assert success and DB record removed.
    *   Test agent attempting to delete another user's device (should be blocked by policy or check if admin). This route is also used by admins, so context matters.
*   **`SettingsController@save` (Admin)**:
    *   Test saving valid settings (project ID, server name, etc.). Assert redirect and settings saved.
    *   Test file upload for Firebase credentials. Assert file is stored and path saved.
    *   Test validation errors for settings.
*   **`NotificationController@test` (Admin)**:
    *   Test "Send Test Notification" button functionality. Mock `FirebaseNotificationService->sendTestMessage`. Assert 200 JSON response.
*   **`DeviceManagementController@index` & `destroy` (Admin)**:
    *   Test listing all devices. Assert view is returned with devices.
    *   Test deleting a device. Assert redirect and device removed.
*   **`UserDeviceController@index` (Agent)**:
    *   Test authenticated agent viewing their own devices and preferences.
        *   Assert view is returned with correct devices.
        *   Assert preferences section shows correct labels and checkbox states (reflecting saved or default preferences).
    *   Test agent seeing no devices if none are registered (preferences section should still show).
    *   **`UserDeviceController@updatePreferences` (Agent)**:
        *   Test submitting the preferences form:
            *   Enable a previously disabled type.
            *   Disable a previously enabled type.
            *   Save with no changes.
            *   Uncheck all types.
            *   Check all types.
        *   Assert redirect back to the devices page with a success message.
        *   Assert that records in `agent_mobile_notification_preferences` table are correctly created/updated for the user.
*   **`NotificationLogController@index` (Admin)**:
    *   Test admin viewing notification logs. Assert view is returned with logs.

## General Considerations

*   **Authentication & Authorization:** Ensure all routes that require authentication (agent or admin) are protected. Test unauthorized access attempts.
*   **Database State:** Use database migrations and transactions (or `RefreshDatabase` trait) to ensure a clean state for each test.
*   **Mocking:** Mock external services like Firebase SDK extensively in unit tests and selectively in integration tests.
*   **Configuration:** Ensure tests can run with mock configuration values.
*   **Coverage:** Aim for good test coverage across critical parts of the module.

This outline should provide a solid foundation for developing a comprehensive test suite for the MobileNotifications module.
