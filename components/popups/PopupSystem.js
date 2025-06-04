/**
 * Custom Popup System
 * Replaces native JavaScript alert(), confirm(), and prompt() functions
 */

class PopupSystem {
  constructor() {
    this.popupContainer = null;
    this.notificationContainer = null;
    this.activePopups = new Set();
    this.notificationQueue = [];
    this.maxNotifications = 5;
    this.init();
  }

  init() {
    // Get or create containers
    this.popupContainer = document.getElementById('popup-container');
    this.notificationContainer = document.getElementById('notification-container');
    
    if (!this.popupContainer) {
      this.popupContainer = document.createElement('div');
      this.popupContainer.id = 'popup-container';
      document.body.appendChild(this.popupContainer);
    }
    
    if (!this.notificationContainer) {
      this.notificationContainer = document.createElement('div');
      this.notificationContainer.id = 'notification-container';
      this.notificationContainer.className = 'fixed z-[9999] w-80 fixed right-0 p-1 flex flex-col-reverse gap-2 pointer-events-none items-end';
      document.body.appendChild(this.notificationContainer);
    }

    // Handle escape key for closing popups
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.activePopups.size > 0) {
        this.closeTopPopup();
      }
    });
  }

  // Create popup overlay
  createOverlay(content, className = '') {
    const overlay = document.createElement('div');
    overlay.className = `fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[1000] opacity-0 transition-opacity duration-300 ${className}`;
    
    const popupContent = document.createElement('div');
    popupContent.className = 'bg-white rounded-lg shadow-xl max-w-[90vw] max-h-[90vh] overflow-auto transform scale-95 transition-transform duration-300';
    popupContent.innerHTML = content;
    
    overlay.appendChild(popupContent);
    
    // Close on overlay click (but not on content click)
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        this.closePopup(overlay);
      }
    });
    
    return overlay;
  }

  // Show popup
  showPopup(overlay) {
    this.popupContainer.appendChild(overlay);
    this.activePopups.add(overlay);
    
    // Trigger animation
    requestAnimationFrame(() => {
      overlay.classList.add('show');
    });
    
    return overlay;
  }

  // Close specific popup
  closePopup(overlay) {
    if (!overlay || !this.activePopups.has(overlay)) return;
    
    overlay.classList.remove('show');
    this.activePopups.delete(overlay);
    
    setTimeout(() => {
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
    }, 300);
  }

  // Close the topmost popup
  closeTopPopup() {
    const popups = Array.from(this.activePopups);
    if (popups.length > 0) {
      this.closePopup(popups[popups.length - 1]);
    }
  }

  // Show notification card
  showNotification(message, type = 'info', title = '', duration = 5000) {
    // Remove oldest notification if we have too many
    const existingNotifications = this.notificationContainer.children;
    if (existingNotifications.length >= this.maxNotifications) {
      const oldest = existingNotifications[0];
      this.removeNotification(oldest);
    }

    const notification = new NotificationCard(message, type, title, duration);
    const element = notification.create();
    
    this.notificationContainer.appendChild(element);
    
    // Trigger animation - important to use requestAnimationFrame to ensure the transition works
    requestAnimationFrame(() => {
      element.classList.remove('translate-x-full');
      element.classList.add('translate-x-0');
    });

    // Auto-remove after duration
    if (duration > 0) {
      setTimeout(() => {
        this.removeNotification(element);
      }, duration);
    }

    return element;
  }

  // Remove notification
  removeNotification(element) {
    if (!element || !element.parentNode) return;
    
    element.classList.remove('translate-x-0');
    element.classList.add('translate-x-full');
    setTimeout(() => {
      if (element.parentNode) {
        element.parentNode.removeChild(element);
      }
    }, 300);
  }

  // Clear all notifications
  clearNotifications() {
    const notifications = Array.from(this.notificationContainer.children);
    notifications.forEach(notification => {
      this.removeNotification(notification);
    });
  }

  // Alert dialog
  alert(message, title = 'Alert', type = 'info') {
    return new Promise((resolve) => {
      const dialog = new AlertDialog(message, title, type, resolve);
      const overlay = dialog.create();
      this.showPopup(overlay);
    });
  }

  // Confirm dialog
  confirm(message, title = 'Confirm', options = {}) {
    return new Promise((resolve) => {
      const dialog = new ConfirmDialog(message, title, options, resolve);
      const overlay = dialog.create();
      this.showPopup(overlay);
    });
  }

  // Input dialog
  prompt(message, title = 'Input Required', defaultValue = '', options = {}) {
    return new Promise((resolve) => {
      const dialog = new InputDialog(message, title, defaultValue, options, resolve);
      const overlay = dialog.create();
      this.showPopup(overlay);
    });
  }

  // Success notification shorthand
  success(message, title = 'Success') {
    return this.showNotification(message, 'success', title);
  }

  // Error notification shorthand
  error(message, title = 'Error') {
    return this.showNotification(message, 'error', title);
  }

  // Warning notification shorthand
  warning(message, title = 'Warning') {
    return this.showNotification(message, 'warning', title);
  }

  // Info notification shorthand
  info(message, title = 'Information') {
    return this.showNotification(message, 'info', title);
  }
}

// Create global instance
window.popupSystem = new PopupSystem();

// Override native functions for backward compatibility
window.alert = function(message) {
  return window.popupSystem.alert(message);
};

window.confirm = function(message) {
  return window.popupSystem.confirm(message);
};

window.prompt = function(message, defaultValue = '') {
  return window.popupSystem.prompt(message, 'Input Required', defaultValue);
};

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = PopupSystem;
}


