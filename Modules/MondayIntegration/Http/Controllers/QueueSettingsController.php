<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MondayIntegration\Services\SecurityService;
use Modules\MondayIntegration\Services\QueueService;
use App\Mailbox;

class QueueSettingsController extends Controller
{
    protected $securityService;
    protected $queueService;

    public function __construct(SecurityService $securityService, QueueService $queueService)
    {
        $this->securityService = $securityService;
        $this->queueService = $queueService;
    }

    public function index($mailbox_id)
    {
        $settings = [
            'high_load_threshold' => $this->securityService->getOption('queue_high_load_threshold', 50),
            'job_timeout' => $this->securityService->getOption('queue_job_timeout', 300),
            'max_retries' => $this->securityService->getOption('queue_max_retries', 3),
            'retry_delay' => $this->securityService->getOption('queue_retry_delay', 5),
        ];
        $queueMetrics = $this->queueService->getAllQueueMetrics();
        if (!array_key_exists('failed', $queueMetrics)) {
            $queueMetrics['failed'] = 0; // or any default value
        }
        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        return view('mondayintegration::settings.queue', compact('settings', 'queueMetrics', 'mailbox'));
    }

    public function save(Request $request)
    {
        $this->securityService->setOption('queue_high_load_threshold', $request->input('high_load_threshold'));
        $this->securityService->setOption('queue_job_timeout', $request->input('job_timeout'));
        $this->securityService->setOption('queue_max_retries', $request->input('max_retries'));
        $this->securityService->setOption('queue_retry_delay', $request->input('retry_delay'));
        return redirect()->back();
    }
}
