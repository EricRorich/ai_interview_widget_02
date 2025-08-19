<?php
/**
 * AI Interview Widget - Provider and Model Definitions
 * 
 * Centralized provider and model definitions with extensibility hooks.
 * Maintains current supported models with capabilities information.
 * 
 * @since 1.9.6
 * @author Eric Rorich
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Provider and Model Definitions Class
 * 
 * Provides centralized management of AI providers and their models
 * with extensibility hooks for developers.
 */
class AIW_Provider_Definitions {
    
    /**
     * Get all available providers
     * 
     * @return array Array of provider configurations
     */
    public static function get_providers() {
        $providers = array(
            'openai' => array(
                'name' => 'OpenAI',
                'description' => 'OpenAI GPT models including GPT-4 and GPT-3.5',
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'docs_url' => 'https://platform.openai.com/docs',
                'requires_api_key' => true,
                'supports_streaming' => true,
                'supports_function_calling' => true,
                'supports_json_mode' => true
            ),
            'anthropic' => array(
                'name' => 'Anthropic',
                'description' => 'Anthropic Claude models for conversational AI',
                'api_url' => 'https://api.anthropic.com/v1/messages',
                'docs_url' => 'https://docs.anthropic.com/',
                'requires_api_key' => true,
                'supports_streaming' => true,
                'supports_function_calling' => true,
                'supports_json_mode' => false
            ),
            'gemini' => array(
                'name' => 'Google Gemini',
                'description' => 'Google Gemini models via Vertex AI',
                'api_url' => 'https://generativelanguage.googleapis.com/v1/models',
                'docs_url' => 'https://ai.google.dev/docs',
                'requires_api_key' => true,
                'supports_streaming' => true,
                'supports_function_calling' => true,
                'supports_json_mode' => true
            ),
            'azure' => array(
                'name' => 'Azure OpenAI',
                'description' => 'OpenAI models hosted on Microsoft Azure',
                'api_url' => null, // Dynamic based on deployment
                'docs_url' => 'https://docs.microsoft.com/en-us/azure/cognitive-services/openai/',
                'requires_api_key' => true,
                'requires_endpoint' => true,
                'supports_streaming' => true,
                'supports_function_calling' => true,
                'supports_json_mode' => true
            ),
            'custom' => array(
                'name' => 'Custom/Self-hosted',
                'description' => 'Custom or self-hosted AI models',
                'api_url' => null, // User-defined
                'docs_url' => null,
                'requires_api_key' => false,
                'requires_endpoint' => true,
                'supports_streaming' => false,
                'supports_function_calling' => false,
                'supports_json_mode' => false
            )
        );
        
        return apply_filters('aiw_providers', $providers);
    }
    
