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
    // Create dialog content with Tailwind classes for consistent styling
    const dialog = document.createElement('div');
    dialog.className = 'w-[400px] p-6';

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

    // Get button styles
    const { confirmButtonClass, cancelButtonClass } = this.getButtonClasses();

    dialog.innerHTML = `
      <h3 class="text-lg font-semibold text-center mb-2 text-gray-900">${this.escapeHtml(this.title)}</h3>
      <p class="text-center text-gray-500 mb-4 leading-relaxed">${this.escapeHtml(this.message)}</p>
      <input class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-6" ${inputAttributes} />
      <div class="flex justify-center gap-3">
        <button class="${cancelButtonClass}" data-action="cancel">
          ${this.escapeHtml(this.options.cancelText)}
        </button>
        <button class="${confirmButtonClass}" data-action="confirm">
          ${this.escapeHtml(this.options.confirmText)}
        </button>
      </div>
    `;

    // Use PopupSystem's createOverlay for consistent centering
    const overlay = window.popupSystem ?
      window.popupSystem.createOverlay(dialog.outerHTML) :
      this.createFallbackOverlay(dialog);
    
    const input = overlay.querySelector('input');
    const okButton = overlay.querySelector('button:last-child');
    
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
    overlay.addEventListener('click', (e) => {
      const button = e.target.closest('button[data-action]');
      if (button) {
        const action = button.getAttribute('data-action');
        if (action === 'confirm') {
          this.handleOk(overlay, input);
        } else if (action === 'cancel') {
          this.close(overlay, null);
        }
      }
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
    error.className = 'input-error text-red-600 text-sm mt-1 mb-4';
    error.textContent = message;

    input.parentNode.insertBefore(error, input.nextSibling);

    // Add error styling to input using Tailwind classes
    input.className = input.className.replace('border-gray-300', 'border-red-500');
    input.className = input.className.replace('focus:ring-blue-500', 'focus:ring-red-500');
    input.focus();

    // Remove error styling on input
    const removeError = () => {
      input.className = input.className.replace('border-red-500', 'border-gray-300');
      input.className = input.className.replace('focus:ring-red-500', 'focus:ring-blue-500');
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

  getButtonClasses() {
    const baseButtonClass = 'px-4 py-2 rounded-md font-medium text-sm transition-colors';

    const confirmButtonClass = `${baseButtonClass} bg-blue-500 text-white hover:bg-blue-600`;
    const cancelButtonClass = `${baseButtonClass} bg-gray-200 text-gray-700 hover:bg-gray-300`;

    return { confirmButtonClass, cancelButtonClass };
  }

  createFallbackOverlay(dialog) {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[1000] opacity-0 transition-opacity duration-300';

    const popupContent = document.createElement('div');
    popupContent.className = 'bg-white rounded-lg shadow-xl max-w-[90vw] max-h-[90vh] overflow-auto transform scale-95 transition-transform duration-300';
    popupContent.appendChild(dialog);

    overlay.appendChild(popupContent);
    return overlay;
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
