# AI Interview Widget - Finalized Architecture v0.2.0

🚀 **Production-Ready WordPress Plugin with Modern Architecture**

This repository contains the finalized architecture implementation of the AI Interview Widget plugin, featuring modern WordPress development standards with Service Provider pattern, Dependency Injection, and comprehensive build pipeline.

## 🏗️ Finalized Architecture Features

### Core Architecture
- **Service Provider Pattern** - Modular service registration with extensibility hooks
- **Dependency Injection Container** - Lightweight DI container with defensive error handling  
- **PSR-4 Autoloading** - Modern class autoloading with fallback support
- **Asset Build Pipeline** - Vite-powered build system with manifest-based loading and fallbacks
- **Migration System** - Version-aware database migrations with rollback support

### Enhanced Integrations
- **Elementor Widgets** - Interview chat and topic list widgets with graceful degradation
- **Internationalization** - Complete i18n support with text domain loading
- **Requirements Checking** - PHP/WordPress version validation with admin notices
- **Backward Compatibility** - Utility functions and legacy support

### Developer Experience
- **Coding Standards** - WordPress, PSR-12, and custom rules via PHPCS
- **Static Analysis** - PHPStan for advanced code analysis
- **CI/CD Pipeline** - GitHub Actions with multi-PHP/Node version testing
- **Comprehensive Testing** - Unit and integration tests with PHPUnit

## 📋 Development Instructions

### Prerequisites
- PHP 7.4 or higher
- Node.js 18.x or higher  
- Composer
- WordPress 5.0 or higher

### Development Setup

1. **Clone and Install Dependencies**
   ```bash
   git clone https://github.com/EricRorich/ai_interview_widget_02.git
   cd ai_interview_widget_02
   composer install
   npm install
   ```

2. **Build Assets**
   ```bash
   # Development build with watching
   npm run dev
   
   # Production build
   npm run build
   
   # Preview build
   npm run preview
   ```

3. **Code Quality**
   ```bash
   # Run PHP code standards check
   vendor/bin/phpcs --standard=phpcs.xml.dist src/ ai-interview-widget.php
   
   # Fix auto-fixable issues
   vendor/bin/phpcbf --standard=phpcs.xml.dist src/ ai-interview-widget.php
   
   # Run static analysis
   vendor/bin/phpstan analyse --configuration=phpstan.neon.dist
   
   # Run all quality checks
   composer run lint
   ```

4. **Testing**
   ```bash
   # Run unit tests
   vendor/bin/phpunit
   
   # Run with coverage
   vendor/bin/phpunit --coverage-html coverage/
   
   # Run specific test suite
   vendor/bin/phpunit --testsuite unit
   vendor/bin/phpunit --testsuite integration
   ```

### Asset Pipeline

The plugin uses **Vite** for modern asset building:

- **Source files**: `assets/src/js/` and `assets/src/css/`
- **Built files**: `assets/build/` (with hashed filenames)
- **Manifest**: `assets/build/.vite/manifest.json`
- **Fallback**: Falls back to `assets/src/` files if build not available

### Migration System

Version management with semantic migrations:

```php
// Creating a new migration
class Migration_030 implements MigrationInterface {
    public function targetVersion(): string {
        return '0.3.0';
    }
    
    public function run(): bool {
        // Migration logic here
        return true;
    }
}
```

**Migration versioning strategy:**
- Use semantic versioning (x.y.z)
- Migrations run only once per target version
- Rollback support for development
- Migration history tracked in `ai_interview_widget_migrations` option

### Service Provider Extension

Extend functionality via the service provider filter:

```php
add_filter('ai_interview_widget_service_providers', function($providers) {
    $providers[] = new MyCustomServiceProvider();
    return $providers;
});
```

### Internationalization

```bash
# Extract translatable strings
wp i18n make-pot . languages/ai-interview-widget.pot

# Test text domain loading
php -r "
require_once 'ai-interview-widget.php';
if (is_textdomain_loaded('ai-interview-widget')) {
    echo 'Text domain loaded successfully';
} else {
    echo 'Text domain not loaded';
}
"
```

### Directory Structure

```
ai-interview-widget/
├── ai-interview-widget.php          # Main plugin file
├── assets/
│   ├── src/                         # Source assets
│   │   ├── js/                      # JavaScript sources
│   │   └── css/                     # CSS sources
│   └── build/                       # Built assets (generated)
├── src/
│   ├── Core/                        # Core classes
│   ├── Admin/                       # Admin functionality
│   ├── Frontend/                    # Frontend functionality
│   ├── Integrations/Elementor/      # Elementor widgets
│   └── Setup/                       # Setup, activation, migrations
├── templates/elementor/             # Widget templates
├── languages/                       # Translation files
├── tests/                          # Test suite
├── .github/workflows/              # CI configuration
├── composer.json                   # PHP dependencies
├── package.json                    # Node dependencies
├── phpcs.xml.dist                  # Code standards
└── vite.config.js                  # Build configuration
```

