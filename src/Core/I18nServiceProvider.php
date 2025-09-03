<?php
/**
 * Internationalization Service Provider
 * 
 * Handles plugin text domain loading and i18n functionality.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Core;

use EricRorich\AIInterviewWidget\Core\Container;
use EricRorich\AIInterviewWidget\Core\Contracts\ServiceProviderInterface;

/**
 * I18n Service Provider
 * 
 * Manages internationalization and localization.
 * 
 * @since 2.0.0
 */
class I18nServiceProvider implements ServiceProviderInterface {

    /**
     * Register services with the container
     * 
     * @param Container $container The service container
     * @return void
     */
    public function register(Container $container) {
        $container->singleton('i18n', function($container) {
            return new I18nLoader();
        });
    }

    /**
     * Boot services after all providers are registered
     * 
     * @param Container $container The service container
     * @return void
     */
    public function boot(Container $container) {
        add_action('init', function() use ($container) {
            $i18n = $container->make('i18n');
            $i18n->load_textdomain();
        });
    }
}

/**
 * I18n Loader class
 * 
 * Handles text domain loading and language functionality.
 * 
 * @since 2.0.0
 */
class I18nLoader {

    /**
     * Text domain
     * 
     * @var string
     */
    const TEXT_DOMAIN = 'ai-interview-widget';

    /**
     * Load plugin text domain
     * 
     * @return bool True if successful, false on failure
     */
    public function load_textdomain(): bool {
        $loaded = load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            dirname(plugin_basename(AIW_PLUGIN_FILE)) . '/languages'
        );

        // Set locale filter
        add_filter('plugin_locale', [$this, 'set_locale'], 10, 2);

        return $loaded;
    }

    /**
     * Set locale for the plugin
     * 
     * @param string $locale Current locale
     * @param string $domain Text domain
     * @return string Modified locale
     */
    public function set_locale(string $locale, string $domain): string {
        if ($domain === self::TEXT_DOMAIN) {
            // Allow override via option or filter
            $override_locale = apply_filters('aiw_locale_override', null);
            if ($override_locale) {
                return $override_locale;
            }

            // Check for saved user language preference
            $user_locale = get_option('ai_interview_widget_language');
            if ($user_locale) {
                return $user_locale;
            }
        }

        return $locale;
    }

    /**
     * Check if text domain is loaded
     * 
     * @return bool True if loaded, false otherwise
     */
    public function is_textdomain_loaded(): bool {
        return is_textdomain_loaded(self::TEXT_DOMAIN);
    }

    /**
     * Get available languages
     * 
     * @return array Array of available language codes
     */
    public function get_available_languages(): array {
        $languages_dir = AIW_PLUGIN_DIR . 'languages/';
        
        if (!is_dir($languages_dir)) {
            return ['en_US'];
        }

        $languages = ['en_US']; // Always include English
        $files = glob($languages_dir . '*.mo');
        
        foreach ($files as $file) {
            $basename = basename($file, '.mo');
            $lang_code = str_replace(self::TEXT_DOMAIN . '-', '', $basename);
            if (!in_array($lang_code, $languages)) {
                $languages[] = $lang_code;
            }
        }

        return $languages;
    }

    /**
     * Get language name from code
     * 
     * @param string $code Language code
     * @return string Language name
     */
    public function get_language_name(string $code): string {
        $names = [
            'en_US' => 'English (US)',
            'en_GB' => 'English (UK)', 
            'es_ES' => 'Español',
            'fr_FR' => 'Français',
            'de_DE' => 'Deutsch',
            'it_IT' => 'Italiano',
            'pt_PT' => 'Português',
            'pt_BR' => 'Português (Brasil)',
            'ru_RU' => 'Русский',
            'ja' => '日本語',
            'ko_KR' => '한국어',
            'zh_CN' => '中文 (简体)',
            'zh_TW' => '中文 (繁體)',
            'ar' => 'العربية',
            'hi_IN' => 'हिन्दी',
            'tr_TR' => 'Türkçe',
            'pl_PL' => 'Polski',
            'nl_NL' => 'Nederlands',
            'sv_SE' => 'Svenska',
            'da_DK' => 'Dansk',
        ];

        return $names[$code] ?? $code;
    }
}