class ConfirmDialog {
  constructor(message, title = 'Confirm', options = {}, callback = null) {
    this.message = message;
    this.title = title;
    this.callback = callback;

    this.options = {
      confirmText: 'Yes',
      cancelText: 'No',
      confirmClass: 'popup-btn-primary',
      cancelClass: 'popup-btn-secondary',
      type: 'warning',
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

  getIconClasses() {
    const classes = {
      warning: 'bg-yellow-100 text-yellow-500',
      danger: 'bg-red-100 text-red-500',
      info: 'bg-blue-100 text-blue-500'
    };
    return classes[this.options.type] || classes.warning;
  }

  getButtonClasses() {
    const baseButtonClass = 'px-4 py-2 rounded-md font-medium text-sm transition-colors';

    let confirmButtonClass, cancelButtonClass;

    if (this.options.type === 'danger') {
      confirmButtonClass = `${baseButtonClass} bg-red-500 text-white hover:bg-red-600`;
    } else {
      confirmButtonClass = `${baseButtonClass} bg-blue-500 text-white hover:bg-blue-600`;
    }

    cancelButtonClass = `${baseButtonClass} bg-gray-200 text-gray-700 hover:bg-gray-300`;

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

  create() {
    const dialog = document.createElement('div');
    dialog.className = 'w-[400px] p-6';

    const { confirmButtonClass, cancelButtonClass } = this.getButtonClasses();

    const iconClass = `w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center ${this.getIconClasses()}`;

    dialog.innerHTML = `
      <div class="${iconClass}">
        ${this.getIcon()}
      </div>
      <h3 class="text-lg font-semibold text-center mb-2 text-gray-900">${this.escapeHtml(this.title)}</h3>
      <p class="text-center text-gray-500 mb-6 leading-relaxed">${this.escapeHtml(this.message)}</p>
      <div class="flex justify-center gap-3">
        <button class="${cancelButtonClass}" data-action="cancel">
          ${this.escapeHtml(this.options.cancelText)}
        </button>
        <button class="${confirmButtonClass}" data-action="confirm">
          ${this.escapeHtml(this.options.confirmText)}
        </button>
      </div>
    `;

    const overlay = window.popupSystem ?
      window.popupSystem.createOverlay(dialog.outerHTML) :
      this.createFallbackOverlay(dialog);

    // Handle button clicks
    overlay.addEventListener('click', (e) => {
      const button = e.target.closest('button[data-action]');
      if (button) {
        const action = button.getAttribute('data-action');
        if (action === 'confirm') {
          this.close(overlay, true);
        } else if (action === 'cancel') {
          this.close(overlay, false);
        }
      }
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

if (typeof module !== 'undefined' && module.exports) {
  module.exports = ConfirmDialog;
}
