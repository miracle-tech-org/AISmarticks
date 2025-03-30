<?php

namespace Modules\MondayIntegration\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\MondayIntegration\Models\MondaySettings;

/**
 * Service for managing caching in the Monday.com integration module.
 * Implements a tiered caching system with different TTLs for different types of data.
 */
class CacheService
{
    /**
     * Cache tiers with default TTLs in seconds
     */
    const TIER_STRUCTURE = 'structure'; // Board structure, columns (1 day)
    const TIER_DATA = 'data';           // Board items, conversations (1 hour)
    const TIER_MAPPING = 'mapping';     // Field mappings, settings (4 hours)
    const TIER_SEARCH = 'search';       // Search results (15 minutes)
    const TIER_TEMP = 'temp';           // Temporary data (5 minutes)
    
    /**
     * Default TTLs for each cache tier in seconds
     */
    protected $defaultTtls = [
        self::TIER_STRUCTURE => 86400,  // 1 day
        self::TIER_DATA => 3600,       // 1 hour
        self::TIER_MAPPING => 14400,   // 4 hours
        self::TIER_SEARCH => 900,      // 15 minutes
        self::TIER_TEMP => 300,        // 5 minutes
    ];
    
    /**
     * Cache key prefix
     */
    protected $keyPrefix = 'monday_';
    
    /**
     * Whether caching is enabled
     */
    protected $cacheEnabled = true;
    
    /**
     * Custom TTLs from settings
     */
    protected $customTtls = [];
    
    /**
     * In-memory cache for frequently accessed data
     */
    protected $memoryCache = [];
    
    /**
     * Request deduplication registry
     */
    protected $requestRegistry = [];
    
    /**
     * CacheService constructor.
     */
    public function __construct()
    {
        $this->loadSettings();
    }
    
    /**
     * Load cache settings from the database
     */
    protected function loadSettings()
    {
        try {
            $settings = MondaySettings::getSettings();
            
            // Check if caching is enabled
            $this->cacheEnabled = $settings['cache_enabled'] ?? true;
            
            // Load custom TTLs if available
            if (isset($settings['cache_ttls']) && is_array($settings['cache_ttls'])) {
                $this->customTtls = $settings['cache_ttls'];
            }
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error loading cache settings', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get the TTL for a specific tier
     *
     * @param string $tier
     * @return int TTL in seconds
     */
    public function getTtl($tier)
    {
        // Check for custom TTL in settings
        if (isset($this->customTtls[$tier])) {
            return (int) $this->customTtls[$tier];
        }
        
        // Fall back to default TTL
        return $this->defaultTtls[$tier] ?? $this->defaultTtls[self::TIER_TEMP];
    }
    
    /**
     * Generate a cache key with prefix and tier
     *
     * @param string $key Base key
     * @param string $tier Cache tier
     * @return string Full cache key
     */
    public function makeKey($key, $tier)
    {
        return $this->keyPrefix . $tier . '_' . $key;
    }
    
    /**
     * Get a value from cache
     *
     * @param string $key Base key
     * @param string $tier Cache tier
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function get($key, $tier, $default = null)
    {
        if (!$this->cacheEnabled) {
            return $default;
        }
        
        // Check in-memory cache first for frequently accessed data
        $memKey = $tier . '_' . $key;
        if (isset($this->memoryCache[$memKey])) {
            return $this->memoryCache[$memKey];
        }
        
        $fullKey = $this->makeKey($key, $tier);
        $value = Cache::get($fullKey, $default);
        
        // Store in memory cache for faster subsequent access
        if ($value !== $default && in_array($tier, [self::TIER_STRUCTURE, self::TIER_MAPPING])) {
            $this->memoryCache[$memKey] = $value;
        }
        
        return $value;
    }
    
    /**
     * Store a value in cache
     *
     * @param string $key Base key
     * @param mixed $value Value to store
     * @param string $tier Cache tier
     * @param int|null $ttl Custom TTL (overrides tier default)
     * @return bool
     */
    public function put($key, $value, $tier, $ttl = null)
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        
        // Store in memory cache for faster subsequent access
        if (in_array($tier, [self::TIER_STRUCTURE, self::TIER_MAPPING])) {
            $memKey = $tier . '_' . $key;
            $this->memoryCache[$memKey] = $value;
        }
        
        $fullKey = $this->makeKey($key, $tier);
        $cacheTtl = $ttl ?? $this->getTtl($tier);
        
        return Cache::put($fullKey, $value, $cacheTtl);
    }
    
    /**
     * Check if a key exists in cache
     *
     * @param string $key Base key
     * @param string $tier Cache tier
     * @return bool
     */
    public function has($key, $tier)
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        
        // Check in-memory cache first
        $memKey = $tier . '_' . $key;
        if (isset($this->memoryCache[$memKey])) {
            return true;
        }
        
        $fullKey = $this->makeKey($key, $tier);
        return Cache::has($fullKey);
    }
    
