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

  convertForm(formSelector, apiEndpoint, options = {}) {
    const form = typeof formSelector === 'string'
      ? document.querySelector(formSelector)
      : formSelector;

    if (!form) {
      console.error('Form not found:', formSelector);
      return;
    }

    const config = { ...this.defaultOptions, ...options };

    if (config.preventRefresh) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.submitForm(form, apiEndpoint, config);
      });
    }

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

  async submitForm(form, apiEndpoint, config) {
    try {
      if (config.validateBeforeSubmit && !this.validateForm(form)) {
        return;
      }

      if (config.showLoading) {
        this.setLoadingState(form, true);
      }

      const formData = this.collectFormData(form);

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
        if (window.popupSystem) {
          popupSystem.success(config.successMessage);
        } else {
          alert(config.successMessage);
        }

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

        if (config.onSuccess) {
          config.onSuccess(result, form);
        }

      } else {
        const errorMsg = result.error || config.errorMessage;
        if (window.popupSystem) {
          popupSystem.error(errorMsg);
        } else {
          alert('Error: ' + errorMsg);
        }

        if (config.onError) {
          config.onError(result, form);
        }
      }

    } catch (error) {
      const errorMsg = 'Network error: ' + error.message;
      if (window.popupSystem) {
        popupSystem.error(errorMsg);
      } else {
        alert(errorMsg);
      }

      if (config.onError) {
        config.onError({ error: error.message }, form);
      }

    } finally {
      if (config.showLoading) {
        this.setLoadingState(form, false);
      }
    }
  }

  collectFormData(form) {
    const formData = new FormData(form);
    const data = {};

    for (let [key, value] of formData.entries()) {
      if (key.endsWith('[]')) {
        const arrayKey = key.slice(0, -2);
        if (!data[arrayKey]) data[arrayKey] = [];
        data[arrayKey].push(value);
      } else {
        data[key] = value;
      }
    }

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

    if (!form.checkValidity()) {
      isValid = false;
    }

    return isValid;
  }

  showFieldError(field, message) {
    this.clearFieldError(field);

    field.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error text-red-600 text-sm mt-1';
    errorDiv.textContent = message;

    field.parentNode.insertBefore(errorDiv, field.nextSibling);
  }

  clearFieldError(field) {
    field.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');

    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
      existingError.remove();
    }
  }

  setLoadingState(form, loading) {
    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');

    if (loading) {
      form.classList.add('opacity-75', 'pointer-events-none');

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.dataset.originalText = submitButton.textContent;
        submitButton.textContent = 'Loading...';
      }
    } else {
      form.classList.remove('opacity-75', 'pointer-events-none');

      if (submitButton) {
        submitButton.disabled = false;
        if (submitButton.dataset.originalText) {
          submitButton.textContent = submitButton.dataset.originalText;
          delete submitButton.dataset.originalText;
        }
      }
    }
  }

  static quickSetup() {
    const handler = new FormHandler();

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

document.addEventListener('DOMContentLoaded', () => {
  FormHandler.quickSetup();
});

if (typeof module !== 'undefined' && module.exports) {
  module.exports = FormHandler;
}

window.FormHandler = FormHandler;
