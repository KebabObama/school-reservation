/**
 * Confirm Dialog Component
 * Full-featured popup for user confirmations with Yes/No or custom buttons
 */

class ConfirmDialog {
  constructor(message, title = 'Confirm', options = {}, callback = null) {
    this.message = message;
    this.title = title;
    this.callback = callback;
    
    // Default options
    this.options = {
      confirmText: 'Yes',
      cancelText: 'No',
      confirmClass: 'popup-btn-primary',
      cancelClass: 'popup-btn-secondary',
      type: 'warning', // warning, danger, info
      ...options
    };
  }

  getIcon() {
    const icons = {
      warning: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>`,
      danger: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                       d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
               </svg>`,
      info: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                     d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
             </svg>`
    };
    return icons[this.options.type] || icons.warning;
  }

  create() {
    const overlay = document.createElement('div');
    overlay.className = 'popup-overlay';
    
    const content = document.createElement('div');
    content.className = 'popup-content confirm-dialog';
    
    // Adjust button classes for danger type
    let confirmClass = this.options.confirmClass;
    if (this.options.type === 'danger') {
      confirmClass = 'popup-btn-danger';
    }
    
    content.innerHTML = `
      <div class="confirm-icon">
        ${this.getIcon()}
      </div>
      <div class="confirm-title">${this.escapeHtml(this.title)}</div>
      <div class="confirm-message">${this.escapeHtml(this.message)}</div>
      <div class="confirm-actions">
        <button class="popup-btn ${this.options.cancelClass}" onclick="this.closest('.popup-overlay').dispatchEvent(new CustomEvent('confirm-cancel'))">
          ${this.escapeHtml(this.options.cancelText)}
        </button>
        <button class="popup-btn ${confirmClass}" onclick="this.closest('.popup-overlay').dispatchEvent(new CustomEvent('confirm-ok'))">
          ${this.escapeHtml(this.options.confirmText)}
        </button>
      </div>
    `;
    
    overlay.appendChild(content);
    
    // Handle button clicks
    overlay.addEventListener('confirm-ok', () => {
      this.close(overlay, true);
    });
    
    overlay.addEventListener('confirm-cancel', () => {
      this.close(overlay, false);
    });
    
    // Handle escape key (defaults to cancel)
    const handleKeydown = (e) => {
      if (e.key === 'Escape') {
        this.close(overlay, false);
        document.removeEventListener('keydown', handleKeydown);
      }
    };
    document.addEventListener('keydown', handleKeydown);
    
    // Close on overlay click (defaults to cancel)
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        this.close(overlay, false);
      }
    });
    
    return overlay;
  }

  close(overlay, result) {
    if (this.callback) {
      this.callback(result);
    }
    
    if (window.popupSystem) {
      window.popupSystem.closePopup(overlay);
    } else {
      // Fallback if popup system not available
      overlay.classList.remove('show');
      setTimeout(() => {
        if (overlay.parentNode) {
          overlay.parentNode.removeChild(overlay);
        }
      }, 300);
    }
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = ConfirmDialog;
}