    /**
     * Remove a key from cache
     *
     * @param string $key Base key
     * @param string $tier Cache tier
     * @return bool
     */
    public function forget($key, $tier)
    {
        // Remove from memory cache
        $memKey = $tier . '_' . $key;
        unset($this->memoryCache[$memKey]);
        
        $fullKey = $this->makeKey($key, $tier);
        return Cache::forget($fullKey);
    }
    
    /**
     * Get a value from cache or store it if it doesn't exist
     *
     * @param string $key Base key
     * @param string $tier Cache tier
     * @param \Closure $callback Function to generate value if not in cache
     * @param int|null $ttl Custom TTL (overrides tier default)
     * @return mixed
     */
    public function remember($key, $tier, $callback, $ttl = null)
    {
        if (!$this->cacheEnabled) {
            return $callback();
        }
        
        // Check for request deduplication to prevent duplicate cache misses
        $requestHash = md5($key . $tier);
        if (isset($this->requestRegistry[$requestHash])) {
            // Wait for the original request to complete
            $startTime = microtime(true);
            $timeout = 5; // 5 seconds timeout
            
            while (isset($this->requestRegistry[$requestHash]) && 
                  (microtime(true) - $startTime) < $timeout) {
                usleep(50000); // 50ms sleep
                
                // Check if the value is now in cache
                $memKey = $tier . '_' . $key;
                if (isset($this->memoryCache[$memKey])) {
                    return $this->memoryCache[$memKey];
                }
                
                $fullKey = $this->makeKey($key, $tier);
                if (Cache::has($fullKey)) {
                    $value = Cache::get($fullKey);
                    
                    // Store in memory cache
                    if (in_array($tier, [self::TIER_STRUCTURE, self::TIER_MAPPING])) {
                        $this->memoryCache[$memKey] = $value;
                    }
                    
                    return $value;
                }
            }
        }
        
        // Register this request
        $this->requestRegistry[$requestHash] = true;
        
        try {
            // Check in-memory cache first
            $memKey = $tier . '_' . $key;
            if (isset($this->memoryCache[$memKey])) {
                unset($this->requestRegistry[$requestHash]);
                return $this->memoryCache[$memKey];
            }
            
            $fullKey = $this->makeKey($key, $tier);
            $cacheTtl = $ttl ?? $this->getTtl($tier);
            
            $value = Cache::remember($fullKey, $cacheTtl, $callback);
            
            // Store in memory cache for faster subsequent access
            if (in_array($tier, [self::TIER_STRUCTURE, self::TIER_MAPPING])) {
                $this->memoryCache[$memKey] = $value;
            }
            
            return $value;
        } finally {
            // Always unregister the request when done
            unset($this->requestRegistry[$requestHash]);
        }
    }
    
    /**
     * Clear all cache for a specific tier
     *
     * @param string $tier Cache tier
     * @return bool
     */
    public function clearTier($tier)
    {
        // Clear memory cache for this tier
        foreach (array_keys($this->memoryCache) as $key) {
            if (strpos($key, $tier . '_') === 0) {
                unset($this->memoryCache[$key]);
            }
        }
        
        $pattern = $this->keyPrefix . $tier . '_*';
        $keys = $this->getKeysMatchingPattern($pattern);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        return true;
    }
    
