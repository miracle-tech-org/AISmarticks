<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class LicenseLimit extends Model
{
    protected $fillable = ['email', 'mailbox', 'max_admin', 'max_user', 'workflow', 'sms_out_quota', 'whatsapp_out_quota', 'db_size_quota', 'block_over_quota_whatsapp', 'block_over_quota_sms'];
    public $timestamps = false;

    public static function getLimits()
    {
        $limits = self::where(['id' => 1])->first();
        if (empty($limits))
		{
			LicenseLimit::updateOrCreate(['id' => 1], ['email' => '', 'mailbox' => 0, 'max_admin' => 0, 'max_user' => 0, 'workflow' => 0]);
			$limits = self::where(['id' => 1])->first();
        }
		return $limits;
    }
}