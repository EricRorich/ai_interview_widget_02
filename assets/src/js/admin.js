/**
 * Admin JavaScript Entry Point
 * 
 * Main JavaScript file for admin functionality.
 * 
 * @package AIInterviewWidget
 * @since 2.0.0
 */

import '../css/admin.css'

// Main admin functionality
class AIInterviewWidgetAdmin {
  constructor() {
    this.init()
  }

  init() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.initializeAdmin())
    } else {
      this.initializeAdmin()
    }
  }

  initializeAdmin() {
    this.setupColorPickers()
    this.setupTabs()
    this.setupProviderSelection()
    this.setupFormValidation()
    this.setupResetButtons()
  }

  setupColorPickers() {
    // Initialize WordPress color picker if available
    if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
      jQuery('.aiw-color-picker').wpColorPicker()
    }
  }

  setupTabs() {
    const tabButtons = document.querySelectorAll('.aiw-tab-button')
    const tabPanes = document.querySelectorAll('.aiw-tab-pane')
    
    tabButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault()
        
        const targetTab = button.dataset.tab
        
        // Update active states
        tabButtons.forEach(btn => btn.classList.remove('active'))
        tabPanes.forEach(pane => pane.classList.remove('active'))
        
        button.classList.add('active')
        const targetPane = document.getElementById(targetTab)
        if (targetPane) {
          targetPane.classList.add('active')
        }
      })
    })
  }

  setupProviderSelection() {
    const providerSelect = document.querySelector('#ai_provider')
    const providerOptions = document.querySelectorAll('.aiw-provider-options')
    
    if (providerSelect) {
      providerSelect.addEventListener('change', (e) => {
        const selectedProvider = e.target.value
        
        providerOptions.forEach(option => {
          if (option.dataset.provider === selectedProvider) {
            option.style.display = 'block'
          } else {
            option.style.display = 'none'
          }
        })
      })
      
      // Trigger on page load
      providerSelect.dispatchEvent(new Event('change'))
    }
  }

  setupFormValidation() {
    const forms = document.querySelectorAll('.aiw-admin-form')
    
    forms.forEach(form => {
      form.addEventListener('submit', (e) => {
        if (!this.validateForm(form)) {
          e.preventDefault()
        }
      })
    })
  }

  validateForm(form) {
    let isValid = true
    const requiredFields = form.querySelectorAll('[required]')
    
    requiredFields.forEach(field => {
      if (!field.value.trim()) {
        this.showFieldError(field, 'This field is required')
        isValid = false
      } else {
        this.clearFieldError(field)
      }
    })
    
    return isValid
  }

  showFieldError(field, message) {
    this.clearFieldError(field)
    
    const errorDiv = document.createElement('div')
    errorDiv.className = 'aiw-field-error'
    errorDiv.textContent = message
    
    field.parentNode.appendChild(errorDiv)
    field.classList.add('error')
  }

  clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.aiw-field-error')
    if (existingError) {
      existingError.remove()
    }
    field.classList.remove('error')
  }

  setupResetButtons() {
    const resetButtons = document.querySelectorAll('.aiw-reset-button')
    
    resetButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault()
        
        const confirmMessage = button.dataset.confirm || 'Are you sure you want to reset this setting?'
        
        if (confirm(confirmMessage)) {
          const targetField = document.querySelector(button.dataset.target)
          const defaultValue = button.dataset.default || ''
          
          if (targetField) {
            targetField.value = defaultValue
            
            // Trigger change event
            targetField.dispatchEvent(new Event('change'))
          }
        }
      })
    })
  }
}

// Initialize when script loads
new AIInterviewWidgetAdmin()

// Export for external use
window.AIInterviewWidgetAdmin = AIInterviewWidgetAdmin