<?php

namespace Modules\TelegramIntegration\Http\Controllers;

use App\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\AIAssistant\Jobs\ProcessAIResponse;
use Modules\AIAssistant\Models\Ticket;

class TelegramIntegrationController extends Controller
{
    public function webhooks(Request $request, $mailbox_id, $mailbox_secret)
    {
        if (class_exists('Debugbar')) {
            \Debugbar::disable();
        }

        $mailbox = Mailbox::find($mailbox_id);

        if (
            !$mailbox || \TelegramIntegration::getMailboxSecret($mailbox_id) != $request->mailbox_secret
        ) {
            \TelegramIntegration::log('Incorrect webhook URL: ' . url()->current(), $mailbox ?? null, true);
            abort(404);
        }

        $botman = \TelegramIntegration::getBotman($mailbox, $request, true);

        if (!$botman) {
            abort(404);
        }

        $botman->hears('(.*)', function ($bot, $text) use ($mailbox, $request) {
            // Text is obtained from the payload to prevent removal of newlines
            // in the message text.
            $data = $bot->getMessage()->getPayload();
            $text = $data['text'] ?? $text;

            \TelegramIntegration::processIncomingMessage($bot, $text, $mailbox);

            if(\Module::isActive('aiassistant')) {
                $tgMessage = json_decode(json_encode($request->all()));
                $ticket = new Ticket;
                $ticket->message = $tgMessage->message->text;
                $job = new ProcessAIResponse($ticket);
                $job->execute();
            }
        });

        $botman->receivesFiles(function ($bot, $files) use ($mailbox) {

            \TelegramIntegration::processIncomingMessage($bot, __('File(s)'), $mailbox, $files);

            // foreach ($files as $file) {

            //     $url = $file->getUrl(); // The direct url
            //     $payload = $file->getPayload(); // The original payload
            // }
        });

        $botman->receivesImages(function ($bot, $images) use ($mailbox) {

            \TelegramIntegration::processIncomingMessage($bot, __('Image(s)'), $mailbox, $images);

            // foreach ($images as $image) {

            //     $url = $image->getUrl(); // The direct url
            //     $title = $image->getTitle(); // The title, if available
            //     $payload = $image->getPayload(); // The original payload
            // }
        });

        $botman->receivesVideos(function ($bot, $videos) use ($mailbox) {

            \TelegramIntegration::processIncomingMessage($bot, __('Video(s)'), $mailbox, $videos);
            // foreach ($videos as $video) {

            //     $url = $video->getUrl(); // The direct url
            //     $payload = $video->getPayload(); // The original payload
            // }
        });

        $botman->receivesAudio(function ($bot, $audios) use ($mailbox) {

            \TelegramIntegration::processIncomingMessage($bot, __('Audio'), $mailbox, $audios);
            // foreach ($audios as $audio) {

            //     $url = $audio->getUrl(); // The direct url
            //     $payload = $audio->getPayload(); // The original payload
            // }
        });

        $botman->receivesLocation(function ($bot, $location) use ($mailbox) {

            \TelegramIntegration::processIncomingMessage($bot, __('Location: ' . $location->getLatitude() . ',' . $location->getLongitude()), $mailbox);
            // $lat = $location->getLatitude();
            // $lng = $location->getLongitude();
        });

        $botman->listen();
    }

    /**
     * Settings.
     */
    public function settings($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        if (!auth()->user()->isAdmin()) {
            \Helper::denyAccess();
        }

        $settings = $mailbox->meta[\TelegramIntegration::DRIVER] ?? [];

        return view('telegramintegration::settings', [
            'mailbox'   => $mailbox,
            'settings'   => $settings,
        ]);
    }

    /**
     * Settings save.
     */
    public function settingsSave(Request $request, $mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $settings = $request->settings;

        $webhooks_enabled = (int)($mailbox->meta[\TelegramIntegration::DRIVER]['enabled'] ?? 0);

        $settings['enabled'] = (int)($settings['enabled'] ?? 0);

        // Try to add a webhook.
        if (!$webhooks_enabled && (int)$settings['enabled']) {
            $result = \TelegramIntegration::setWebhook($settings['token'] ?? '', $mailbox->id);

            if (!$result['result']) {
                $settings['enabled'] = false;
                \Session::flash('flash_error', __('Error occurred setting up a Telegram webhook') . ': ' . $result['msg']);
            }
        }
        // Remove webhook.
        if ($webhooks_enabled && !(int)$settings['enabled']) {
            $result = \TelegramIntegration::setWebhook($settings['token'] ?? '', $mailbox->id, true);

            if (!$result['result'] && !strstr($result['msg'], 'Unauthorized')) {
                $settings['enabled'] = true;
                \Session::flash('flash_error', __('Error occurred removing a Telegram webhook') . ': ' . $result['msg']);
            }
        }

        $mailbox->setMetaParam(\TelegramIntegration::DRIVER, $settings);
        $mailbox->save();

        \Session::flash('flash_success_floating', __('Settings updated'));

        return redirect()->route('mailboxes.telegram.settings', ['mailbox_id' => $mailbox_id]);
    }
}
