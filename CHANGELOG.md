# AI Interview Widget - CHANGELOG

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