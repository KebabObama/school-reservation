/**
 * Alert Dialog Component
 * Full-featured popup for displaying important messages to users
 */

class AlertDialog {
  constructor(message, title = 'Alert', type = 'info', callback = null) {
    this.message = message;
    this.title = title;
    this.type = type; // info, success, warning, error
    this.callback = callback;
  }

  getIcon() {
    const icons = {
      info: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                     d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
             </svg>`,
      success: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`,
      warning: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>`,
      error: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>`
    };
    return icons[this.type] || icons.info;
  }

  create() {
    const dialog = document.createElement('div');
    dialog.className = 'w-[400px] p-6';
    
    // Use Tailwind classes for the icon
    const iconClass = `w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center ${this.getTailwindIconClasses()}`;
    
    dialog.innerHTML = `
      <div class="${iconClass}">
        ${this.getIconSvg()}
      </div>
      <h3 class="text-lg font-semibold text-center mb-2 text-gray-900">${this.escapeHtml(this.title)}</h3>
      <p class="text-center text-gray-500 mb-6 leading-relaxed">${this.escapeHtml(this.message)}</p>
      <div class="flex justify-center">
        <button class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors font-medium text-sm">
          ${this.escapeHtml(this.buttonText)}
        </button>
      </div>
    `;
    
    // Handle OK button click
    dialog.querySelector('button').addEventListener('click', () => {
      this.close(dialog);
    });
    
    // Handle escape key
    const handleKeydown = (e) => {
      if (e.key === 'Escape') {
        this.close(dialog);
        document.removeEventListener('keydown', handleKeydown);
      }
    };
    document.addEventListener('keydown', handleKeydown);
    
    // Close on overlay click
    dialog.addEventListener('click', (e) => {
      if (e.target === dialog) {
        this.close(dialog);
      }
    });
    
    return dialog;
  }

  close(dialog) {
    if (this.callback) {
      this.callback(true);
    }
    
    if (window.popupSystem) {
      window.popupSystem.closePopup(dialog);
    } else {
      // Fallback if popup system not available
      dialog.classList.remove('show');
      setTimeout(() => {
        if (dialog.parentNode) {
          dialog.parentNode.removeChild(dialog);
        }
      }, 300);
    }
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  getTailwindIconClasses() {
    const classes = {
      info: 'bg-blue-100 text-blue-500',
      success: 'bg-green-100 text-green-500',
      warning: 'bg-yellow-100 text-yellow-500',
      error: 'bg-red-100 text-red-500'
    };
    return classes[this.type] || classes.info;
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = AlertDialog;
}

