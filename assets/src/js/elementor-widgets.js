/**
 * Elementor Widgets JavaScript Entry Point
 * 
 * JavaScript for Elementor widget functionality.
 * 
 * @package AIInterviewWidget
 * @since 2.0.0
 */

import '../css/elementor-widgets.css'

// Elementor widget functionality
class AIInterviewElementorWidgets {
  constructor() {
    this.init()
  }

  init() {
    // Initialize when Elementor frontend is ready
    if (typeof elementorFrontend !== 'undefined') {
      elementorFrontend.hooks.addAction('frontend/element_ready/ai_interview_widget.default', this.initInterviewWidget.bind(this))
      elementorFrontend.hooks.addAction('frontend/element_ready/ai_interview_list_widget.default', this.initListWidget.bind(this))
    }
    
    // Fallback for non-Elementor pages
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.initializeWidgets())
    } else {
      this.initializeWidgets()
    }
  }

  initializeWidgets() {
    const interviewWidgets = document.querySelectorAll('.aiw-interview-widget-elementor')
    const listWidgets = document.querySelectorAll('.aiw-interview-list-widget-elementor')
    
    interviewWidgets.forEach(widget => this.initInterviewWidget(widget))
    listWidgets.forEach(widget => this.initListWidget(widget))
  }

  initInterviewWidget($scope) {
    const widget = $scope.get ? $scope.get(0) : $scope
    const chatContainer = widget.querySelector('.aiw-chat-container')
    
    if (!chatContainer) return
    
    // Initialize chat functionality
    this.setupChatInterface(chatContainer)
    
    // Initialize voice features if enabled
    const voiceToggle = widget.querySelector('.aiw-voice-toggle')
    if (voiceToggle) {
      this.setupVoiceFeatures(widget)
    }
    
    // Setup auto-scroll
    const autoScroll = widget.dataset.autoScroll !== 'false'
    if (autoScroll) {
      this.setupAutoScroll(chatContainer)
    }
  }

  initListWidget($scope) {
    const widget = $scope.get ? $scope.get(0) : $scope
    const topicItems = widget.querySelectorAll('.aiw-topic-item')
    
    topicItems.forEach(item => {
      this.setupTopicItem(item)
    })
    
    // Setup layout-specific functionality
    const layout = widget.dataset.layout || 'cards'
    this.setupLayoutFeatures(widget, layout)
  }

  setupChatInterface(container) {
    const messageInput = container.querySelector('.aiw-message-input')
    const sendButton = container.querySelector('.aiw-send-button')
    const messagesArea = container.querySelector('.aiw-messages')
    
    if (!messageInput || !sendButton || !messagesArea) return
    
    // Enable send button when there's text
    messageInput.addEventListener('input', (e) => {
      sendButton.disabled = !e.target.value.trim()
    })
    
    // Handle send button click
    sendButton.addEventListener('click', (e) => {
      e.preventDefault()
      this.handleSendMessage(messageInput, messagesArea)
    })
    
    // Handle enter key
    messageInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault()
        this.handleSendMessage(messageInput, messagesArea)
      }
    })
  }

  async handleSendMessage(input, messagesArea) {
    const message = input.value.trim()
    if (!message) return
    
    // Disable input during request
    input.disabled = true
    
    // Add user message
    this.addMessage(messagesArea, message, 'user')
    
    // Clear input
    input.value = ''
    
    // Show typing indicator
    const typingIndicator = this.showTypingIndicator(messagesArea)
    
    try {
      const response = await this.sendMessage(message)
      
      // Remove typing indicator
      if (typingIndicator) {
        typingIndicator.remove()
      }
      
      // Add AI response
      this.addMessage(messagesArea, response.data.response, 'assistant')
      
    } catch (error) {
      console.error('Elementor chat error:', error)
      
      // Remove typing indicator
      if (typingIndicator) {
        typingIndicator.remove()
      }
      
      // Show error
      const errorMessage = window.aiwElementor?.i18n?.error || 'An error occurred. Please try again.'
      this.addMessage(messagesArea, errorMessage, 'error')
    }
    
    // Re-enable input
    input.disabled = false
    input.focus()
  }

  async sendMessage(message) {
    const formData = new FormData()
    formData.append('action', 'ai_interview_chat')
    formData.append('message', message)
    formData.append('nonce', window.aiwElementor?.nonce || '')
    
    const response = await fetch(window.aiwElementor?.ajaxUrl || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      body: formData
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    return await response.json()
  }

  addMessage(container, text, type) {
    const message = document.createElement('div')
    message.className = `aiw-message aiw-message-${type}`
    message.innerHTML = `
      <div class="aiw-message-content">${this.escapeHtml(text)}</div>
      <div class="aiw-message-time">${new Date().toLocaleTimeString()}</div>
    `
    
    container.appendChild(message)
    
    // Animate in
    requestAnimationFrame(() => {
      message.classList.add('aiw-message-visible')
    })
    
    // Scroll to bottom
    container.scrollTop = container.scrollHeight
  }

  showTypingIndicator(container) {
    const indicator = document.createElement('div')
    indicator.className = 'aiw-message aiw-message-assistant aiw-typing-indicator'
    indicator.innerHTML = `
      <div class="aiw-message-content">
        <div class="aiw-typing-dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    `
    
    container.appendChild(indicator)
    container.scrollTop = container.scrollHeight
    
    return indicator
  }

  setupVoiceFeatures(widget) {
    const voiceToggle = widget.querySelector('.aiw-voice-toggle')
    
    if (voiceToggle) {
      voiceToggle.addEventListener('click', (e) => {
        e.preventDefault()
        widget.classList.toggle('aiw-voice-enabled')
        
        const isEnabled = widget.classList.contains('aiw-voice-enabled')
        voiceToggle.textContent = isEnabled ? 'Disable Voice' : 'Enable Voice'
      })
    }
  }

  setupAutoScroll(container) {
    const observer = new MutationObserver(() => {
      container.scrollTop = container.scrollHeight
    })
    
    observer.observe(container, {
      childList: true,
      subtree: true
    })
  }

  setupTopicItem(item) {
    const title = item.querySelector('.aiw-topic-title')
    
    if (title) {
      title.addEventListener('click', () => {
        item.classList.toggle('aiw-topic-expanded')
      })
    }
  }

  setupLayoutFeatures(widget, layout) {
    if (layout === 'accordion') {
      // Setup accordion functionality
      const items = widget.querySelectorAll('.aiw-topic-item')
      
      items.forEach(item => {
        const title = item.querySelector('.aiw-topic-title')
        
        if (title) {
          title.addEventListener('click', () => {
            // Close other items
            items.forEach(otherItem => {
              if (otherItem !== item) {
                otherItem.classList.remove('aiw-topic-expanded')
              }
            })
            
            // Toggle current item
            item.classList.toggle('aiw-topic-expanded')
          })
        }
      })
    }
  }

  escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
  }
}

// Initialize when script loads
new AIInterviewElementorWidgets()

// Export for external use
window.AIInterviewElementorWidgets = AIInterviewElementorWidgets