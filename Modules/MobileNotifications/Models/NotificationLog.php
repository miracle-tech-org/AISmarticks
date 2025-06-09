<?php

namespace Modules\MobileNotifications\Models;

use Illuminate\Database\Eloquent\Model;
use App\User; // Assuming App\User is your user model
use Modules\MobileNotifications\Models\DeviceToken;

class NotificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'device_token_id',
        'status',
        'message_payload',
        'response',
        'sent_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sent_at' => 'datetime',
        'message_payload' => 'array', // Assuming you store payload as JSON and want it cast to array
    ];

    /**
     * Get the user associated with the notification log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the device token associated with the notification log.
     */
    public function deviceToken()
    {
        return $this->belongsTo(DeviceToken::class);
    }
}
