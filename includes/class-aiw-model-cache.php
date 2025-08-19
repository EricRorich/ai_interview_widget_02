<?php
/**
 * AI Interview Widget - Model Cache Manager
 * 
 * Optional caching system for model data to improve performance.
 * Can be extended for live fetching from provider APIs in the future.
 * 
 * @since 1.9.6
 * @author Eric Rorich
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Model Cache Manager Class
 * 
 * Handles caching of model data with WordPress transients
 */
class AIW_Model_Cache {
    
    const CACHE_KEY_PREFIX = 'aiw_models_';
    // Use WordPress constant if available, otherwise fallback to 12 hours in seconds
    const CACHE_DURATION = 43200; // 12 hours
    
    /**
     * Get cached models for a provider
     * 
     * @param string $provider Provider ID
     * @return array|false Cached models or false if not found
     */
    public static function get_cached_models($provider) {
        return get_transient(self::CACHE_KEY_PREFIX . $provider);
    }
    
    /**
     * Get cache duration
     * 
     * @return int Cache duration in seconds
     */
    public static function get_cache_duration() {
        if (defined('HOUR_IN_SECONDS')) {
            return 12 * HOUR_IN_SECONDS;
        }
        return self::CACHE_DURATION;
    }
    
    /**
     * Cache models for a provider
     * 
     * @param string $provider Provider ID
     * @param array $models Model data to cache
     * @param int $duration Cache duration in seconds (optional)
     * @return bool True on success, false on failure
     */
    public static function cache_models($provider, $models, $duration = null) {
        if ($duration === null) {
            $duration = self::get_cache_duration();
        }
        
        return set_transient(self::CACHE_KEY_PREFIX . $provider, $models, $duration);
    }
    
    /**
     * Clear cached models for a provider
     * 
     * @param string $provider Provider ID
     * @return bool True on success, false on failure
     */
    public static function clear_cache($provider) {
        return delete_transient(self::CACHE_KEY_PREFIX . $provider);
    }
    
    /**
     * Clear all cached model data
     * 
     * @return bool True on success
     */
    public static function clear_all_cache() {
        $providers = array('openai', 'anthropic', 'gemini', 'azure', 'custom');
        $success = true;
        
        foreach ($providers as $provider) {
            if (!self::clear_cache($provider)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get models with caching - first checks cache, then provider definitions
     * 
     * @param string $provider Provider ID
     * @return array Model data
     */
    public static function get_models_with_cache($provider) {
        // Check cache first
        $cached_models = self::get_cached_models($provider);
        if ($cached_models !== false) {
            return $cached_models;
        }
        
        // Get from provider definitions
        $models = AIW_Provider_Definitions::get_models_for_provider($provider);
        
        // Cache the result
        self::cache_models($provider, $models);
        
        return $models;
    }
    
    /**
     * Get models for select dropdown with caching
     * 
     * @param string $provider Provider ID
     * @return array Model data formatted for select dropdown
     */
    public static function get_models_for_select_with_cache($provider) {
        $cache_key = self::CACHE_KEY_PREFIX . $provider . '_select';
        $cached_models = get_transient($cache_key);
        
        if ($cached_models !== false) {
            return $cached_models;
        }
        
        // Get from provider definitions
        $models = AIW_Provider_Definitions::get_models_for_select($provider);
        
        // Cache the result
        set_transient($cache_key, $models, self::get_cache_duration());
        
        return $models;
    }
    
    /**
     * Check if caching is enabled
     * 
     * @return bool Whether caching is enabled
     */
    public static function is_caching_enabled() {
        return apply_filters('aiw_enable_model_caching', true);
    }
    
    /**
     * Get cache status information
     * 
     * @return array Cache status for each provider
     */
    public static function get_cache_status() {
        $providers = array('openai', 'anthropic', 'gemini', 'azure', 'custom');
        $status = array();
        
        foreach ($providers as $provider) {
            $cached = self::get_cached_models($provider);
            $status[$provider] = array(
                'cached' => $cached !== false,
                'count' => $cached !== false ? count($cached) : 0,
                'expires' => $cached !== false ? self::get_cache_expiry($provider) : null
            );
        }
        
        return $status;
    }
    
    /**
     * Get cache expiry time for a provider
     * 
     * @param string $provider Provider ID
     * @return int|null Expiry timestamp or null if not cached
     */
    private static function get_cache_expiry($provider) {
        $transient_name = self::CACHE_KEY_PREFIX . $provider;
        $transient_timeout = '_transient_timeout_' . $transient_name;
        
        return get_option($transient_timeout);
    }
}