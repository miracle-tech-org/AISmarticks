<?php

namespace Modules\MondayIntegration\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Conversation;
use App\Customer;

class MondayCallerIdMapping extends Model
{
    protected $table = 'monday_caller_id_mappings';
    
    protected $fillable = [
        'source_id',
        'alternate_id',
        'monday_item_id',
        'board_id',
        'channel',
        'conversation_id',
        'last_used_at',
        'confidence_score'
    ];
    
    protected $casts = [
        'last_used_at' => 'datetime',
        'confidence_score' => 'float'
    ];
    
    /**
     * Validation rules
     *
     * @var array
     */
    protected static $rules = [
        'source_id' => 'required|string|max:255',
        'alternate_id' => 'nullable|string|max:255',
        'monday_item_id' => 'required|string|max:255',
        'board_id' => 'nullable|string|max:255',
        'channel' => 'required|string|in:email,phone,whatsapp,facebook,twitter,other',
        'confidence_score' => 'numeric|min:0|max:1',
        'conversation_id' => 'nullable|integer|exists:conversations,id',
    ];
    
    /**
     * Get the conversation associated with this mapping
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
    
    /**
     * Find a mapping by source ID and channel
     *
     * @param string $sourceId
     * @param string $channel
     * @param string|null $boardId Optional board ID to filter by
     * @return MondayCallerIdMapping|null
     */
    public static function findBySourceAndChannel($sourceId, $channel, $boardId = null)
    {
        $query = static::where('source_id', $sourceId)
            ->where('channel', $channel);
            
        if ($boardId) {
            $query->where('board_id', $boardId);
        }
        
        return $query->orderBy('confidence_score', 'desc')
            ->first();
    }
    
    /**
     * Find a mapping by Monday item ID
     *
     * @param string $mondayItemId
     * @param string|null $boardId Optional board ID to filter by
     * @return MondayCallerIdMapping|null
     */
    public static function findByMondayItemId($mondayItemId, $boardId = null)
    {
        $query = static::where('monday_item_id', $mondayItemId);
        
        if ($boardId) {
            $query->where('board_id', $boardId);
        }
        
        return $query->orderBy('confidence_score', 'desc')
            ->first();
    }
    
    /**
     * Find mappings by customer
     *
     * @param Customer $customer
     * @param string|null $boardId Optional board ID to filter by
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findByCustomer(Customer $customer, $boardId = null)
    {
        $query = static::query();
        
        // Check for email
        if ($customer->email) {
            $query->orWhere(function(Builder $q) use ($customer, $boardId) {
                $q->where('source_id', $customer->email)
                  ->where('channel', 'email');
                  
                if ($boardId) {
                    $q->where('board_id', $boardId);
                }
            });
            
            $query->orWhere(function(Builder $q) use ($customer, $boardId) {
                $q->where('alternate_id', $customer->email);
                
                if ($boardId) {
                    $q->where('board_id', $boardId);
                }
            });
        }
        
        // Check for phone number
        if ($customer->getPhoneNumber()) {
            $query->orWhere(function(Builder $q) use ($customer, $boardId) {
                $q->where('source_id', $customer->getPhoneNumber())
                  ->whereIn('channel', ['phone', 'whatsapp']);
                  
                if ($boardId) {
                    $q->where('board_id', $boardId);
                }
            });
            
            $query->orWhere(function(Builder $q) use ($customer, $boardId) {
                $q->where('alternate_id', $customer->getPhoneNumber())
                  ->whereIn('channel', ['phone', 'whatsapp']);
                  
                if ($boardId) {
                    $q->where('board_id', $boardId);
                }
            });
        }
        
        return $query->orderBy('confidence_score', 'desc')->get();
    }
    
    /**
     * Update the last used timestamp
     *
     * @return bool
     */
    public function updateLastUsed()
    {
        $this->last_used_at = Carbon::now();
        return $this->save();
    }
    
    /**
     * Create or update a mapping
     *
     * @param string $sourceId
     * @param string $mondayItemId
     * @param string $channel
     * @param float $confidenceScore
     * @param int|null $conversationId
     * @param string|null $boardId
     * @param string|null $alternateId
     * @return MondayCallerIdMapping
     * @throws ValidationException
     */
    public static function createOrUpdateMapping(
        $sourceId,
        $mondayItemId,
        $channel,
        $confidenceScore = 1.0,
        $conversationId = null,
        $boardId = null,
        $alternateId = null
    ) {
        $validator = Validator::make([
            'source_id' => $sourceId,
            'monday_item_id' => $mondayItemId,
            'board_id' => $boardId,
            'channel' => $channel,
            'confidence_score' => $confidenceScore,
            'conversation_id' => $conversationId,
            'alternate_id' => $alternateId
        ], static::$rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return static::updateOrCreate(
            ['source_id' => $sourceId, 'channel' => $channel, 'board_id' => $boardId],
            [
                'monday_item_id' => $mondayItemId,
                'confidence_score' => $confidenceScore,
                'conversation_id' => $conversationId,
                'alternate_id' => $alternateId,
                'last_used_at' => Carbon::now()
            ]
        );
    }
    
    /**
     * Find mappings by phone number with fuzzy matching
     *
     * @param string $phoneNumber
     * @param int $minDigits Minimum number of matching digits from right
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findByPhoneNumberFuzzy($phoneNumber, $minDigits = 8)
    {
        // Note: The actual phone number normalization is now handled by the PhoneNormalizer service
        // We just get all WhatsApp mappings and let the service handle the matching
        $mappings = static::where('channel', 'whatsapp')->get();
        $matches = collect();
        
        foreach ($mappings as $mapping) {
            // Note: The actual phone number normalization is now handled by the PhoneNormalizer service
            // We just get all phone-related mappings and let the service handle the matching
            $matches->push($mapping);
        }
        
        return $matches->sortByDesc('confidence_score');
    }
    
    /**
     * Get all mappings for a specific channel
     *
     * @param string $channel
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByChannel($channel)
    {
        return static::where('channel', $channel)
                     ->orderBy('last_used_at', 'desc')
                     ->get();
    }
    
    /**
     * Get statistics about mappings
     *
     * @return array
     */
    public static function getStatistics()
    {
        return [
            'total' => static::count(),
            'by_channel' => [
                'whatsapp' => static::where('channel', 'whatsapp')->count(),
                'email' => static::where('channel', 'email')->count(),
                'other' => static::whereNotIn('channel', ['whatsapp', 'email'])->count(),
            ],
            'high_confidence' => static::where('confidence_score', '>=', 0.8)->count(),
            'medium_confidence' => static::whereBetween('confidence_score', [0.5, 0.8])->count(),
            'low_confidence' => static::where('confidence_score', '<', 0.5)->count(),
            'recent' => static::where('last_used_at', '>=', Carbon::now()->subDays(7))->count(),
        ];
    }
}
