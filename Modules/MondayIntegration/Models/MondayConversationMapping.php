<?php

namespace Modules\MondayIntegration\Models;

use Illuminate\Database\Eloquent\Model;
use App\Conversation;

class MondayConversationMapping extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'monday_conversation_mappings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'conversation_id',
        'monday_item_id',
        'board_id',
    ];

    /**
     * Get the conversation associated with the mapping.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Find a mapping by conversation ID.
     *
     * @param int $conversationId
     * @return MondayConversationMapping|null
     */
    public static function findByConversation($conversationId)
    {
        return self::where('conversation_id', $conversationId)->first();
    }

    /**
     * Find a mapping by Monday.com item ID.
     *
     * @param string $mondayItemId
     * @param string|null $boardId
     * @return MondayConversationMapping|null
     */
    public static function findByMondayItemId($mondayItemId, $boardId = null)
    {
        $query = self::where('monday_item_id', $mondayItemId);
        
        if ($boardId) {
            $query->where('board_id', $boardId);
        }
        
        return $query->first();
    }

    /**
     * Create or update a mapping.
     *
     * @param int $conversationId
     * @param string $mondayItemId
     * @param string $boardId
     * @return MondayConversationMapping
     */
    public static function createOrUpdateMapping($conversationId, $mondayItemId, $boardId)
    {
        $mapping = self::where('conversation_id', $conversationId)->first();
        
        if (!$mapping) {
            $mapping = new self();
            $mapping->conversation_id = $conversationId;
        }
        
        $mapping->monday_item_id = $mondayItemId;
        $mapping->board_id = $boardId;
        $mapping->save();
        
        return $mapping;
    }
}
