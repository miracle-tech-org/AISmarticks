<?php

namespace Modules\MondayIntegration\Services;

use Modules\MondayIntegration\Models\MondayCallerIdMapping;
use App\Customer;
use App\Conversation;
use Illuminate\Support\Facades\Log;
use Modules\MondayIntegration\Services\CacheService;

class CallerIdService
{
    /**
     * @var MondayApiService
     */
    protected $mondayApiService;
    
    /**
     * @var PhoneNormalizer
     */
    protected $phoneNormalizer;
    
    /**
     * @var CacheService
     */
    protected $cacheService;
    
    /**
     * Minimum number of digits that must match for fuzzy phone matching
     *
     * @var int
     */
    protected $minMatchingDigits = 8;
    
    /**
     * Minimum confidence score for valid matches
     *
     * @var float
     */
    protected $minConfidenceScore = 0.5;
    
    /**
     * Confidence adjustments for different match types
     *
     * @var array
     */
    protected $confidenceAdjustments = [
        'exact_match' => 1.0,
        'normalized_match' => 0.9,
        'partial_match' => 0.7,
        'cross_channel_match' => 0.6,
        'api_search' => 0.5
    ];
    
    /**
     * Channel groups for cross-channel matching
     *
     * @var array
     */
    protected $channelGroups = [
        'messaging' => ['whatsapp', 'facebook', 'twitter'],
        'voice' => ['phone', 'voip'],
        'email' => ['email', 'web']
    ];
    
    /**
     * Constructor
     * 
     * @param MondayApiService $mondayApiService
     * @param PhoneNormalizer $phoneNormalizer
     * @param CacheService $cacheService
     */
    public function __construct(MondayApiService $mondayApiService, PhoneNormalizer $phoneNormalizer, CacheService $cacheService)
    {
        $this->mondayApiService = $mondayApiService;
        $this->phoneNormalizer = $phoneNormalizer;
        $this->cacheService = $cacheService;
    }
    
    /**
     * Find Monday.com item ID by caller ID (phone number, email, etc.)
     * Uses a cascading search strategy:
     * 1. Exact match
     * 2. Normalized match (for phone numbers)
     * 3. Partial match (for phone numbers)
     * 4. Cross-channel match
     * 5. API search
     * 
     * @param string $callerId
     * @param string $channel
     * @param string|null $boardId Optional board ID to search within
     * @return array|null Array with 'monday_item_id', 'confidence_score', and 'match_type'
     */
    public function findMondayItemByCallerId($callerId, $channel, $boardId = null)
    {
        if (empty($callerId) || empty($channel)) {
            return null;
        }
        
        // Step 1: Check for exact match in our mapping database
        $exactMatch = $this->findExactMatch($callerId, $channel, $boardId);
        if ($exactMatch) {
            return $exactMatch;
        }
        
        // Step 2: For phone numbers, try normalized matching
        if ($channel === 'phone' || $channel === 'whatsapp') {
            $normalizedMatch = $this->findFuzzyMatch($callerId, $boardId);
            if ($normalizedMatch) {
                return $normalizedMatch;
            }
            
            // Step 3: Try partial phone number matching
            $partialMatch = $this->findByPartialPhoneNumber($callerId, $boardId);
            if ($partialMatch) {
                return [
                    'monday_item_id' => $partialMatch->monday_item_id,
                    'confidence_score' => $this->confidenceAdjustments['partial_match'] * $partialMatch->confidence_score,
                    'match_type' => 'partial_match',
                    'mapping_id' => $partialMatch->id,
                    'source_id' => $partialMatch->source_id
                ];
            }
        }
        
        // Step 4: Try cross-channel matching
        $crossChannelMatch = $this->findCrossChannelMatch($callerId, $channel, $boardId);
        if ($crossChannelMatch) {
            return $crossChannelMatch;
        }
        
        // Step 5: Search Monday.com API
        try {
            $apiMatch = $this->searchMondayForCallerId($callerId, $channel, $boardId);
            if ($apiMatch) {
                return $apiMatch;
            }
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error searching Monday.com API', [
                'error' => $e->getMessage(),
                'caller_id' => $callerId,
                'channel' => $channel,
                'board_id' => $boardId
            ]);
            
