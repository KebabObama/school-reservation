class AlertDialog {
  constructor(message, title = 'Alert', type = 'info', callback = null) {
    this.message = message;
    this.title = title;
    this.type = type;
    this.callback = callback;
    this.buttonText = 'OK';
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
        ${this.getIcon()}
      </div>
      <h3 class="text-lg font-semibold text-center mb-2 text-gray-900">${this.escapeHtml(this.title)}</h3>
      <p class="text-center text-gray-500 mb-6 leading-relaxed">${this.escapeHtml(this.message)}</p>
      <div class="flex justify-center">
        <button class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors font-medium text-sm" data-action="ok">
          ${this.escapeHtml(this.buttonText)}
        </button>
      </div>
    `;

    const overlay = window.popupSystem ?
      window.popupSystem.createOverlay(dialog.outerHTML) :
      this.createFallbackOverlay(dialog);

    overlay.addEventListener('click', (e) => {
      const button = e.target.closest('button[data-action="ok"]');
      if (button) {
        this.close(overlay);
      }
    });

    const handleKeydown = (e) => {
      if (e.key === 'Escape') {
        this.close(overlay);
        document.removeEventListener('keydown', handleKeydown);
      }
    };
    document.addEventListener('keydown', handleKeydown);

    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        this.close(overlay);
      }
    });

    return overlay;
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

  close(overlay) {
    if (this.callback) {
      this.callback(true);
    }

    if (window.popupSystem) {
      window.popupSystem.closePopup(overlay);
    } else {
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

if (typeof module !== 'undefined' && module.exports) {
  module.exports = AlertDialog;
}

