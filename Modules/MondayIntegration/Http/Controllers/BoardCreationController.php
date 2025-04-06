<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\MondayIntegration\Helpers\Logger;
use Modules\MondayIntegration\Services\BoardCreationService;
use Modules\MondayIntegration\Services\MondayApiService;
use Modules\MondayIntegration\Services\SecurityService;
use App\Mailbox;

/**
 * Controller for board creation functionality
 */
class BoardCreationController extends Controller
{
    /**
     * @var BoardCreationService
     */
    protected $boardCreationService;

    /**
     * @var MondayApiService
     */
    protected $mondayApiService;

    /**
     * BoardCreationController constructor.
     *
     * @param BoardCreationService $boardCreationService
     * @param MondayApiService $mondayApiService
     */
    public function __construct(BoardCreationService $boardCreationService, MondayApiService $mondayApiService)
    {
        $this->boardCreationService = $boardCreationService;
        $this->mondayApiService = $mondayApiService;
    }

    /**
     * Display the board creation page
     *
     * @return \Illuminate\View\View
     */
    public function index($mailbox_id)
    {
        // Get available templates
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $templates = $this->boardCreationService->getAvailableTemplates();
        return view('mondayintegration::board_creation.index', [
            'templates' => $templates,
            'mailbox' => $mailbox
        ]);
    }

    /**
     * Create a new board
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createBoard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'board_name' => 'required|string|min:3|max:255',
            'board_kind' => 'required|string|in:public,private,share',
            'use_template' => 'sometimes|boolean',
            'template_id' => 'required_if:use_template,1|string',
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'string',
            'permission_level' => 'sometimes|string|in:view,edit,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid input parameters'),
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $boardName = $request->input('board_name');
        $boardKind = $request->input('board_kind', 'public');
        $useTemplate = $request->input('use_template', false);
        $templateId = $request->input('template_id');
        $userIds = $request->input('user_ids', []);
        $permissionLevel = $request->input('permission_level', 'view');

        try {
            // Create the board
            $board = null;
            
            if ($useTemplate && $templateId) {
                $board = $this->boardCreationService->createBoardFromTemplate($boardName, $templateId, $boardKind);
            } else {
                $board = $this->boardCreationService->createBoard($boardName, $boardKind);
            }

            if (!$board) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to create board')
                ], 500);
            }

            // Set permissions if user IDs are provided
            if (!empty($userIds)) {
                $this->boardCreationService->setBoardPermissions($board['id'], $userIds, $permissionLevel);
            }

            return response()->json([
                'success' => true,
                'message' => __('Board created successfully'),
                'board' => $board
            ]);
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error creating board', [
                'board_name' => $boardName,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Error creating board: ') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available board templates
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTemplates()
    {
        try {
            $templates = $this->boardCreationService->getAvailableTemplates();

            return response()->json([
                'success' => true,
                'templates' => $templates
            ]);
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error getting templates', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Error getting templates: ') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Monday.com users for permission assignment
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsers()
    {
        try {
            $users = $this->mondayApiService->getUsers();

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error getting users', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Error getting users: ') . $e->getMessage()
            ], 500);
        }
    }
}