    /**
     * Get models for a specific provider
     * 
     * @param string $provider Provider ID
     * @return array Array of model configurations
     */
    public static function get_models_for_provider($provider) {
        $models = array();
        
        switch ($provider) {
            case 'openai':
                $models = array(
                    'gpt-4o' => array(
                        'name' => 'GPT-4o',
                        'description' => 'Most capable multimodal model with vision, audio, and text capabilities',
                        'context_window' => 128000,
                        'max_output' => 16384,
                        'capabilities' => array('text', 'vision', 'audio', 'function_calling', 'json_mode'),
                        'recommended' => true,
                        'deprecated' => false,
                        'cost_tier' => 'high'
                    ),
                    'gpt-4o-mini' => array(
                        'name' => 'GPT-4o Mini',
                        'description' => 'Fast, affordable model with vision capabilities - best value for most tasks',
                        'context_window' => 128000,
                        'max_output' => 16384,
                        'capabilities' => array('text', 'vision', 'function_calling', 'json_mode'),
                        'recommended' => true,
                        'deprecated' => false,
                        'cost_tier' => 'low'
                    ),
                    'o1-preview' => array(
                        'name' => 'o1-preview',
                        'description' => 'Advanced reasoning model for complex problem-solving tasks',
                        'context_window' => 128000,
                        'max_output' => 32768,
                        'capabilities' => array('text', 'advanced_reasoning'),
                        'recommended' => false,
                        'deprecated' => false,
                        'cost_tier' => 'premium'
                    ),
                    'o1-mini' => array(
                        'name' => 'o1-mini',
                        'description' => 'Faster reasoning model optimized for STEM tasks',
                        'context_window' => 128000,
                        'max_output' => 65536,
                        'capabilities' => array('text', 'advanced_reasoning'),
                        'recommended' => false,
                        'deprecated' => false,
                        'cost_tier' => 'high'
                    ),
                    'gpt-4-turbo' => array(
                        'name' => 'GPT-4 Turbo',
                        'description' => 'Previous generation high-performance model',
                        'context_window' => 128000,
                        'max_output' => 4096,
                        'capabilities' => array('text', 'vision', 'function_calling', 'json_mode'),
                        'recommended' => false,
                        'deprecated' => false,
                        'cost_tier' => 'high'
                    ),
                    'gpt-4' => array(
                        'name' => 'GPT-4',
                        'description' => 'Original GPT-4 model',
                        'context_window' => 8192,
                        'max_output' => 4096,
                        'capabilities' => array('text', 'function_calling'),
                        'recommended' => false,
                        'deprecated' => true,
                        'migration_suggestion' => 'gpt-4o',
                        'cost_tier' => 'high'
                    ),
                    'gpt-3.5-turbo' => array(
                        'name' => 'GPT-3.5 Turbo',
                        'description' => 'Legacy model - use GPT-4o Mini for better performance and lower cost',
                        'context_window' => 16385,
                        'max_output' => 4096,
                        'capabilities' => array('text', 'function_calling', 'json_mode'),
                        'recommended' => false,
                        'deprecated' => true,
                        'migration_suggestion' => 'gpt-4o-mini',
                        'cost_tier' => 'low'
                    )
                );
                break;
                
            case 'anthropic':
                $models = array(
                    'claude-3-5-sonnet-20241022' => array(
                        'name' => 'Claude 3.5 Sonnet',
                        'description' => 'Most capable Claude model with excellent reasoning and coding abilities',
                        'context_window' => 200000,
                        'max_output' => 8192,
                        'capabilities' => array('text', 'vision', 'function_calling'),
                        'recommended' => true,
                        'deprecated' => false,
                        'cost_tier' => 'medium'
                    ),
                    'claude-3-5-haiku-20241022' => array(
                        'name' => 'Claude 3.5 Haiku',
                        'description' => 'Fast and affordable Claude model with vision capabilities',
                        'context_window' => 200000,
                        'max_output' => 8192,
                        'capabilities' => array('text', 'vision'),
                        'recommended' => true,
                        'deprecated' => false,
                        'cost_tier' => 'low'
                    ),
                    'claude-3-opus-20240229' => array(
                        'name' => 'Claude 3 Opus',
                        'description' => 'Most powerful Claude 3 model for complex tasks',
                        'context_window' => 200000,
                        'max_output' => 4096,
                        'capabilities' => array('text', 'vision'),
                        'recommended' => false,
                        'deprecated' => false,
                        'cost_tier' => 'high'
                    ),
                    'claude-3-sonnet-20240229' => array(
                        'name' => 'Claude 3 Sonnet',
                        'description' => 'Previous generation model - consider upgrading to Claude 3.5',
                        'context_window' => 200000,
                        'max_output' => 4096,
                        'capabilities' => array('text', 'vision'),
                        'recommended' => false,
                        'deprecated' => true,
                        'migration_suggestion' => 'claude-3-5-sonnet-20241022',
                        'cost_tier' => 'medium'
                    ),
                    'claude-3-haiku-20240307' => array(
                        'name' => 'Claude 3 Haiku',
                        'description' => 'Previous generation model - consider upgrading to Claude 3.5 Haiku',
                        'context_window' => 200000,
                        'max_output' => 4096,
                        'capabilities' => array('text', 'vision'),
                        'recommended' => false,
                        'deprecated' => true,
                        'migration_suggestion' => 'claude-3-5-haiku-20241022',
                        'cost_tier' => 'low'
                    )
                );
                break;
                
            case 'gemini':
                $models = array(
                    'gemini-2.0-flash-exp' => array(
                        'name' => 'Gemini 2.0 Flash (Experimental)',
                        'description' => 'Latest experimental Gemini model with advanced multimodal capabilities',
                        'context_window' => 1000000,
                        'max_output' => 8192,
                        'capabilities' => array('text', 'vision', 'audio', 'function_calling', 'json_mode'),
                        'recommended' => false,
                        'deprecated' => false,
                        'experimental' => true,
                        'cost_tier' => 'medium'
                    ),
                    'gemini-1.5-pro' => array(
                        'name' => 'Gemini 1.5 Pro',
                        'description' => 'Most capable production model with massive context window',
                        'context_window' => 2000000,
                        'max_output' => 8192,
                        'capabilities' => array('text', 'vision', 'audio', 'function_calling', 'json_mode'),
                        'recommended' => true,
                        'deprecated' => false,
                        'cost_tier' => 'high'
                    ),
                    'gemini-1.5-flash' => array(
                        'name' => 'Gemini 1.5 Flash',
                        'description' => 'Fast and efficient model for most tasks with excellent value',
                        'context_window' => 1000000,
                        'max_output' => 8192,
                        'capabilities' => array('text', 'vision', 'audio', 'function_calling', 'json_mode'),
                        'recommended' => true,
                        'deprecated' => false,
                        'cost_tier' => 'low'
                    ),
                    'gemini-1.5-flash-8b' => array(
                        'name' => 'Gemini 1.5 Flash-8B',
                        'description' => 'Ultra-fast and cost-effective model for high-volume applications',
                        'context_window' => 1000000,
                        'max_output' => 8192,
                        'capabilities' => array('text', 'vision', 'function_calling', 'json_mode'),
                        'recommended' => false,
                        'deprecated' => false,
                        'cost_tier' => 'ultra_low'
                    ),
                    'gemini-1.0-pro' => array(
                        'name' => 'Gemini 1.0 Pro',
                        'description' => 'Legacy model - consider upgrading to Gemini 1.5 Flash for better performance',
                        'context_window' => 32000,
                        'max_output' => 2048,
                        'capabilities' => array('text', 'function_calling'),
                        'recommended' => false,
                        'deprecated' => true,
                        'migration_suggestion' => 'gemini-1.5-flash',
                        'cost_tier' => 'medium'
                    )
                );
                break;
                
            case 'azure':
                // Azure uses same models as OpenAI but with different naming
                $models = array(
                    'gpt-4o' => array(
                        'name' => 'GPT-4o (Azure)',
                        'description' => 'Latest OpenAI model hosted on Azure with enterprise features',
                        'context_window' => 128000,
                        'max_output' => 16384,
                        'capabilities' => array('text', 'vision', 'function_calling', 'json_mode'),
                        'recommended' => true,
                        'deprecated' => false,
                        'cost_tier' => 'high'
                    ),
                    'gpt-4o-mini' => array(
                        'name' => 'GPT-4o Mini (Azure)',
                        'description' => 'Fast and affordable OpenAI model hosted on Azure',
                        'context_window' => 128000,
                        'max_output' => 16384,
                        'capabilities' => array('text', 'vision', 'function_calling', 'json_mode'),
                        'recommended' => true,
                        'deprecated' => false,
                        'cost_tier' => 'low'
                    ),
                    'o1-preview' => array(
                        'name' => 'o1-preview (Azure)',
                        'description' => 'Advanced reasoning model available on Azure',
                        'context_window' => 128000,
                        'max_output' => 32768,
                        'capabilities' => array('text', 'advanced_reasoning'),
                        'recommended' => false,
                        'deprecated' => false,
                        'cost_tier' => 'premium'
                    ),
                    'gpt-4-turbo' => array(
                        'name' => 'GPT-4 Turbo (Azure)',
                        'description' => 'Previous generation high-performance model on Azure',
                        'context_window' => 128000,
                        'max_output' => 4096,
                        'capabilities' => array('text', 'vision', 'function_calling', 'json_mode'),
                        'recommended' => false,
                        'deprecated' => false,
                        'cost_tier' => 'high'
                    ),
                    'gpt-35-turbo' => array(
                        'name' => 'GPT-3.5 Turbo (Azure)',
                        'description' => 'Legacy model - consider upgrading to GPT-4o Mini for better performance',
                        'context_window' => 16385,
                        'max_output' => 4096,
                        'capabilities' => array('text', 'function_calling', 'json_mode'),
                        'recommended' => false,
                        'deprecated' => true,
                        'migration_suggestion' => 'gpt-4o-mini',
                        'cost_tier' => 'low'
                    )
                );
                break;
                
            case 'custom':
                $models = array(
                    'custom-model' => array(
                        'name' => 'Custom Model',
                        'description' => 'User-defined custom or self-hosted model',
                        'context_window' => null,
                        'max_output' => null,
                        'capabilities' => array('text'),
                        'recommended' => true,
                        'deprecated' => false,
                        'cost_tier' => 'unknown'
                    )
                );
                break;
        }
        
        return apply_filters('aiw_models_for_provider', $models, $provider);
    }
    