### Hooks & Filters

**Action Hooks:**
- `ai_interview_widget_bootstrapped` - Fired after plugin initialization

**Filter Hooks:**
- `ai_interview_widget_service_providers` - Modify service provider list

**Utility Functions:**
- `ai_interview_widget()` - Get main plugin instance

### Production Deployment

1. **Build assets**: `npm run build`
2. **Install composer dependencies**: `composer install --no-dev --optimize-autoloader`
3. **Upload to WordPress**: Plugin will auto-activate migrations on first load


🎯 Your Goals Achieved
✅ Primary Objectives
1. AI Chat Integration - GPT-4o-mini powered conversations as Eric Rorich
2. Voice Capabilities - Speech-to-text input and text-to-speech output
3. Visual Customization - Complete widget appearance control
4. Professional Portfolio Tool - Showcase technical expertise and creativity
5. Debugging & Reliability - Robust error handling and troubleshooting

🚀 Complete Feature Set
🧠 AI Chat Engine
* OpenAI GPT-4o-mini Integration - Latest model for optimal responses
* Custom System Prompts - Bilingual (EN/DE) personality configuration
* Conversation Memory - Context-aware chat sessions
* Fallback Handling - Graceful error management
* API Key Validation - Format and connectivity verification
🎤 Voice Features
* Speech Recognition - Browser-based voice input
* Text-to-Speech - ElevenLabs premium voice synthesis
* Voice Controls - Intuitive microphone and speaker buttons
* Audio Upload - Custom greeting file support (MP3)
* Multilingual Support - English and German voice models
* Browser Fallback - Works without ElevenLabs API
🎨 Enhanced Visual Customizer
* Complete Style Control - Colors, gradients, borders, animations
* Real-time Preview - Live widget display with all components
* Individual Reset Buttons - Reset specific settings independently
* Section Reset - Reset entire customization sections
* Export Functionality - Download generated custom CSS
* Sticky Preview Panel - Always visible while customizing
📝 Content Management
* Custom Headlines - Typography and color control
* Bilingual Welcome Messages - English and German greetings
* AI System Prompts - Complete personality customization
* Font Controls - Family, size, color selection
* Dynamic Content Updates - Real-time preview changes
🔊 Audio Management
* Custom Audio Upload - Replace default greeting files
* File Validation - MP3 format, size limits (5MB)
* Audio Preview - Listen to uploaded files in admin
* Status Indicators - Current audio file status display
* File Management - Clean removal and replacement tools
🔧 Enhanced Debugging System
* Comprehensive Error Logging - Step-by-step request tracking
* Browser Console Integration - Frontend error capture
* WordPress Error Logs - Backend debugging information
* Debug Dashboard - Real-time system status monitoring
* **Translation Debug Panel** - Advanced troubleshooting for system prompt translation
  - Environment status checks (API keys, nonce validation, permissions)
  - Real-time translation logs with timestamps and context
  - Request/response preview for debugging failed translations
  - Test translation functionality with detailed error reporting
  - Export debug logs for technical support
  - Global debug API (window.aiwTranslationDebug) for extensibility
* API Testing Tools - Connection validation and diagnostics
* Troubleshooting Guides - Step-by-step issue resolution

🏗️ Technical Architecture
🔒 Security Features
* Nonce Verification - WordPress security standards
* Input Sanitization - XSS and injection protection
* User Capability Checks - Admin-only configuration access
* AJAX Security - Secured endpoints with validation
📊 Performance Optimization
* Efficient Database Storage - JSON-encoded settings
* Conditional Script Loading - Only load when needed
* CSS Generation - Dynamic styles with caching
* File Management - Automatic cleanup of temporary files
🛠️ WordPress Integration
* Top-level Admin Menu - Professional plugin interface
* Settings API - WordPress standards compliance
* Shortcode Support - [ai_interview_widget] implementation
* Theme Compatibility - Works with any WordPress theme
* Plugin Standards - Full WP coding guidelines compliance

📈 Version Evolution
v1.8.1-1.8.5 - Foundation & Core Features
* Basic OpenAI chat integration
* Initial voice capabilities
* Simple customization options
* Admin interface setup
v1.8.6-1.8.7 - Enhanced Customization
* Complete visual customizer
* Real-time preview system
* Audio file upload
* Content management
v1.8.8-1.8.9 - Refinement & Polish
* Enhanced preview with full widget display
* Improved error handling
* Better user experience
* Performance optimizations


