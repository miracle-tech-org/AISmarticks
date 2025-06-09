<?php

namespace Modules\MobileNotifications\Services;

use Modules\MobileNotifications\Models\DeviceToken;
use Modules\MobileNotifications\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use App\User;
use Kreait\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;

class FirebaseNotificationService
{
    protected $messaging;
    protected $isConfigured = false;

    public function __construct()
    {
        // The service provider now ensures 'mobilenotifications.firebase_credentials_path' is an absolute path.
        $actualCredentialsPath = config('mobilenotifications.firebase_credentials_path');
        $projectId = config('mobilenotifications.project_id');

        if (empty($actualCredentialsPath)) {
            Log::warning('Firebase credentials path configuration (mobilenotifications.firebase_credentials_path) is empty in the runtime config. Mobile notifications will not be sent.');
            $this->isConfigured = false;
            return;
        }

        if (file_exists($actualCredentialsPath) && !empty($projectId)) {
            try {
                $firebase = (new Firebase\Factory)
                    ->withServiceAccount($actualCredentialsPath)
                    // ->withProjectId($projectId) // Optional: often included in service account file
                    ->create();
                $this->messaging = $firebase->getMessaging();
                $this->isConfigured = true;
                Log::info('Firebase Admin SDK initialized successfully using credentials path: ' . $actualCredentialsPath);
            } catch (FirebaseException $e) {
                Log::error('Firebase Admin SDK Initialization Error for path (' . $actualCredentialsPath . '): ' . $e->getMessage());
                $this->isConfigured = false;
            } catch (\InvalidArgumentException $e) { // Catch potential issues with service account path/content
                Log::error('Firebase Admin SDK Initialization Error (InvalidArgumentException) for path (' . $actualCredentialsPath . '): ' . $e->getMessage());
                $this->isConfigured = false;
            }
        } else {
            $logMessage = 'Firebase notifications cannot be configured: ';
            if (empty($projectId)) {
                $logMessage .= 'Project ID is empty. ';
            }
            if (!file_exists($actualCredentialsPath)) {
                $logMessage .= 'Credentials file not found at resolved path: ' . $actualCredentialsPath . '. ';
            }
            Log::warning($logMessage . 'Mobile notifications will not be sent.');
            $this->isConfigured = false;
        }
    }

    /**
     * Prepares, sends, and logs a notification for a user.
     *
     * @param User $user The user to notify.
     * @param string $title The title of the notification.
     * @param string $body The body of the notification.
     * @param array $dataPayload Custom data to include in the notification.
     * @return void
     */
    public function prepareAndLogNotification(User $user, string $title, string $body, array $dataPayload = [])
    {
        if (!$this->isConfigured || !config('mobilenotifications.enabled')) {
            $logReason = !$this->isConfigured ? 'Firebase not configured.' : 'Mobile notifications disabled.';
            Log::warning("Skipping notification send: {$logReason} User: {$user->id}, Title: {$title}");
            // Optionally log to NotificationLog table as 'skipped' or 'disabled'
            NotificationLog::create([
                'user_id' => $user->id,
                'device_token_id' => null,
                'status' => $this->isConfigured ? 'disabled_in_settings' : 'service_not_configured',
                'message_payload' => json_encode(['notification' => ['title' => $title, 'body' => $body], 'data' => $dataPayload]),
                'response' => $logReason,
                'sent_at' => now(),
            ]);
            return;
        }

        $deviceTokens = DeviceToken::where('user_id', $user->id)->get();

        if ($deviceTokens->isEmpty()) {
            Log::info("No active device tokens found for user {$user->id} for notification titled '{$title}'.");
            NotificationLog::create([
                'user_id' => $user->id,
                'device_token_id' => null,
                'status' => 'no_tokens_found',
                'message_payload' => json_encode(['notification' => ['title' => $title, 'body' => $body], 'data' => $dataPayload]),
                'response' => 'No active device tokens found for user.',
                'sent_at' => now(),
            ]);
            return;
        }

        foreach ($deviceTokens as $deviceToken) {
            $message = CloudMessage::withTarget('token', $deviceToken->token)
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData($dataPayload);

            $status = 'unknown_failure'; // Default status
            $responseMessage = 'Notification sending not attempted due to prior error or configuration issue.';

            try {
                $this->messaging->send($message);
                $status = 'success';
                $responseMessage = 'Sent successfully.';
                Log::info("Firebase notification sent to token: {$deviceToken->token} for user {$user->id}");

            } catch (MessagingException $e) {
                $status = 'failure';
                $responseMessage = 'Failed to send: ' . $e->getMessage();
                Log::error("Firebase MessagingException for token {$deviceToken->token}: " . $e->getMessage());
            } catch (FirebaseException $e) {
                $status = 'failure';
                $responseMessage = 'Firebase SDK error: ' . $e->getMessage();
                Log::error("Firebase SDK Exception for token {$deviceToken->token}: " . $e->getMessage());
            }

            NotificationLog::create([
                'user_id' => $user->id,
                'device_token_id' => $deviceToken->id,
                'status' => $status,
                'message_payload' => json_encode($message->jsonSerialize()), // Log the actual sent payload
                'response' => $responseMessage,
                'sent_at' => now(),
            ]);
        }
    }

    /**
     * Sends a test message using the prepareAndLogNotification method.
     *
     * @param User $user
     * @return void
     */
    public function sendTestMessage(User $user)
    {
        $title = 'Test Notification';
        $body = 'This is a test notification from the application.';
        $dataPayload = ['type' => 'test_message'];

        // Call the main preparation and logging function
        $this->prepareAndLogNotification($user, $title, $body, $dataPayload);

        // The prepareAndLogNotification already logs 'prepared_for_send' or 'no_tokens_found'.
        // For sendTestMessage, we can add a summary log or rely on the detailed logs from prepareAndLogNotification.
        // If we want a specific 'simulated_success/failure' for the *test action itself*:
        $deviceTokens = DeviceToken::where('user_id', $user->id)->get();
        if ($deviceTokens->isEmpty()) {
            Log::info("Simulated test message for user {$user->id}: Failed (no tokens).");
            // NotificationLog for 'simulated_failure' is already created by prepareAndLogNotification if no tokens.
            // Or, if prepareAndLogNotification didn't log for the overall test *action*, log here.
            // For simplicity, we assume prepareAndLogNotification's logs are sufficient.
        } else {
            Log::info("Simulated test message for user {$user->id}: Success (payloads prepared).");
            // Optionally, add a specific log for the 'test action' itself being successful in *preparing* messages.
            // Example:
            // NotificationLog::create([
            //     'user_id' => $user->id,
            //     'status' => 'simulated_test_action_success',
            //     'message_payload' => json_encode(['notification' => ['title' => $title, 'body' => $body], 'data' => $dataPayload]),
            //     'response' => 'Test message preparation initiated for all user tokens.',
            //     'sent_at' => now(),
            // ]);
        }
    }
}
