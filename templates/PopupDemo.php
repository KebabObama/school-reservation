<?php
// Demo page to showcase the popup system
// This is for testing purposes only
?>

<div class="space-y-6">
  <!-- Header -->
  <div class="bg-gradient-to-r from-purple-600 to-purple-800 rounded-lg p-6 text-white">
    <h1 class="text-3xl font-bold mb-2">Popup System Demo</h1>
    <p class="text-purple-100">Test all the different popup components and notifications.</p>
  </div>

  <!-- Notification Cards Section -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Notification Cards</h2>
    <p class="text-gray-600 mb-4">Small cards that appear in the bottom-right corner for informational messages.</p>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <button onclick="popupSystem.success('Operation completed successfully!')" 
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
        Success Notification
      </button>
      
      <button onclick="popupSystem.error('Something went wrong!')" 
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
        Error Notification
      </button>
      
      <button onclick="popupSystem.warning('Please review your settings')" 
              class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
        Warning Notification
      </button>
      
      <button onclick="popupSystem.info('New feature available!')" 
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        Info Notification
      </button>
    </div>
  </div>

  <!-- Alert Dialogs Section -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Alert Dialogs</h2>
    <p class="text-gray-600 mb-4">Full-featured popups for displaying important messages.</p>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <button onclick="popupSystem.alert('This is an informational alert', 'Information', 'info')" 
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        Info Alert
      </button>
      
      <button onclick="popupSystem.alert('Operation completed successfully!', 'Success', 'success')" 
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
        Success Alert
      </button>
      
      <button onclick="popupSystem.alert('Please check your input', 'Warning', 'warning')" 
              class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
        Warning Alert
      </button>
      
      <button onclick="popupSystem.alert('An error occurred', 'Error', 'error')" 
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
        Error Alert
      </button>
    </div>
  </div>

  <!-- Confirm Dialogs Section -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Confirm Dialogs</h2>
    <p class="text-gray-600 mb-4">User confirmation dialogs with customizable buttons.</p>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <button onclick="(async () => {
        const result = await popupSystem.confirm('Do you want to proceed?');
        popupSystem.info('You chose: ' + (result ? 'Yes' : 'No'));
      })()" 
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        Basic Confirm
      </button>
      
      <button onclick="(async () => {
        const result = await popupSystem.confirm('This action cannot be undone!', 'Delete Item', {
          confirmText: 'Delete',
          cancelText: 'Keep',
          type: 'danger'
        });
        popupSystem.info('Delete action: ' + (result ? 'Confirmed' : 'Cancelled'));
      })()" 
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
        Danger Confirm
      </button>
      
      <button onclick="(async () => {
        const result = await popupSystem.confirm('Save changes before closing?', 'Unsaved Changes', {
          confirmText: 'Save',
          cancelText: 'Discard',
          type: 'warning'
        });
        popupSystem.info('Save action: ' + (result ? 'Saved' : 'Discarded'));
      })()" 
              class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
        Warning Confirm
      </button>
    </div>
  </div>

  <!-- Input Dialogs Section -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Input Dialogs</h2>
    <p class="text-gray-600 mb-4">Collect user input with validation.</p>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <button onclick="(async () => {
        const result = await popupSystem.prompt('Enter your name:');
        if (result !== null) {
          popupSystem.success('Hello, ' + result + '!');
        }
      })()" 
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        Basic Input
      </button>
      
      <button onclick="(async () => {
        const result = await popupSystem.prompt('Enter your email:', 'Email Required', '', {
          inputType: 'email',
          placeholder: 'user@example.com',
          required: true,
          validator: (value) => {
            if (!value.includes('@')) {
              return 'Please enter a valid email address';
            }
            return true;
          }
        });
        if (result !== null) {
          popupSystem.success('Email saved: ' + result);
        }
      })()" 
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
        Email Input
      </button>
      
      <button onclick="(async () => {
        const result = await popupSystem.prompt('Enter a password:', 'Password Required', '', {
          inputType: 'password',
          required: true,
          minLength: 6,
          validator: (value) => {
            if (value.length < 6) {
              return 'Password must be at least 6 characters';
            }
            if (!/[A-Z]/.test(value)) {
              return 'Password must contain at least one uppercase letter';
            }
            return true;
          }
        });
        if (result !== null) {
          popupSystem.success('Password set successfully!');
        }
      })()" 
              class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
        Password Input
      </button>
    </div>
  </div>

  <!-- Legacy Functions Section -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Legacy Functions</h2>
    <p class="text-gray-600 mb-4">Test backward compatibility with native JavaScript functions.</p>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <button onclick="alert('This is a legacy alert!')" 
              class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
        Legacy alert()
      </button>
      
      <button onclick="(async () => {
        const result = await confirm('Do you confirm this action?');
        alert('You chose: ' + (result ? 'OK' : 'Cancel'));
      })()" 
              class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
        Legacy confirm()
      </button>
      
      <button onclick="(async () => {
        const result = await prompt('Enter something:');
        if (result !== null) {
          alert('You entered: ' + result);
        }
      })()" 
              class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
        Legacy prompt()
      </button>
    </div>
  </div>

  <!-- Clear Notifications -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Utility Functions</h2>
    <div class="flex gap-4">
      <button onclick="popupSystem.clearNotifications()" 
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
        Clear All Notifications
      </button>
      
      <button onclick="for(let i = 0; i < 5; i++) { 
        setTimeout(() => popupSystem.info('Notification ' + (i + 1)), i * 200);
      }" 
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        Test Multiple Notifications
      </button>
    </div>
  </div>
</div>