            return null;
        }
        
        return null;
    }
    
    /**
     * Find exact match in our mapping database
     * 
     * @param string $callerId
     * @param string $channel
     * @param string|null $boardId
     * @return array|null
     */
    protected function findExactMatch($callerId, $channel, $boardId = null)
    {
        $cacheKey = "exact_match_{$callerId}_{$channel}" . ($boardId ? "_{$boardId}" : '');
        
        return $this->cacheService->remember($cacheKey, CacheService::TIER_SEARCH, function() use ($callerId, $channel, $boardId) {
            $mapping = MondayCallerIdMapping::findBySourceAndChannel($callerId, $channel, $boardId);
            
            if ($mapping) {
                return [
                    'monday_item_id' => $mapping->monday_item_id,
                    'confidence_score' => $this->confidenceAdjustments['exact_match'],
                    'match_type' => 'exact_match',
                    'mapping_id' => $mapping->id,
                    'source_id' => $mapping->source_id
                ];
            }
            
            return null;
        });
    }
    
    /**
     * Find fuzzy match for phone numbers
     * 
     * @param string $phoneNumber
     * @param string|null $boardId
     * @return array|null
     */
    protected function findFuzzyMatch($phoneNumber, $boardId = null)
    {
        // Only process if it looks like a phone number
        if (!preg_match('/\d/', $phoneNumber)) {
            return null;
        }
        
        $normalizedPhone = $this->phoneNormalizer->normalize($phoneNumber);
        $cacheKey = "fuzzy_match_{$normalizedPhone}" . ($boardId ? "_{$boardId}" : '');
        
        return $this->cacheService->remember($cacheKey, CacheService::TIER_SEARCH, function() use ($normalizedPhone, $boardId) {
            // Try to find by normalized phone number
            $mapping = $this->findByPhoneNumberFuzzy($normalizedPhone, $boardId);
            
            if ($mapping) {
                return [
                    'monday_item_id' => $mapping->monday_item_id,
                    'confidence_score' => $this->confidenceAdjustments['normalized_match'] * $mapping->confidence_score,
                    'match_type' => 'normalized_match',
                    'mapping_id' => $mapping->id,
                    'source_id' => $mapping->source_id
                ];
            }
            
            return null;
        });
    }
    
    /**
     * Find a match across different channels
     * 
     * @param string $callerId
     * @param string $channel
     * @param string|null $boardId
     * @return array|null
     */
    protected function findCrossChannelMatch($callerId, $channel, $boardId = null)
    {
        // Get the channel group for the current channel
        $currentGroup = null;
        foreach ($this->channelGroups as $group => $channels) {
            if (in_array($channel, $channels)) {
                $currentGroup = $group;
                break;
            }
        }
        
        // If we couldn't determine the channel group, return null
        if (!$currentGroup) {
            return null;
        }
        
        $cacheKey = "cross_channel_{$callerId}_{$channel}" . ($boardId ? "_{$boardId}" : '');
        
        return $this->cacheService->remember($cacheKey, CacheService::TIER_SEARCH, function() use ($callerId, $channel, $currentGroup, $boardId) {
            // Look for matches in other channels within the same group
            foreach ($this->channelGroups[$currentGroup] as $relatedChannel) {
                // Skip the current channel
                if ($relatedChannel === $channel) {
                    continue;
                }
                
                $match = $this->findExactMatch($callerId, $relatedChannel, $boardId);
                if ($match) {
                    $match['confidence_score'] = $this->confidenceAdjustments['cross_channel_match'] * $match['confidence_score'];
                    $match['match_type'] = 'cross_channel_match';
                    return $match;
                }
            }
            
            return null;
        });
    }
    
    /**
     * Find mappings by phone number with fuzzy matching using the PhoneNormalizer
     * 
     * @param string $phoneNumber
     * @param string|null $boardId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByPhoneNumberFuzzy($phoneNumber, $boardId = null)
    {
        // Normalize the input phone number
        $normalizedInput = $this->phoneNormalizer->normalize($phoneNumber);
        
        if (empty($normalizedInput)) {
            return collect();
        }
        
        // Get all phone-related mappings
        $phoneChannels = ['phone', 'whatsapp'];
        $allMappings = collect();
        
        foreach ($phoneChannels as $channel) {
            $mappings = MondayCallerIdMapping::where('channel', $channel);
            
            if ($boardId) {
                $mappings = $mappings->where('board_id', $boardId);
            }
            
            $mappings = $mappings->get();
            $allMappings = $allMappings->merge($mappings);
        }
        
        // Filter and sort mappings by match quality
        $matchedMappings = collect();
        
        foreach ($allMappings as $mapping) {
            $normalizedMapping = $this->phoneNormalizer->normalize($mapping->source_id);
            
            // Compare the normalized phone numbers
            [$isMatch, $matchedDigits, $confidence] = $this->phoneNormalizer->compare(
                $normalizedInput,
                $normalizedMapping,
                $this->minMatchingDigits
            );
            
            if ($isMatch) {
                // Adjust the mapping's confidence score based on the match quality
                $adjustedConfidence = $mapping->confidence_score * $confidence;
                $mapping->confidence_score = $adjustedConfidence;
                
                $matchedMappings->push($mapping);
            }
        }
        
        return $matchedMappings->sortByDesc('confidence_score');
    }
    
    /**
     * Find mappings by partial phone number (right-to-left matching)
     * 
     * @param string $phoneNumber
     * @param string|null $boardId
     * @return MondayCallerIdMapping|null
     */
    public function findByPartialPhoneNumber($phoneNumber, $boardId = null)
    {
        $normalizedInput = $this->phoneNormalizer->normalize($phoneNumber);
        
        if (strlen($normalizedInput) < $this->minMatchingDigits) {
            return null;
        }
        
        // Get the last N digits for matching
        $lastDigits = substr($normalizedInput, -$this->minMatchingDigits);
        
        // Find mappings with phone numbers ending with these digits
        $phoneChannels = ['phone', 'whatsapp'];
        $bestMatch = null;
        $highestConfidence = 0;
        
        foreach ($phoneChannels as $channel) {
            $mappings = MondayCallerIdMapping::where('channel', $channel);
            
            if ($boardId) {
                $mappings = $mappings->where('board_id', $boardId);
            }
            
            $mappings = $mappings->get();
            
            foreach ($mappings as $mapping) {
                $normalizedMapping = $this->phoneNormalizer->normalize($mapping->source_id);
                
                if (strlen($normalizedMapping) < $this->minMatchingDigits) {
                    continue;
                }
                
                $mappingLastDigits = substr($normalizedMapping, -$this->minMatchingDigits);
                
                if ($mappingLastDigits === $lastDigits) {
                    // Calculate a confidence score based on the length of the match
                    // relative to the total length of the phone numbers
                    $matchRatio = $this->minMatchingDigits / max(strlen($normalizedInput), strlen($normalizedMapping));
                    $confidence = $mapping->confidence_score * $matchRatio;
                    
                    if ($confidence > $highestConfidence) {
                        $highestConfidence = $confidence;
                        $bestMatch = $mapping;
                    }
                }
            }
        }
        
        if ($bestMatch) {
            $bestMatch->updateLastUsed();
        }
        
        return $bestMatch;
    }
    
    /**
     * Search Monday.com for a caller ID
     * 
     * @param string $callerId
     * @param string $channel
     * @param string|null $boardId
     * @return array|null
     */
    public function searchMondayForCallerId($callerId, $channel, $boardId = null)
    {
        // Generate a cache key based on inputs
        $cacheKey = "monday_search_{$callerId}_{$channel}" . ($boardId ? "_{$boardId}" : '');
        
        return $this->cacheService->remember($cacheKey, CacheService::TIER_SEARCH, function() use ($callerId, $channel, $boardId) {
            // Map channel to Monday.com column ID
            $columnMap = [
                'email' => 'email',
                'phone' => 'phone',
                'whatsapp' => 'phone',
                'facebook' => 'facebook_id',
                'twitter' => 'twitter_id'
            ];
            
            $columnId = $columnMap[$channel] ?? null;
            
            if (!$columnId) {
                return null;
            }
            
            // Get the board ID from settings
            $settings = app('monday_settings');
            $board = $boardId ?: $settings->get('board_id');
            
            if (!$board) {
                return null;
            }
            
            // Search Monday.com for the caller ID
            $items = $this->mondayApiService->searchItems($board, $columnId, $callerId);
            
            if (!empty($items) && isset($items[0]['id'])) {
                $item = $items[0];
                
                // Create a mapping for this caller ID
                $mapping = $this->createMapping(
                    $callerId,
                    $channel,
                    $item['id'],
                    $boardId
                );
                
                return [
                    'monday_item_id' => $item['id'],
                    'confidence_score' => $this->confidenceAdjustments['api_search'],
                    'match_type' => 'api_search',
                    'mapping_id' => $mapping->id,
                    'source_id' => $mapping->source_id
                ];
            }
            
            return null;
        });
    }
    
    /**
     * Find Monday.com item by customer
     * Uses a cascading search strategy across multiple identifiers
     *
     * @param Customer $customer
     * @param string|null $boardId
     * @return array|null
     */
    public function findMondayItemByCustomer(Customer $customer, $boardId = null)
    {
        $mapping = MondayCallerIdMapping::findByCustomer($customer->id, $boardId);
        
        if ($mapping) {
            return [
                'monday_item_id' => $mapping->monday_item_id,
                'confidence_score' => 1.0,
                'match_type' => 'customer_mapping',
                'mapping_id' => $mapping->id,
                'source_id' => $mapping->source_id
            ];
        }
        
        // Try to find by email
        if ($customer->getMainEmail()) {
            $emailMatch = $this->findMondayItemByCallerId($customer->getMainEmail(), 'email', $boardId);
            if ($emailMatch) {
                return $emailMatch;
            }
        }
        
        // Try to find by phone
        if ($customer->getMainPhoneWithPlus()) {
            $phoneMatch = $this->findMondayItemByCallerId($customer->getMainPhoneWithPlus(), 'phone', $boardId);
            if ($phoneMatch) {
                return $phoneMatch;
            }
        }
        
        return null;
    }
    
    /**
     * Find Monday.com item ID by conversation
     * 
     * @param Conversation $conversation
     * @param string|null $boardId
     * @return array|null Array with 'monday_item_id', 'confidence_score', and 'match_type'
     */
    public function findMondayItemByConversation(Conversation $conversation, $boardId = null)
    {
        $mapping = \Modules\MondayIntegration\Models\MondayConversationMapping::where('conversation_id', $conversation->id);
        
        if ($boardId) {
            $mapping = $mapping->where('board_id', $boardId);
        }

        $mapping = $mapping->first();

        if ($mapping) {
            return [
                'monday_item_id' => $mapping->monday_item_id,
                'confidence_score' => 1.0,
                'match_type' => 'conversation_mapping',
                'mapping_id' => $mapping->id
            ];
        }

        // If no mapping, try to find by customer
        // if ($conversation->customer_id) {
        //     return $this->findMondayItemByCustomer($conversation->customer, $boardId);
        // }
        
        return null;
    }
    
    /**
     * Create a mapping for a conversation
     *
     * @param Conversation $conversation
     * @param string $mondayItemId
     * @param float $confidenceScore
     * @param string|null $boardId
     * @return MondayCallerIdMapping|null
     */
    protected function createMappingForConversation(Conversation $conversation, $mondayItemId, $confidenceScore, $boardId = null)
    {
        // Determine the source ID and channel
        $sourceId = $conversation->customer_email ?: $conversation->customer->email;
        $channel = $this->mapConversationSourceToChannel($conversation->source_via);
        
        if (!$sourceId || !$channel) {
            return null;
        }
        
        return $this->createMapping(
            $sourceId,
            $mondayItemId,
            $channel,
            $confidenceScore,
            $conversation,
            $boardId
        );
    }
    
    /**
     * Map conversation source to channel
     *
     * @param string $source
     * @return string|null
     */
    protected function mapConversationSourceToChannel($source)
    {
        $sourceMap = [
            'email' => 'email',
            'web' => 'email',
            'whatsapp' => 'whatsapp',
            'phone' => 'phone',
            'facebook' => 'facebook',
            'twitter' => 'twitter'
        ];
        
        return $sourceMap[$source] ?? null;
    }
    
    /**
     * Create or update a mapping
     *
     * @param string $sourceId
     * @param string $mondayItemId
     * @param string $channel
     * @param float $confidenceScore
     * @param Conversation|null $conversation
     * @param string|null $boardId
     * @return MondayCallerIdMapping
     */
    public function createMapping($sourceId, $mondayItemId, $channel, $confidenceScore, $conversation = null, $boardId = null)
    {
        $conversationId = $conversation ? $conversation->id : null;
        
        try {
            return MondayCallerIdMapping::createOrUpdateMapping(
                $sourceId,
                $mondayItemId,
                $channel,
                $confidenceScore,
                $conversationId,
                $boardId
            );
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error creating mapping', [
                'error' => $e->getMessage(),
                'source_id' => $sourceId,
                'monday_item_id' => $mondayItemId,
                'channel' => $channel,
                'board_id' => $boardId
            ]);
            
            // Create a basic mapping object to return
            $mapping = new MondayCallerIdMapping();
            $mapping->source_id = $sourceId;
            $mapping->monday_item_id = $mondayItemId;
            $mapping->channel = $channel;
            $mapping->confidence_score = $confidenceScore;
            $mapping->conversation_id = $conversationId;
            $mapping->board_id = $boardId;
            
            return $mapping;
        }
    }
    
    /**
     * Update an existing mapping's confidence score
     *
     * @param int $mappingId
     * @param float $newConfidenceScore
     * @return bool
     */
    public function updateMappingConfidence($mappingId, $newConfidenceScore)
    {
        $mapping = MondayCallerIdMapping::find($mappingId);
        
        if (!$mapping) {
            return false;
        }
        
        $mapping->confidence_score = max(0, min(1, $newConfidenceScore));
        $mapping->updateLastUsed();
        
        return $mapping->save();
    }
    
    /**
     * Delete a mapping
     *
     * @param int $mappingId
     * @return bool
     */
    public function deleteMapping($mappingId)
    {
        $mapping = MondayCallerIdMapping::find($mappingId);
        
        if (!$mapping) {
            return false;
        }
        
        return $mapping->delete();
    }
    
    /**
     * Set minimum matching digits for fuzzy phone matching
     *
     * @param int $digits
     * @return $this
     */
    public function setMinMatchingDigits($digits)
    {
        $this->minMatchingDigits = max(4, min(15, (int)$digits));
        return $this;
    }
    
    /**
     * Set minimum confidence score for valid matches
     *
     * @param float $score
     * @return $this
     */
    public function setMinConfidenceScore($score)
    {
        $this->minConfidenceScore = max(0, min(1, (float)$score));
        return $this;
    }
    
    /**
     * Get mapping statistics
     *
     * @return array
     */
    public function getStatistics()
    {
        $totalMappings = MondayCallerIdMapping::count();
        $channelCounts = MondayCallerIdMapping::selectRaw('channel, count(*) as count')
            ->groupBy(['channel'])
            ->pluck('count', 'channel')
            ->toArray();
        
        return [
            'total_mappings' => $totalMappings,
            'channel_counts' => $channelCounts,
            'min_matching_digits' => $this->minMatchingDigits,
            'min_confidence_score' => $this->minConfidenceScore
        ];
    }
    
    /**
     * Format a phone number for display
     *
     * @param string $phoneNumber
     * @param bool $includeCountryCode
     * @return string
     */
    public function formatPhoneNumber($phoneNumber, $includeCountryCode = true)
    {
        return $this->phoneNormalizer->format($phoneNumber, $includeCountryCode);
    }
    
    /**
     * Get the country code from a phone number
     *
     * @param string $phoneNumber
     * @return string|null
     */
    public function getCountryCode($phoneNumber)
    {
        [$countryCode, ] = $this->phoneNormalizer->extractCountryCode($phoneNumber);
        return $countryCode;
    }
    
    /**
     * Set confidence adjustment for a specific match type
     *
     * @param string $matchType
     * @param float $adjustment
     * @return $this
     */
    public function setConfidenceAdjustment($matchType, $adjustment)
    {
        if (array_key_exists($matchType, $this->confidenceAdjustments)) {
            $this->confidenceAdjustments[$matchType] = max(0, min(1, (float)$adjustment));
        }
        
        return $this;
    }
    
    /**
     * Get all confidence adjustments
     *
     * @return array
     */
    public function getConfidenceAdjustments()
    {
        return $this->confidenceAdjustments;
    }
    
    /**
     * Add a channel to a channel group
     *
     * @param string $channel
     * @param string $groupName
     * @return $this
     */
    public function addChannelToGroup($channel, $groupName)
    {
        if (!array_key_exists($groupName, $this->channelGroups)) {
            $this->channelGroups[$groupName] = [];
        }
        
        if (!in_array($channel, $this->channelGroups[$groupName])) {
            $this->channelGroups[$groupName][] = $channel;
        }
        
        return $this;
    }
    
    /**
     * Get all channel groups
     *
     * @return array
     */
    public function getChannelGroups()
    {
        return $this->channelGroups;
    }
}
