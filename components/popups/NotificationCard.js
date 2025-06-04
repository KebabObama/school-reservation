/**
 * Notification Card Component
 * Small cards that appear in the bottom-right corner for informational messages
 */

class NotificationCard {
  constructor(message, type = 'info', title = '', duration = 5000) {
    this.message = message;
    this.type = type; // info, success, warning, error
    this.title = title;
    this.duration = duration;
  }

  getIcon() {
    const icons = {
      info: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                     d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
             </svg>`,
      success: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`,
      warning: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>`,
      error: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>`
    };
    return icons[this.type] || icons.info;
  }

  getDefaultTitle() {
    const titles = {
      info: 'Information',
      success: 'Success',
      warning: 'Warning',
      error: 'Error'
    };
    return titles[this.type] || 'Notification';
  }

  create() {
    const card = document.createElement('div');
    // Use Tailwind classes for positioning and animation
    card.className = `pointer-events-auto bg-white rounded-lg shadow-lg p-4 cursor-pointer border-l-4 ${this.getTailwindBorderColor()} transform translate-x-full transition-transform duration-300 ease-in-out`;
    
    const displayTitle = this.title || this.getDefaultTitle();
    
    // Use Tailwind classes for the inner structure
    card.innerHTML = `
      <div class="flex items-center justify-between mb-1">
        <div class="font-semibold text-sm text-gray-900">${this.escapeHtml(displayTitle)}</div>
        <button class="bg-transparent border-none text-gray-400 hover:text-gray-600 cursor-pointer p-0 flex items-center justify-center w-4 h-4">×</button>
      </div>
      <div class="text-sm text-gray-500 leading-relaxed">${this.escapeHtml(this.message)}</div>
    `;
    
    // Click to dismiss
    card.addEventListener('click', (e) => {
      if (!e.target.closest('button')) {
        this.remove(card);
      }
    });

    return card;
  }

  getTailwindBorderColor() {
    const colors = {
      info: 'border-blue-500',
      success: 'border-green-500',
      warning: 'border-yellow-500',
      error: 'border-red-500'
    };
    return colors[this.type] || 'border-blue-500';
  }

  remove(element) {
    if (element && element.parentNode) {
      element.classList.remove('translate-x-0');
      element.classList.add('translate-x-full');
      setTimeout(() => {
        if (element.parentNode) {
          element.parentNode.removeChild(element);
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
  module.exports = NotificationCard;
}