🎯 Current Status: Production Ready
✅ Fully Implemented
* Complete AI chat functionality
* Full voice feature set
* Comprehensive visual customization
* Enhanced debugging and error handling
* Professional admin interface
* Security and performance optimization
🔧 Debug Capabilities
* Real-time API key validation
* Step-by-step AJAX request logging
* Browser console error tracking
* WordPress error log integration
* Network connectivity testing
* Comprehensive troubleshooting guides

🚀 Future Enhancement Opportunities
Potential v2.0+ Features
* Advanced Analytics - Chat interaction tracking
* Multi-language Expansion - Additional language support
* Custom Voice Training - Personalized voice models
* Integration APIs - Third-party service connections
* Advanced Templates - Pre-built widget configurations
* Performance Monitoring - Real-time usage statistics

📝 Implementation Guide
Quick Setup:
1. Install and activate plugin
2. Configure OpenAI API key
3. Test API connection
4. Customize appearance (optional)
5. Add [ai_interview_widget] to any page/post
For Troubleshooting:
1. Check enhanced debug dashboard in customizer
2. Monitor browser console (F12) for errors
3. Review WordPress error logs at /wp-content/debug.log
4. Use API testing tools in main settings
5. Follow step-by-step troubleshooting guides

## 📋 Deprecated / Removed Customizer Options

**As of v1.9.5, the following customization options have been removed from the Customizer UI to streamline the user experience. Previously saved values continue to be honored for backward compatibility.**

### Removed Sections & Controls:

#### 🎤 Voice Buttons (Entire Section)
- **Background Color** - Voice button background styling
- **Border Color** - Voice button border styling  
- **Text Color** - Voice button text styling
- **Status:** Removed from Enhanced Customizer UI
- **Impact:** Voice button features remain active with last saved configuration

#### ▶️ Play-Button Designs (Entire Section)
- **Button Size** - Size control (40px-120px range)
- **Button Shape** - Circle, Rounded, Square options
- **Primary Color** - Main button color
- **Secondary Color (Gradient)** - Gradient end color
- **Icon Style** - Triangle variants and styles
- **Icon Color** - Play icon color
- **Pulse Effect** - Enable/disable pulse animation
- **Pulse Color** - Pulse effect color
- **Pulse Duration** - Animation timing (0.8s-3.5s)
- **Pulse Max Spread** - Shadow radius (8px-40px)
- **Hover Effect Style** - Scale, Glow, or None
- **Focus Ring Color** - Accessibility outline color
- **Status:** Removed from WordPress Customizer
- **Impact:** Play button continues to render with stored design configuration

#### 🎨 Canvas Shadow Intensity (Single Control)
- **Canvas Shadow Intensity** - Shadow strength control (0-100 range)
- **Status:** Removed from Canvas & Background section
- **Impact:** Canvas shadow uses last saved intensity value, shadow color control remains available

### Backward Compatibility Guarantees:

✅ **No Visual Regression** - Existing sites retain their current appearance  
✅ **Stored Values Preserved** - No deletion of saved customization data  
✅ **Frontend Functionality** - All features continue to work as configured  
✅ **Graceful Degradation** - Fresh installs use sensible defaults  

### For Developers:

- **Filter Available:** `ai_interview_widget_hide_deprecated_controls` - Set to `false` to restore deprecated controls for testing
- **Debug Logging:** Deprecation notices logged when `WP_DEBUG` is enabled
- **Future Removal:** Deprecated code paths marked for removal in v2.0.0

### Migration Path:
No action required. The plugin automatically maintains compatibility with existing configurations while providing a cleaner UI for new users.

---

## 📋 Canvas Shadow Color Setting Unification (v1.9.4)

**Backward Compatibility Notice**

The canvas shadow color setting has been unified to use canonical naming:
- **Current (canonical):** `canvas_shadow_color` - used in all internal plugin logic
- **Legacy (deprecated):** `ai_canvas_shadow_color` - WordPress Customizer theme_mod key

**Backward Compatibility:**
- Existing installations automatically migrate legacy settings
- Legacy `ai_canvas_shadow_color` values are preserved during migration
- CSS variables updated to `--aiw-canvas-shadow-color` with `--aiw-shadow-color` alias
- Deprecation notices logged in debug mode when legacy keys detected

**For Developers:**
- Use `canvas_shadow_color` for all new integrations
- Helper function `get_canvas_shadow_color()` handles fallback logic
- Legacy support maintained for one release cycle

🎉 Achievement Summary
Eric, you now have a complete, professional-grade AI chat widget that:
* Showcases your technical expertise
* Provides interactive portfolio experience
* Demonstrates AI integration skills
* Offers complete customization control
* Includes robust debugging capabilities
* Follows WordPress best practices
* Ready for production deployment
Current Version: 1.9.5 | Status: Complete & Production Ready ✅
