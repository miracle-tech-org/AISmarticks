<?php

namespace Modules\MondayIntegration\Services;

use Illuminate\Support\Facades\Log;
use Modules\MondayIntegration\Helpers\Logger;

/**
 * Service for automated board creation in Monday.com
 */
class BoardCreationService
{
    /**
     * @var MondayApiService
     */
    protected $apiService;

    /**
     * @var array
     */
    protected $defaultColumnTypes = [
        'text', 'email', 'phone', 'status', 'date', 'people', 'dropdown'
    ];

    /**
     * BoardCreationService constructor.
     *
     * @param MondayApiService $apiService
     */
    public function __construct(MondayApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Create a new board with standard columns
     *
     * @param string $boardName Name of the board to create
     * @param string $boardKind Type of board (public/private/share)
     * @param array $standardColumns Array of standard columns to create
     * @return array|null The created board data or null on failure
     */
    public function createBoard($boardName, $boardKind = 'public', $standardColumns = [])
    {
        try {
            Logger::info('MondayIntegration: Creating new board', [
                'board_name' => $boardName,
                'board_kind' => $boardKind
            ]);

            // Create the board
            $board = $this->apiService->createBoard($boardName, $boardKind);

            if (!$board || !isset($board['id'])) {
                Logger::error('MondayIntegration: Failed to create board', [
                    'board_name' => $boardName
                ]);
                return null;
            }

            $boardId = $board['id'];

            // Add standard columns if provided, otherwise use defaults
            $columnsToCreate = !empty($standardColumns) ? $standardColumns : $this->getDefaultColumns();

            foreach ($columnsToCreate as $column) {
                $this->createColumn($boardId, $column['title'], $column['type'], $column['settings'] ?? null);
            }

            // Get the updated board with columns
            $updatedBoard = $this->apiService->getBoardById($boardId);

            Logger::info('MondayIntegration: Successfully created board with columns', [
                'board_id' => $boardId,
                'board_name' => $boardName,
                'columns_count' => count($columnsToCreate)
            ]);

            return $updatedBoard;
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error creating board', [
                'board_name' => $boardName,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Create a column in a board
     *
     * @param string $boardId ID of the board
     * @param string $title Column title
     * @param string $columnType Column type
     * @param array|null $settings Additional column settings
     * @return array|null The created column data or null on failure
     */
    public function createColumn($boardId, $title, $columnType, $settings = null)
    {
        try {
            // Validate column type
            if (!in_array($columnType, $this->defaultColumnTypes)) {
                Logger::warning('MondayIntegration: Invalid column type', [
                    'column_type' => $columnType,
                    'allowed_types' => implode(', ', $this->defaultColumnTypes)
                ]);
                return null;
            }

            $column = $this->apiService->createColumn($boardId, $title, $columnType, $settings);

            if (!$column || !isset($column['id'])) {
                Logger::error('MondayIntegration: Failed to create column', [
                    'board_id' => $boardId,
                    'column_title' => $title,
                    'column_type' => $columnType
                ]);
                return null;
            }

            Logger::info('MondayIntegration: Successfully created column', [
                'board_id' => $boardId,
                'column_id' => $column['id'],
                'column_title' => $title,
                'column_type' => $columnType
            ]);

            return $column;
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error creating column', [
                'board_id' => $boardId,
                'column_title' => $title,
                'column_type' => $columnType,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Create a board with a predefined template
     *
     * @param string $boardName Name of the board to create
     * @param string $templateId ID of the template to use
     * @param string $boardKind Type of board (public/private/share)
     * @return array|null The created board data or null on failure
     */
    public function createBoardFromTemplate($boardName, $templateId, $boardKind = 'public')
    {
        try {
            Logger::info('MondayIntegration: Creating new board from template', [
                'board_name' => $boardName,
                'template_id' => $templateId,
                'board_kind' => $boardKind
            ]);

            $board = $this->apiService->createBoardFromTemplate($boardName, $templateId, $boardKind);

            if (!$board || !isset($board['id'])) {
                Logger::error('MondayIntegration: Failed to create board from template', [
                    'board_name' => $boardName,
                    'template_id' => $templateId
                ]);
                return null;
            }

            Logger::info('MondayIntegration: Successfully created board from template', [
                'board_id' => $board['id'],
                'board_name' => $boardName,
                'template_id' => $templateId
            ]);

            return $board;
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error creating board from template', [
                'board_name' => $boardName,
                'template_id' => $templateId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Create a board from template data
     *
     * @param string $boardName Board name
     * @param string $boardKind Board kind (public, private, share)
     * @param array $template Template data with columns and groups
     * @return array|null Created board data or null on failure
     */
    public function createBoardFromTemplateData($boardName, $boardKind = 'public', array $template = [])
    {
        try {
            // Create the board
            $board = $this->apiService->createBoard($boardName, $boardKind);
            
            if (!$board) {
                Logger::error("Failed to create board from template data");
                return null;
            }
            
            $boardId = $board['id'];
            
            // Add columns from template
            if (isset($template['columns']) && is_array($template['columns'])) {
                foreach ($template['columns'] as $column) {
                    $columnTitle = $column['title'] ?? '';
                    $columnType = $column['type'] ?? 'text';
                    $columnSettings = $column['settings'] ?? null;
                    
                    if (!empty($columnTitle)) {
                        $this->apiService->createColumn(
                            $boardId,
                            $columnTitle,
                            $columnType,
                            $columnSettings
                        );
                    }
                }
            }
            
            // Add groups from template
            if (isset($template['groups']) && is_array($template['groups'])) {
                foreach ($template['groups'] as $group) {
                    $groupTitle = $group['title'] ?? '';
                    
                    if (!empty($groupTitle)) {
                        $this->apiService->createGroup($boardId, $groupTitle);
                    }
                }
            }
            
            // Refresh board data to include the new columns and groups
            $updatedBoard = $this->apiService->getBoardById($boardId);
            return $updatedBoard ?: $board;
            
        } catch (\Exception $e) {
            Logger::error('Error creating board from template data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Set permissions for a board
     *
     * @param string $boardId ID of the board
     * @param array $userIds Array of user IDs to add to the board
     * @param string $permissionLevel Permission level (view/edit/admin)
     * @return bool Success status
     */
    public function setBoardPermissions($boardId, $userIds, $permissionLevel = 'view')
    {
        try {
            Logger::info('MondayIntegration: Setting board permissions', [
                'board_id' => $boardId,
                'user_count' => count($userIds),
                'permission_level' => $permissionLevel
            ]);

            $success = true;

            foreach ($userIds as $userId) {
                $result = $this->apiService->addUserToBoard($boardId, $userId, $permissionLevel);
                
                if (!$result) {
                    Logger::warning('MondayIntegration: Failed to add user to board', [
                        'board_id' => $boardId,
                        'user_id' => $userId,
                        'permission_level' => $permissionLevel
                    ]);
                    $success = false;
                }
            }

            if ($success) {
                Logger::info('MondayIntegration: Successfully set board permissions', [
                    'board_id' => $boardId,
                    'user_count' => count($userIds),
                    'permission_level' => $permissionLevel
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error setting board permissions', [
                'board_id' => $boardId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get available board templates
     *
     * @return array Array of available templates
     */
    public function getAvailableTemplates()
    {
        try {
            return $this->apiService->getBoardTemplates();
        } catch (\Exception $e) {
            Logger::error('MondayIntegration: Error getting board templates', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get default columns for a new board
     *
     * @return array Array of default columns
     */
    protected function getDefaultColumns()
    {
        return [
            [
                'title' => 'Email',
                'type' => 'email',
            ],
            [
                'title' => 'Phone',
                'type' => 'phone',
            ],
            [
                'title' => 'Status',
                'type' => 'status',
                'settings' => json_encode([
                    'labels' => [
                        'New',
                        'In Progress',
                        'Done',
                        'Stuck'
                    ]
                ])
            ],
            [
                'title' => 'Date',
                'type' => 'date',
            ],
            [
                'title' => 'Assigned To',
                'type' => 'people',
            ],
            [
                'title' => 'Source',
                'type' => 'dropdown',
                'settings' => json_encode([
                    'labels' => [
                        'Email',
                        'Phone',
                        'WhatsApp',
                        'Facebook',
                        'Other'
                    ]
                ])
            ],
        ];
    }
}
