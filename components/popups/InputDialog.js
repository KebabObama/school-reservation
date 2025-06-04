/**
 * Input Dialog Component
 * Full-featured popup for collecting user input with validation
 */

class InputDialog {
  constructor(message, title = 'Input Required', defaultValue = '', options = {}, callback = null) {
    this.message = message;
    this.title = title;
    this.defaultValue = defaultValue;
    this.callback = callback;
    
    // Default options
    this.options = {
      placeholder: '',
      inputType: 'text', // text, password, email, number, etc.
      required: true,
      maxLength: null,
      minLength: null,
      pattern: null,
      confirmText: 'OK',
      cancelText: 'Cancel',
      confirmClass: 'popup-btn-primary',
      cancelClass: 'popup-btn-secondary',
      validator: null, // Custom validation function
      ...options
    };
  }

  create() {
    const overlay = document.createElement('div');
    overlay.className = 'popup-overlay';
    
    const content = document.createElement('div');
    content.className = 'popup-content input-dialog';
    
    // Build input attributes
    let inputAttributes = `
      type="${this.options.inputType}"
      placeholder="${this.escapeHtml(this.options.placeholder)}"
      value="${this.escapeHtml(this.defaultValue)}"
    `;
    
    if (this.options.required) {
      inputAttributes += ' required';
    }
    
    if (this.options.maxLength) {
      inputAttributes += ` maxlength="${this.options.maxLength}"`;
    }
    
    if (this.options.minLength) {
      inputAttributes += ` minlength="${this.options.minLength}"`;
    }
    
    if (this.options.pattern) {
      inputAttributes += ` pattern="${this.options.pattern}"`;
    }
    
    content.innerHTML = `
      <div class="input-title">${this.escapeHtml(this.title)}</div>
      <div class="input-message">${this.escapeHtml(this.message)}</div>
      <input class="input-field" ${inputAttributes} />
      <div class="input-actions">
        <button class="popup-btn ${this.options.cancelClass}" onclick="this.closest('.popup-overlay').dispatchEvent(new CustomEvent('input-cancel'))">
          ${this.escapeHtml(this.options.cancelText)}
        </button>
        <button class="popup-btn ${this.options.confirmClass}" onclick="this.closest('.popup-overlay').dispatchEvent(new CustomEvent('input-ok'))">
          ${this.escapeHtml(this.options.confirmText)}
        </button>
      </div>
    `;
    
    overlay.appendChild(content);
    
    const input = content.querySelector('.input-field');
    const okButton = content.querySelector('.popup-btn-primary, .popup-btn-success');
    
    // Focus the input field
    setTimeout(() => {
      input.focus();
      input.select();
    }, 100);
    
    // Handle Enter key in input
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        this.handleOk(overlay, input);
      }
    });
    
    // Handle button clicks
    overlay.addEventListener('input-ok', () => {
      this.handleOk(overlay, input);
    });
    
    overlay.addEventListener('input-cancel', () => {
      this.close(overlay, null);
    });
    
    // Handle escape key (defaults to cancel)
    const handleKeydown = (e) => {
      if (e.key === 'Escape') {
        this.close(overlay, null);
        document.removeEventListener('keydown', handleKeydown);
      }
    };
    document.addEventListener('keydown', handleKeydown);
    
    // Close on overlay click (defaults to cancel)
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        this.close(overlay, null);
      }
    });
    
    return overlay;
  }

  handleOk(overlay, input) {
    const value = input.value;
    
    // Basic validation
    if (this.options.required && !value.trim()) {
      this.showInputError(input, 'This field is required');
      return;
    }
    
    if (this.options.minLength && value.length < this.options.minLength) {
      this.showInputError(input, `Minimum length is ${this.options.minLength} characters`);
      return;
    }
    
    if (this.options.maxLength && value.length > this.options.maxLength) {
      this.showInputError(input, `Maximum length is ${this.options.maxLength} characters`);
      return;
    }
    
    // Custom validation
    if (this.options.validator) {
      const validationResult = this.options.validator(value);
      if (validationResult !== true) {
        this.showInputError(input, validationResult || 'Invalid input');
        return;
      }
    }
    
    // All validation passed
    this.close(overlay, value);
  }

  showInputError(input, message) {
    // Remove existing error
    const existingError = input.parentNode.querySelector('.input-error');
    if (existingError) {
      existingError.remove();
    }
    
    // Add error message
    const error = document.createElement('div');
    error.className = 'input-error';
    error.style.cssText = 'color: #dc2626; font-size: 0.875rem; margin-top: -1rem; margin-bottom: 1rem;';
    error.textContent = message;
    
    input.parentNode.insertBefore(error, input.nextSibling);
    
    // Add error styling to input
    input.style.borderColor = '#dc2626';
    input.focus();
    
    // Remove error styling on input
    const removeError = () => {
      input.style.borderColor = '';
      if (error.parentNode) {
        error.remove();
      }
      input.removeEventListener('input', removeError);
    };
    
    input.addEventListener('input', removeError);
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
  module.exports = InputDialog;
}