    /**
     * Clear all Monday.com integration cache
     *
     * @return bool
     */
    public function clearAll()
    {
        // Clear all memory cache
        $this->memoryCache = [];
        
        $pattern = $this->keyPrefix . '*';
        $keys = $this->getKeysMatchingPattern($pattern);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        return true;
    }
    
    /**
     * Clear cache for a specific board
     *
     * @param string $boardId
     * @return bool
     */
    public function clearBoardCache($boardId)
    {
        // Clear memory cache for this board
        foreach (array_keys($this->memoryCache) as $key) {
            if (strpos($key, '_board_' . $boardId) !== false || 
                strpos($key, '_board_columns_' . $boardId) !== false) {
                unset($this->memoryCache[$key]);
            }
        }
        
        // Clear structure cache
        $this->forget('board_' . $boardId, self::TIER_STRUCTURE);
        $this->forget('board_columns_' . $boardId, self::TIER_STRUCTURE);
        
        // Clear data cache
        $pattern = $this->keyPrefix . self::TIER_DATA . '_board_items_' . $boardId . '*';
        $keys = $this->getKeysMatchingPattern($pattern);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        return true;
    }
    
    /**
     * Clear cache for a specific item
     *
     * @param string $itemId
     * @return bool
     */
    public function clearItemCache($itemId)
    {
        // Clear memory cache for this item
        foreach (array_keys($this->memoryCache) as $key) {
            if (strpos($key, '_item_' . $itemId) !== false || 
                strpos($key, '_item_updates_' . $itemId) !== false) {
                unset($this->memoryCache[$key]);
            }
        }
        
        $pattern = $this->keyPrefix . '*_*_' . $itemId . '*';
        $keys = $this->getKeysMatchingPattern($pattern);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        return true;
    }
    
    /**
     * Get cache keys matching a pattern
     * Note: This is a simplified implementation and may need to be adapted
     * based on the cache driver being used
     *
     * @param string $pattern
     * @return array
     */
    protected function getKeysMatchingPattern($pattern)
    {
        // This is a simplified implementation that works with file and array cache drivers
        // For Redis or Memcached, you would use their native pattern matching
        $keys = [];
        
        // For Laravel's file cache, we can't easily get keys by pattern
        // This is just a placeholder - in a real implementation, you might
        // need to use a different approach based on your cache driver
        
        // If using Redis, you could do something like:
        // return Cache::getRedis()->keys($pattern);
        
        return $keys;
    }
    
    /**
     * Enable or disable caching
     *
     * @param bool $enabled
     * @return void
     */
    public function setCacheEnabled($enabled)
    {
        $this->cacheEnabled = (bool) $enabled;
        
        // Update settings in database
        try {
            $settings = MondaySettings::getSettings();
            $settings['cache_enabled'] = $this->cacheEnabled;
            MondaySettings::saveSettings($settings);
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error saving cache settings', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Set custom TTL for a tier
     *
     * @param string $tier
     * @param int $ttl TTL in seconds
     * @return void
     */
    public function setTierTtl($tier, $ttl)
    {
        $this->customTtls[$tier] = (int) $ttl;
        
        // Update settings in database
        try {
            $settings = MondaySettings::getSettings();
            $settings['cache_ttls'] = $this->customTtls;
            MondaySettings::saveSettings($settings);
        } catch (\Exception $e) {
            Log::error('MondayIntegration: Error saving cache TTL settings', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Register a request to deduplicate cache misses
     *
     * @param string $key
     * @return string Request ID
     */
    public function registerRequest($key)
    {
        $requestId = uniqid('req_');
        $this->requestRegistry[$key] = $requestId;
        return $requestId;
    }
    
    /**
     * Unregister a request
     *
     * @param string $key
     * @return void
     */
    public function unregisterRequest($key)
    {
        unset($this->requestRegistry[$key]);
    }
    
    /**
     * Check if a request is registered
     *
     * @param string $key
     * @return bool
     */
    public function isRequestRegistered($key)
    {
        return isset($this->requestRegistry[$key]);
    }
}
