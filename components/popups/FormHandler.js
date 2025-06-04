/**
 * Form Handler Utility
 * Provides easy conversion from traditional forms to AJAX submissions
 */

class FormHandler {
  constructor() {
    this.defaultOptions = {
      showLoading: true,
      preventRefresh: true,
      successMessage: 'Operation completed successfully!',
      errorMessage: 'An error occurred',
      redirectOnSuccess: null,
      reloadOnSuccess: false,
      validateBeforeSubmit: true
    };
  }

  /**
   * Convert a traditional form to AJAX submission
   * @param {string|HTMLElement} formSelector - Form selector or element
   * @param {string} apiEndpoint - API endpoint URL
   * @param {Object} options - Configuration options
   */
  convertForm(formSelector, apiEndpoint, options = {}) {
    const form = typeof formSelector === 'string' 
      ? document.querySelector(formSelector) 
      : formSelector;
    
    if (!form) {
      console.error('Form not found:', formSelector);
      return;
    }

    const config = { ...this.defaultOptions, ...options };

    // Prevent default form submission
    if (config.preventRefresh) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.submitForm(form, apiEndpoint, config);
      });
    }

    // Add submit button handler if it's a button type
    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
    if (submitButton) {
      submitButton.addEventListener('click', (e) => {
        if (config.preventRefresh) {
          e.preventDefault();
          this.submitForm(form, apiEndpoint, config);
        }
      });
    }

    return this;
  }

  /**
   * Submit form via AJAX
   * @param {HTMLElement} form - Form element
   * @param {string} apiEndpoint - API endpoint URL
   * @param {Object} config - Configuration options
   */
  async submitForm(form, apiEndpoint, config) {
    try {
      // Validate form if required
      if (config.validateBeforeSubmit && !this.validateForm(form)) {
        return;
      }

      // Show loading state
      if (config.showLoading) {
        this.setLoadingState(form, true);
      }

      // Collect form data
      const formData = this.collectFormData(form);
      
      // Submit to API
      const response = await fetch(apiEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData),
        credentials: 'same-origin'
      });

      const result = await response.json();

      if (response.ok) {
        // Success handling
        if (window.popupSystem) {
          popupSystem.success(config.successMessage);
        } else {
          alert(config.successMessage);
        }

        // Post-success actions
        if (config.redirectOnSuccess) {
          setTimeout(() => {
            if (typeof config.redirectOnSuccess === 'function') {
              config.redirectOnSuccess();
            } else {
              window.location.href = config.redirectOnSuccess;
            }
          }, 1500);
        } else if (config.reloadOnSuccess) {
          setTimeout(() => {
            location.reload();
          }, 1500);
        }

        // Call success callback if provided
        if (config.onSuccess) {
          config.onSuccess(result, form);
        }

      } else {
        // Error handling
        const errorMsg = result.error || config.errorMessage;
        if (window.popupSystem) {
          popupSystem.error(errorMsg);
        } else {
          alert('Error: ' + errorMsg);
        }

        // Call error callback if provided
        if (config.onError) {
          config.onError(result, form);
        }
      }

    } catch (error) {
      // Network error handling
      const errorMsg = 'Network error: ' + error.message;
      if (window.popupSystem) {
        popupSystem.error(errorMsg);
      } else {
        alert(errorMsg);
      }

      // Call error callback if provided
      if (config.onError) {
        config.onError({ error: error.message }, form);
      }

    } finally {
      // Remove loading state
      if (config.showLoading) {
        this.setLoadingState(form, false);
      }
    }
  }

  /**
   * Collect form data as JSON object
   * @param {HTMLElement} form - Form element
   * @returns {Object} Form data as object
   */
  collectFormData(form) {
    const formData = new FormData(form);
    const data = {};

    // Handle regular form fields
    for (let [key, value] of formData.entries()) {
      if (key.endsWith('[]')) {
        // Handle array fields (like checkboxes with same name)
        const arrayKey = key.slice(0, -2);
        if (!data[arrayKey]) data[arrayKey] = [];
        data[arrayKey].push(value);
      } else {
        data[key] = value;
      }
    }

    // Handle checkboxes that aren't checked (they won't be in FormData)
    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
      if (!checkbox.name.endsWith('[]') && !formData.has(checkbox.name)) {
        data[checkbox.name] = false;
      } else if (!checkbox.name.endsWith('[]')) {
        data[checkbox.name] = checkbox.checked;
      }
    });

    return data;
  }

  /**
   * Basic form validation
   * @param {HTMLElement} form - Form element
   * @returns {boolean} Whether form is valid
   */
  validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
      if (!field.value.trim()) {
        this.showFieldError(field, 'This field is required');
        isValid = false;
      } else {
        this.clearFieldError(field);
      }
    });

    // Check HTML5 validity
    if (!form.checkValidity()) {
      isValid = false;
    }

    return isValid;
  }

  /**
   * Show error for a specific field
   * @param {HTMLElement} field - Form field element
   * @param {string} message - Error message
   */
  showFieldError(field, message) {
    // Remove existing error
    this.clearFieldError(field);

    // Add error styling
    field.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');

    // Add error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error text-red-600 text-sm mt-1';
    errorDiv.textContent = message;
    
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
  }

  /**
   * Clear error for a specific field
   * @param {HTMLElement} field - Form field element
   */
  clearFieldError(field) {
    // Remove error styling
    field.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');

    // Remove error message
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
      existingError.remove();
    }
  }

  /**
   * Set loading state for form
   * @param {HTMLElement} form - Form element
   * @param {boolean} loading - Whether form is loading
   */
  setLoadingState(form, loading) {
    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
    
    if (loading) {
      // Disable form
      form.classList.add('opacity-75', 'pointer-events-none');
      
      // Update submit button
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.dataset.originalText = submitButton.textContent;
        submitButton.textContent = 'Loading...';
      }
    } else {
      // Enable form
      form.classList.remove('opacity-75', 'pointer-events-none');
      
      // Restore submit button
      if (submitButton) {
        submitButton.disabled = false;
        if (submitButton.dataset.originalText) {
          submitButton.textContent = submitButton.dataset.originalText;
          delete submitButton.dataset.originalText;
        }
      }
    }
  }

  /**
   * Quick setup for common form patterns
   */
  static quickSetup() {
    const handler = new FormHandler();

    // Auto-convert forms with data-ajax attribute
    document.querySelectorAll('form[data-ajax]').forEach(form => {
      const endpoint = form.dataset.ajax;
      const successMessage = form.dataset.successMessage || 'Operation completed successfully!';
      const redirectTo = form.dataset.redirectTo;
      const reloadOnSuccess = form.dataset.reloadOnSuccess === 'true';

      handler.convertForm(form, endpoint, {
        successMessage,
        redirectOnSuccess: redirectTo,
        reloadOnSuccess
      });
    });

    return handler;
  }
}

// Auto-initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
  FormHandler.quickSetup();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = FormHandler;
}

// Make available globally
window.FormHandler = FormHandler;
