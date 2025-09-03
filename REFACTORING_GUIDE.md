# AI Interview Widget - Modern Refactoring (v2.0.0)

This document describes the major structural refactoring implemented in version 2.0.0 of the AI Interview Widget plugin.

## Overview

The plugin has been completely refactored to follow modern WordPress development standards, including:

- **PSR-4 autoloading** with namespace `EricRorich\AIInterviewWidget\`
- **Composer integration** for dependency management
- **Class-based architecture** with proper separation of concerns
- **Modern directory structure** following WordPress plugin standards
- **Test scaffolding** with PHPUnit integration
- **Elementor widget support** with dedicated widget classes
- **Comprehensive setup classes** for activation/deactivation/uninstall

## New Directory Structure

```
├── ai-interview-widget.php     # New streamlined main plugin file
├── composer.json               # Composer configuration with PSR-4 autoloading
├── phpcs.xml.dist             # PHP CodeSniffer configuration
├── uninstall.php              # Plugin uninstall handler
├── assets/                    # Reorganized asset files
│   ├── admin/                 # Admin-specific assets
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── public/                # Frontend assets
│       ├── css/
│       ├── js/
│       └── images/
├── src/                       # Namespaced source code
│   ├── Admin/                 # Admin functionality
│   │   └── AdminService.php
│   ├── Core/                  # Core plugin functionality
│   │   ├── Plugin.php         # Main plugin class (singleton)
│   │   └── Assets.php         # Asset management
│   ├── Setup/                 # Plugin lifecycle management
│   │   ├── Activator.php
│   │   ├── Deactivator.php
│   │   ├── Requirements.php
│   │   └── Uninstaller.php
│   └── Widgets/               # Elementor integration
│       ├── ElementorService.php
│       └── InterviewWidget.php
├── templates/                 # Widget templates
│   └── widget-base.php
└── tests/                     # Test suite
    ├── phpunit.xml.dist
    ├── bootstrap.php
    ├── unit/                  # Unit tests
    └── integration/           # Integration tests
```

## Key Classes

### Core\Plugin
Main plugin class implementing singleton pattern. Handles:
- Service registration and initialization
- Environment validation
- Service container functionality

### Setup\Requirements
Validates environment requirements:
- PHP version (>= 7.4)
- WordPress version (>= 5.0)
- Required PHP functions
- Optional Elementor version check

### Core\Assets
Manages asset enqueuing with:
- Proper versioning and cache busting
- HTTPS enforcement
- Conditional loading based on context
- Development vs production optimization

### Admin\AdminService
Handles all admin functionality:
- Menu registration
- Settings management
- AJAX handlers
- Admin notices

### Widgets\InterviewWidget
Elementor widget implementation with:
- Full Elementor controls integration
- Template-based rendering
- Customizable appearance options

## Development Setup

### Installation

1. **With Composer (recommended):**
   ```bash
   composer install
   ```

2. **Without Composer:**
   The plugin includes a fallback autoloader that works without Composer.

### Testing

1. **Install WordPress test suite:**
   ```bash
   bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
   ```

2. **Run tests:**
   ```bash
   composer test
   # or
   vendor/bin/phpunit
   ```

3. **Run with coverage:**
   ```bash
   composer test-coverage
   ```

### Code Standards

Run PHP CodeSniffer:
```bash
vendor/bin/phpcs
```

## Backward Compatibility

The refactoring maintains complete backward compatibility:

- **Shortcode:** `[ai_interview_widget]` continues to work
- **AJAX endpoints:** All existing endpoints preserved
- **Options:** Existing settings are maintained
- **Hooks:** All WordPress hooks remain functional
- **Legacy class:** `AIInterviewWidget` class available for compatibility

## Migration Notes

### For Developers

- **Namespace:** New code should use `EricRorich\AIInterviewWidget\` namespace
- **Autoloading:** Classes are automatically loaded via PSR-4
- **Services:** Access services via `Plugin::get_instance()->get_service('service_name')`
- **Constants:** New constants available: `AIW_VERSION`, `AIW_PLUGIN_DIR`, `AIW_PLUGIN_URL`

### For Users

No action required. The plugin will continue to work exactly as before with improved:
- **Performance:** Better organized code and autoloading
- **Reliability:** Enhanced error handling and validation
- **Maintainability:** Modern code structure for future updates

## Elementor Integration

The plugin now provides native Elementor support:

1. **Widget Location:** Find "AI Interview Widget" in Elementor panel
2. **Category:** "AI Interview Widgets"
3. **Controls:** Full customization options including colors, typography, spacing
4. **Template:** Uses `templates/widget-base.php` for consistent rendering

## Testing

Comprehensive test suite includes:

- **Unit Tests:** Core class functionality
- **Integration Tests:** WordPress and Elementor integration
- **Coverage:** Key functionality covered with PHPUnit

Run tests with:
```bash
# All tests
vendor/bin/phpunit

# Specific test suite
vendor/bin/phpunit --testsuite=unit
vendor/bin/phpunit --testsuite=integration
```

## Future Development

The new structure provides a solid foundation for:

- **Enhanced features:** Easier to add new functionality
- **Performance optimization:** Clear separation allows targeted improvements
- **Third-party integrations:** Service-based architecture supports extensions
- **API expansion:** Structured approach to adding new AI providers

## Support

For development questions or issues with the refactored code:

1. Check the test suite for usage examples
2. Review class documentation in `src/` directory
3. Use the debugging features available in development mode
4. Refer to WordPress and Elementor documentation for integration patterns