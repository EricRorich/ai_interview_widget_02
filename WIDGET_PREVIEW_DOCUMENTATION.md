# Widget Preview System Documentation

## Overview

The new Widget Preview System provides a robust, iframe-based live preview of the AI Interview Widget within the Enhanced Visual Customizer. This system replaces the previously removed Enhanced Live Preview System v2.0 with a cleaner, more maintainable approach.

## Architecture

### Core Components

1. **Iframe-based Isolation**: Uses a sandboxed iframe to prevent CSS/JS conflicts
2. **AJAX Endpoints**: Dedicated endpoints for preview rendering and updates
3. **Real-time Updates**: Debounced live updates as users modify controls
4. **Error Handling**: Comprehensive error states with auto-retry functionality
5. **Loading States**: Visual feedback during preview operations

### Data Flow

```
User Input (Controls) → JavaScript Collection → AJAX Request → PHP Processing → Preview Render → Iframe Display
                                                                                            ↓
                                                                              Message-based Updates
```

## Key Features

### 1. Security & Isolation
- Iframe sandbox with `allow-scripts allow-same-origin`
- Nonce verification for all AJAX requests  
- User capability checks (`manage_options`)
- Input validation and sanitization

### 2. Performance Optimization
- Debounced updates (500ms delay) to prevent excessive requests
- Lightweight update mechanism for real-time changes
- Efficient CSS generation and caching

### 3. Error Handling
- Loading, error, and retry states
- Auto-retry with exponential backoff (max 3 attempts)
- Graceful fallbacks for invalid input
- Comprehensive error logging

### 4. User Experience
- Real-time status indicators
- Manual refresh capability
- Responsive design for different screen sizes
- Accessibility considerations

## Implementation Details

### PHP Components

#### AJAX Handlers
- `handle_preview_render()`: Generates complete iframe HTML
- `handle_preview_update()`: Provides incremental updates
- `generate_preview_page()`: Creates standalone preview HTML
- `is_valid_json()`: Input validation helper

#### Security Features
- WordPress nonce verification
- JSON validation
- Input sanitization
- Error logging

### JavaScript Components

#### Core Functions
- `initializePreviewSystem()`: Main initialization
- `loadPreview()`: Full iframe reload
- `updatePreview()`: Incremental updates
- `setupControlListeners()`: Event binding

#### State Management
- `PREVIEW_CONFIG`: Central configuration object
- Debounced update handling
- Retry mechanism with backoff
- Status tracking

### HTML Structure

```html
<div id="widget_preview_container">
  <!-- Loading State -->
  <div id="preview-loading">...</div>
  
  <!-- Error State -->
  <div id="preview-error">...</div>
  
  <!-- Preview Iframe -->
  <iframe id="preview-iframe" sandbox="allow-scripts allow-same-origin">
    <!-- Complete widget HTML with custom styles -->
  </iframe>
</div>
```

## Configuration

### Preview Settings Collection

The system automatically collects all customizer settings:

#### Style Settings
- Container background (color/gradient)
- Border radius and padding
- Canvas colors and effects
- Play button design and colors
- Chatbox typography
- Voice button styling
- Visualizer themes and parameters

#### Content Settings
- Headline text
- Welcome messages (all languages)
- System prompts
- Dynamic language content

### Update Mechanisms

1. **Full Reload**: Complete iframe regeneration
   - Used for initial load
   - Fallback for update failures
   - File upload changes

2. **Incremental Update**: CSS and content injection
   - Real-time control changes
   - Message-based communication
   - Faster performance

## Integration Points

### WordPress Integration
- Uses `wp_enqueue_script/wp_enqueue_style`
- Respects admin dependencies (wp-color-picker, jQuery)
- Follows WordPress coding standards
- Namespaced actions and filters

### Existing Functionality
- Preserves "Save Styles" button functionality
- Maintains color picker compatibility
- No database schema changes
- Backward compatible with existing settings

## Testing

### Browser Compatibility
Tested across common admin browsers:
- Chrome/Chromium
- Firefox
- Safari
- Edge

### Error Scenarios
- Invalid JSON input
- Network failures
- Malformed CSS
- Missing elements
- Permission errors

## Maintenance

### Error Monitoring
- Console error tracking
- WordPress error log integration
- AJAX request logging
- Performance monitoring

### Future Extensibility
- Modular design allows easy feature addition
- Clean separation of concerns
- Documented interfaces for extensions
- Configurable update mechanisms

## Migration Notes

### From Previous System
- Enhanced Live Preview System v2.0 completely removed
- Dead code and orphaned event handlers cleaned up
- Maintained backward compatibility for settings
- Preserved container structure for smooth transition

### Performance Improvements
- Reduced complexity and potential bugs
- Better error handling and recovery
- More efficient update mechanisms
- Cleaner codebase maintenance

## API Reference

### JavaScript Events
```javascript
// Initialize preview system
initializePreviewSystem()

// Manual refresh
loadPreview()

// Update with current settings
updatePreview()

// Error handling
showPreviewError(message)
```

### AJAX Endpoints
```php
// Full preview render
wp_ajax_ai_interview_render_preview

// Incremental update
wp_ajax_ai_interview_update_preview
```

### Configuration Constants
```javascript
const PREVIEW_CONFIG = {
    initialized: false,
    iframe: null,
    updateTimeout: null,
    debounceDelay: 500,
    retryCount: 0,
    maxRetries: 3
}
```

This documentation provides a complete reference for understanding, maintaining, and extending the Widget Preview System.