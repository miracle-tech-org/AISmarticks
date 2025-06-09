<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Option; // Assuming App\Option model exists and has a static set/get method.

class MigrateMobilenotificationsSettingsToOptionsTable extends Migration
{
    protected $optionPrefix = 'mobilenotifications_';
    protected $legacyTableName = 'mobile_notification_settings';

    protected $settingsKeys = [
        'project_id',
        'server_name',
        'default_title',
        'enabled',
        'firebase_credentials' // Special handling for this one to become _path
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->legacyTableName)) {
            $legacySettings = DB::table($this->legacyTableName)->get();

            foreach ($legacySettings as $setting) {
                $newKey = $this->optionPrefix . $setting->key;
                $value = $setting->value;

                if ($setting->key === 'firebase_credentials') {
                    $newKey = $this->optionPrefix . 'firebase_credentials_path';
                }

                if (class_exists(Option::class) && method_exists(Option::class, 'set')) {
                    Option::set($newKey, $value);
                } else {
                    // Fallback if Option model or its 'set' method isn't available
                    DB::table('options')->updateOrInsert(['key' => $newKey], ['value' => $value]);
                }
            }

            Schema::dropIfExists($this->legacyTableName);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 1. Re-create the mobile_notification_settings table
        if (!Schema::hasTable($this->legacyTableName)) {
            Schema::create($this->legacyTableName, function (Blueprint $table) {
                $table->increments('id');
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        $optionKeysToDelete = [];

        // 2. Read settings from options table and insert into mobile_notification_settings
        foreach ($this->settingsKeys as $originalKey) {
            $newKey = $this->optionPrefix . $originalKey;
            if ($originalKey === 'firebase_credentials') {
                $newKey = $this->optionPrefix . 'firebase_credentials_path';
            }

            $optionValue = null;
            if (class_exists(Option::class) && method_exists(Option::class, 'get')) {
                $optionValue = Option::get($newKey);
            } else {
                $optionRow = DB::table('options')->where('key', $newKey)->first();
                if ($optionRow) {
                    $optionValue = $optionRow->value;
                }
            }

            if ($optionValue !== null) {
                DB::table($this->legacyTableName)->updateOrInsert(
                    ['key' => $originalKey],
                    ['value' => $optionValue, 'created_at' => now(), 'updated_at' => now()]
                );
                $optionKeysToDelete[] = $newKey;
            }
        }

        // 3. Delete the corresponding rows from the options table
        if (!empty($optionKeysToDelete)) {
            if (class_exists(Option::class) && method_exists(Option::class, 'forget')) {
                foreach($optionKeysToDelete as $keyToForget) {
                    Option::forget($keyToForget); // Assuming a forget method might exist
                }
            } else {
                 DB::table('options')->whereIn('key', $optionKeysToDelete)->delete();
            }
        }
    }
}
