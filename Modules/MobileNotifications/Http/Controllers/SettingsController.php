<?php

namespace Modules\MobileNotifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Option; // Assuming App\Option model
use Illuminate\Support\Facades\Storage; // Keep for potential future use if direct storage interaction is needed

class SettingsController extends Controller
{
    protected $optionPrefix = 'mobilenotifications_';

    public function index()
    {
        // Default values can be provided from the module's config file if an Option entry doesn't exist yet.
        $defaultModuleConfig = config('mobilenotifications');

        $settings = [
            'project_id' => Option::get($this->optionPrefix . 'project_id', $defaultModuleConfig['project_id'] ?? ''),
            'server_name' => Option::get($this->optionPrefix . 'server_name', $defaultModuleConfig['server_name'] ?? ''),
            'default_title' => Option::get($this->optionPrefix . 'default_title', $defaultModuleConfig['default_title'] ?? ''),
            'enabled' => (bool) Option::get($this->optionPrefix . 'enabled', $defaultModuleConfig['enabled'] ?? false),
            'firebase_credentials_path' => Option::get($this->optionPrefix . 'firebase_credentials_path', $defaultModuleConfig['firebase_credentials_path'] ?? null),
        ];
        // Pass settings as individual variables or as an array, matching the view's expectation.
        // For this refactor, we'll pass them as an array similar to before to minimize view changes initially.
        return view('mobilenotifications::settings', ['settings' => $settings]);
    }

    public function save(Request $request)
    {
        // The 'enabled' field might not be present if checkbox is unchecked, handle default
        $validatedData = $request->validate([
            'project_id' => 'required|string|max:255',
            'server_name' => 'required|string|max:255',
            'default_title' => 'nullable|string|max:255',
            // 'enabled' is not a boolean in HTML forms, handle it separately
            'firebase_credentials' => 'nullable|file|mimes:json', // Validate file type
        ]);

        Option::set($this->optionPrefix . 'project_id', $validatedData['project_id']);
        Option::set($this->optionPrefix . 'server_name', $validatedData['server_name']);
        Option::set($this->optionPrefix . 'default_title', $validatedData['default_title'] ?? '');

        // Handle 'enabled' checkbox
        $enabled = $request->has('enabled') ? true : false;
        Option::set($this->optionPrefix . 'enabled', $enabled);

        if ($request->hasFile('firebase_credentials')) {
            // The store() method saves the file in 'storage/app/mobilenotifications'
            // and returns the relative path, e.g., 'mobilenotifications/filename.json'.
            $path = $request->file('firebase_credentials')->store('mobilenotifications');
            Option::set($this->optionPrefix . 'firebase_credentials_path', $path);
        }
        // Note: Storing files in 'storage/app' is generally secure.
        // This directory is not publicly accessible and is suitable for sensitive files like Firebase credentials.
        // Ensure your web server (e.g., Nginx, Apache) is configured to not serve files from the storage/app directory.

        // It's good practice to clear relevant cache if settings are cached by the Option model or config system
        // For example, if Option::set doesn't handle this: Cache::forget('some_options_cache_key');

        return redirect()->back()->with('status', __('Settings saved'));
    }
}
