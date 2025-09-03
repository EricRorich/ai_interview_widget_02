/**
 * Frontend JavaScript Entry Point
 * 
 * Main JavaScript file for frontend functionality.
 * 
 * @package AIInterviewWidget
 * @since 2.0.0
 */

import '../css/frontend.css'

// Main frontend functionality
class AIInterviewWidgetFrontend {
  constructor() {
    this.initializeWidget = this.initializeWidget.bind(this)
    this.handleChatSubmit = this.handleChatSubmit.bind(this)
    this.handleVoiceToggle = this.handleVoiceToggle.bind(this)
    
    this.init()
  }

  init() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', this.initializeWidget)
    } else {
      this.initializeWidget()
    }
  }

  initializeWidget() {
    const widgets = document.querySelectorAll('.aiw-widget')
    
    widgets.forEach(widget => {
      this.setupWidget(widget)
    })
  }

  setupWidget(widget) {
    // Setup chat functionality
    const chatForm = widget.querySelector('.aiw-chat-form')
    const messageInput = widget.querySelector('.aiw-message-input')
    const sendButton = widget.querySelector('.aiw-send-button')
    const voiceToggle = widget.querySelector('.aiw-voice-toggle')
    
    if (chatForm && messageInput && sendButton) {
      chatForm.addEventListener('submit', this.handleChatSubmit)
      
      // Enable send button when there's text
      messageInput.addEventListener('input', (e) => {
        sendButton.disabled = !e.target.value.trim()
      })
      
      // Enter key support
      messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault()
          this.handleChatSubmit(e)
        }
      })
    }
    
    if (voiceToggle) {
      voiceToggle.addEventListener('click', this.handleVoiceToggle)
    }
    
    // Initialize animations
    this.initializeAnimations(widget)
  }

  async handleChatSubmit(e) {
    e.preventDefault()
    
    const form = e.target.closest('.aiw-chat-form') || e.target.closest('.aiw-widget').querySelector('.aiw-chat-form')
    const messageInput = form.querySelector('.aiw-message-input')
    const sendButton = form.querySelector('.aiw-send-button')
    const messagesContainer = form.closest('.aiw-widget').querySelector('.aiw-messages')
    
    const message = messageInput.value.trim()
    if (!message) return
    
    // Disable form during request
    messageInput.disabled = true
    sendButton.disabled = true
    
    // Add user message to chat
    this.addMessageToChat(messagesContainer, message, 'user')
    
    // Clear input
    messageInput.value = ''
    
    // Show typing indicator
    const typingIndicator = this.showTypingIndicator(messagesContainer)
    
    try {
      const response = await this.sendChatMessage(message)
      
      // Remove typing indicator
      if (typingIndicator) {
        typingIndicator.remove()
      }
      
      // Add AI response to chat
      this.addMessageToChat(messagesContainer, response.data.response, 'assistant')
      
    } catch (error) {
      console.error('Chat error:', error)
      
      // Remove typing indicator
      if (typingIndicator) {
        typingIndicator.remove()
      }
      
      // Show error message
      this.addMessageToChat(messagesContainer, window.aiwFrontend?.i18n?.error || 'An error occurred. Please try again.', 'error')
    }
    
    // Re-enable form
    messageInput.disabled = false
    sendButton.disabled = false
    messageInput.focus()
  }

  async sendChatMessage(message) {
    const formData = new FormData()
    formData.append('action', 'ai_interview_chat')
    formData.append('message', message)
    formData.append('nonce', window.aiwFrontend?.nonce || '')
    
    const response = await fetch(window.aiwFrontend?.ajaxUrl || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      body: formData
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    return await response.json()
  }

  addMessageToChat(container, message, type) {
    const messageElement = document.createElement('div')
    messageElement.className = `aiw-message aiw-message-${type}`
    messageElement.innerHTML = `
      <div class="aiw-message-content">
        ${this.escapeHtml(message)}
      </div>
      <div class="aiw-message-time">
        ${new Date().toLocaleTimeString()}
      </div>
    `
    
    container.appendChild(messageElement)
    
    // Scroll to bottom
    container.scrollTop = container.scrollHeight
    
    // Animate in
    requestAnimationFrame(() => {
      messageElement.classList.add('aiw-message-visible')
    })
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

  handleVoiceToggle(e) {
    const button = e.target.closest('.aiw-voice-toggle')
    const widget = button.closest('.aiw-widget')
    
    widget.classList.toggle('aiw-voice-enabled')
    
    const isEnabled = widget.classList.contains('aiw-voice-enabled')
    button.textContent = isEnabled ? 'Disable Voice' : 'Enable Voice'
    button.setAttribute('aria-pressed', isEnabled)
  }

  initializeAnimations(widget) {
    const animationType = widget.dataset.animation || 'fadeIn'
    
    if (animationType && animationType !== 'none') {
      widget.classList.add('aiw-animate', `aiw-animate-${animationType}`)
    }
  }

  escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
  }
}

// Initialize when script loads
new AIInterviewWidgetFrontend()

// Export for external use
window.AIInterviewWidgetFrontend = AIInterviewWidgetFrontend