<?php
/**
 * I18n Test
 * 
 * Tests for internationalization functionality.
 * 
 * @package EricRorich\AIInterviewWidget\Tests
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Tests\Integration;

use PHPUnit\Framework\TestCase;
use EricRorich\AIInterviewWidget\Core\I18nLoader;

/**
 * I18n Test class
 * 
 * @since 2.0.0
 */
class I18nLoadTest extends TestCase {

    private $i18n_loader;

    protected function setUp(): void {
        // Mock the constants
        if (!defined('AIW_PLUGIN_FILE')) {
            define('AIW_PLUGIN_FILE', '/path/to/plugin/ai-interview-widget.php');
        }
        if (!defined('AIW_PLUGIN_DIR')) {
            define('AIW_PLUGIN_DIR', '/path/to/plugin/');
        }

        $this->i18n_loader = new I18nLoader();
    }

    public function test_text_domain_constant() {
        $reflection = new \ReflectionClass($this->i18n_loader);
        $text_domain = $reflection->getConstant('TEXT_DOMAIN');
        
        $this->assertEquals('ai-interview-widget', $text_domain);
    }

    public function test_get_available_languages() {
        $languages = $this->i18n_loader->get_available_languages();
        
        $this->assertIsArray($languages);
        $this->assertContains('en_US', $languages);
    }

    public function test_get_language_name() {
        $name = $this->i18n_loader->get_language_name('en_US');
        $this->assertEquals('English (US)', $name);
        
        $name = $this->i18n_loader->get_language_name('es_ES');
        $this->assertEquals('Español', $name);
        
        $name = $this->i18n_loader->get_language_name('fr_FR');
        $this->assertEquals('Français', $name);
        
        // Test unknown language code
        $name = $this->i18n_loader->get_language_name('unknown_CODE');
        $this->assertEquals('unknown_CODE', $name);
    }

    public function test_set_locale_filter() {
        // Test that filter returns original locale for different domain
        $locale = $this->i18n_loader->set_locale('en_US', 'other-domain');
        $this->assertEquals('en_US', $locale);
        
        // Test that filter can be applied for our domain
        $locale = $this->i18n_loader->set_locale('en_US', 'ai-interview-widget');
        $this->assertEquals('en_US', $locale); // Should return original if no override
    }

    public function test_language_name_coverage() {
        $test_languages = [
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
        ];

        foreach ($test_languages as $code => $expected_name) {
            $actual_name = $this->i18n_loader->get_language_name($code);
            $this->assertEquals($expected_name, $actual_name, "Language name for {$code} does not match");
        }
    }
}