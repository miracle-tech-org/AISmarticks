<?php

namespace Modules\MobileNotifications\Models;

use Illuminate\Database\Eloquent\Model;
use App\User; // Assuming App\User is your user model

class AgentMobileNotificationPreference extends Model
{
    protected $table = 'agent_mobile_notification_preferences';

    protected $fillable = [
        'user_id',
        'notification_type',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the user that owns these preferences.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a user wants a specific type of mobile notification.
     * Defaults to true if no preference is explicitly set.
     *
     * @param int $userId
     * @param string $notificationTypeKey
     * @return bool
     */
    public static function wantsNotification(int $userId, string $notificationTypeKey): bool
    {
        $preference = self::where('user_id', $userId)
                            ->where('notification_type', $notificationTypeKey)
                            ->first();

        if ($preference) {
            return $preference->is_enabled;
        }

        // Default to enabled if no specific preference is found.
        return true;
    }
}
