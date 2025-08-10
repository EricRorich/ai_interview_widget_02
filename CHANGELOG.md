# AI Interview Widget - CHANGELOG

## [1.9.6] - 2025-01-27

### Added - Translation Debug Panel
- **Advanced Translation Debugging System** in System Prompt Management interface
  - Collapsible debug panel with comprehensive troubleshooting tools
  - Environment readiness checks (API key status, nonce validation, permissions)
  - Real-time debug logging with timestamps and severity levels (INFO/WARN/ERROR)
  - Raw request/response preview with sanitized data for troubleshooting
  - Test translation functionality with detailed error reporting
  - Export debug logs feature for technical support
  - Global JavaScript API (`window.aiwTranslationDebug`) for extensibility

### Enhanced - Translation Backend
- **Improved `aiw_llm_translate()` function** with detailed debug metadata
  - Added latency measurement with microtime tracking
  - Enhanced error handling with structured response format
  - Debug mode support with comprehensive context information
  - Compression ratio analysis and response validation
- **Enhanced `handle_translate_prompt()` AJAX handler**
  - Environment readiness validation before processing
  - API provider configuration checks
  - Structured error responses with diagnostic metadata
  - Debug mode activation via WordPress filters

### Enhanced - Translation Frontend
- **Enhanced translation workflow** with better error handling
  - Per-language status indicators and error reporting
  - Improved partial failure handling (successful translations not discarded)
  - Better loading states and user feedback
  - Override existing translation handlers with debug-enhanced versions

### Improved - User Experience
- **Better error visibility** for translation issues
  - Environment status badges with color-coded indicators
  - Detailed error messages with actionable troubleshooting steps
  - Translation warning system with session-based display
  - Export functionality for sharing debug information with support

### Technical - Infrastructure
- Added comprehensive CSS for debug panel status indicators
- Implemented global debug logging system with log level filtering
- Added debug mode detection via WordPress debug constants
- Enhanced AJAX response structure with metadata support

## [1.9.5] - 2025-01-27

### Removed from UI
- **Play-Button Designs section** - Entire WordPress Customizer section with all play button customization controls
  - Button Size, Shape, Colors, Icon Style, Pulse Effects, Hover Styles, Focus Ring
  - Section registration conditionally disabled by default (can be re-enabled via filter)
- **Voice Buttons section** - Entire Enhanced Customizer section for voice button styling
  - Background Color, Border Color, Text Color controls  
  - Section hidden by default but stored values continue to apply
- **Canvas Shadow Intensity control** - Single control within Canvas & Background section
  - Intensity slider removed from Customizer UI
  - Shadow color control remains available

### Backward Compatibility
- All previously saved customization values continue to be honored on the frontend
- No deletion of stored theme_mod or option values during plugin update
- Canvas Shadow Intensity uses last saved value to prevent sudden visual changes
- Play button designs continue to render with stored configuration
- Voice button features remain active with last saved styling

### Deprecation Infrastructure
- Added `should_hide_deprecated_controls()` method with filter support for easy restoration
- Added `log_deprecation_notice()` for WP_DEBUG logging when deprecated settings accessed
- Wrapped deprecated Customizer controls in conditional registration blocks
- Updated customizer-preview.js to conditionally register listeners for deprecated controls
- Added @deprecated PHPDoc tags to related sanitization functions

### Developer Features
- Filter `ai_interview_widget_hide_deprecated_controls` to control deprecated section visibility
- Debug logging for deprecated setting access when WP_DEBUG is enabled
- Deprecation notices logged during customizer sync operations
- Maintained all sanitization functions for backward compatibility

### Documentation
- Updated inline code comments with deprecation notices
- Added deprecation context to customizer registration functions
- Updated plugin version to 1.9.5
- Created CHANGELOG.md for version history tracking

### Future Removal Path
- Deprecated code paths marked with @todo comments for future full removal
- Easy restoration possible by setting filter to return false
- Clean removal possible in future major version (v2.0.0)

---

## [1.9.4] - 2025-08-03
### Added
- Canvas shadow color backward compatibility
- Enhanced customizer functionality
- WordPress Customizer integration

## [1.9.3] - 2025-08-03  
### Added
- Complete debugging and testing infrastructure
- Enhanced admin interface with comprehensive diagnostics
- Improved error handling and logging

## [1.9.0-1.9.2] - 2025-08
### Added
- Voice capabilities with ElevenLabs integration
- Enhanced visual customizer
- Multi-language support
- Performance optimizations

## [1.8.0-1.8.10] - 2025-07
### Added
- Foundation AI chat functionality
- OpenAI integration
- Basic customization options
- Admin interface setup