    /**
     * Get model capabilities
     * 
     * @param string $model_id Model identifier
     * @param string $provider Provider ID
     * @return array Array of capabilities
     */
    public static function get_model_capabilities($model_id, $provider) {
        $models = self::get_models_for_provider($provider);
        $capabilities = isset($models[$model_id]['capabilities']) ? $models[$model_id]['capabilities'] : array();
        
        return apply_filters('aiw_model_capabilities', $capabilities, $model_id, $provider);
    }
    
    /**
     * Check if a model is deprecated
     * 
     * @param string $model_id Model identifier
     * @param string $provider Provider ID
     * @return bool Whether the model is deprecated
     */
    public static function is_model_deprecated($model_id, $provider) {
        $models = self::get_models_for_provider($provider);
        return isset($models[$model_id]['deprecated']) && $models[$model_id]['deprecated'];
    }
    
    /**
     * Get migration suggestion for a deprecated model
     * 
     * @param string $model_id Model identifier
     * @param string $provider Provider ID
     * @return string|null Suggested replacement model
     */
    public static function get_migration_suggestion($model_id, $provider) {
        $models = self::get_models_for_provider($provider);
        return isset($models[$model_id]['migration_suggestion']) ? $models[$model_id]['migration_suggestion'] : null;
    }
    
    /**
     * Get all models formatted for select dropdown
     * 
     * @param string $provider Provider ID
     * @return array Array of models with value/label structure
     */
    public static function get_models_for_select($provider) {
        $models = self::get_models_for_provider($provider);
        $select_options = array();
        
        foreach ($models as $model_id => $model_config) {
            $label = $model_config['name'];
            
            // Add indicators for special model states
            if (!empty($model_config['recommended'])) {
                $label .= ' ⭐';
            }
            
            if (!empty($model_config['deprecated'])) {
                $label .= ' ⚠️ (Deprecated)';
            }
            
            if (!empty($model_config['experimental'])) {
                $label .= ' 🧪 (Experimental)';
            }
            
            $select_options[] = array(
                'value' => $model_id,
                'label' => $label,
                'description' => $model_config['description'],
                'capabilities' => $model_config['capabilities'],
                'deprecated' => !empty($model_config['deprecated']),
                'recommended' => !empty($model_config['recommended']),
                'experimental' => !empty($model_config['experimental']),
                'migration_suggestion' => isset($model_config['migration_suggestion']) ? $model_config['migration_suggestion'] : null
            );
        }
        
        return $select_options;
    }
}