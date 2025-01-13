<?php

namespace Modules\AIMiddleware\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AIMiddleware\Services\{ReportService, LoggerService};

/**
 * Controller for handling AI Assistant reports
 */
class ReportController extends Controller
{
    protected ReportService $reportService;
    protected LoggerService $logger;

    /**
     * Constructor
     */
    public function __construct(ReportService $reportService, LoggerService $logger)
    {
        $this->middleware(['auth', 'admin']);
        $this->reportService = $reportService;
        $this->logger = $logger;
    }

    /**
     * Show report page
     */
    public function index(Request $request)
    {
        try {
            // Initialize report with request parameters
            $report = $this->reportService->initReport($request->all());

            $this->logger->info('AI Assistant report viewed', [
                'filters' => $request->all(),
            ]);

            return view('report.ai_assistant', [
                'report' => $report,
                'filters' => $report->getFilters(),
                'tables' => $report->getTables()
            ]);

        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'action' => 'view_report',
                'filters' => $request->all()
            ]);

            \Helper::alert([
                'msg' => __('Error generating report'),
                'type' => 'error'
            ]);

            return redirect()->back();
        }
    }

    /**
     * Export report
     */
    public function export(Request $request)
    {
        try {
            // Initialize report
            $report = $this->reportService->initReport($request->all());
            
            // Get export format
            $format = $request->get('format', 'xlsx');

            $this->logger->info('AI Assistant report exported', [
                'filters' => $request->all(),
                'format' => $format
            ]);

            // Generate export file
            return $report->exportFile($format);

        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'action' => 'export_report',
                'filters' => $request->all()
            ]);

            \Helper::alert([
                'msg' => __('Error exporting report'),
                'type' => 'error'
            ]);

            return redirect()->back();
        }
    }
}
