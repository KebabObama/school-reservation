/**
 * Examples of how to use the Custom Popup System
 * These examples show how to replace common alert/confirm patterns
 */

// ============================================================================
// BASIC REPLACEMENTS
// ============================================================================

// OLD: alert('Success message');
// NEW: 
popupSystem.success('Operation completed successfully!');

// OLD: alert('Error: ' + errorMessage);
// NEW:
popupSystem.error('Failed to save data: ' + errorMessage);

// OLD: if (confirm('Delete this item?')) { /* delete */ }
// NEW:
const confirmed = await popupSystem.confirm('Delete this item?');
if (confirmed) {
  // delete logic
}

// ============================================================================
// FORM SUBMISSION EXAMPLES
// ============================================================================

// Example: Form submission with success/error handling
async function submitForm(formData) {
  try {
    const response = await fetch('/api/submit', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(formData)
    });
    
    const result = await response.json();
    
    if (response.ok) {
      // OLD: alert('Form submitted successfully!');
      popupSystem.success('Form submitted successfully!');
      // Redirect or update UI
    } else {
      // OLD: alert('Error: ' + result.error);
      popupSystem.error(result.error || 'Submission failed');
    }
  } catch (error) {
    // OLD: alert('Network error: ' + error.message);
    popupSystem.error('Network error: ' + error.message);
  }
}

// ============================================================================
// DELETE CONFIRMATION EXAMPLES
// ============================================================================

// Example: Delete with danger confirmation
async function deleteRoom(roomId, roomName) {
  const confirmed = await popupSystem.confirm(
    `Are you sure you want to delete "${roomName}"? This action cannot be undone.`,
    'Delete Room',
    {
      confirmText: 'Delete',
      cancelText: 'Cancel',
      type: 'danger'
    }
  );
  
  if (!confirmed) return;
  
  try {
    const response = await fetch(`/api/rooms/${roomId}`, {
      method: 'DELETE'
    });
    
    if (response.ok) {
      popupSystem.success('Room deleted successfully');
      // Remove from UI or refresh
    } else {
      popupSystem.error('Failed to delete room');
    }
  } catch (error) {
    popupSystem.error('Network error: ' + error.message);
  }
}

// ============================================================================
// INPUT COLLECTION EXAMPLES
// ============================================================================

// Example: Simple text input
async function renameItem() {
  const newName = await popupSystem.prompt('Enter new name:');
  if (newName !== null && newName.trim()) {
    // Process the new name
    console.log('New name:', newName);
  }
}

// Example: Email input with validation
async function addUserEmail() {
  const email = await popupSystem.prompt(
    'Enter user email address:',
    'Add User',
    '',
    {
      inputType: 'email',
      placeholder: 'user@example.com',
      required: true,
      validator: (value) => {
        if (!value.includes('@')) {
          return 'Please enter a valid email address';
        }
        if (value.length < 5) {
          return 'Email address is too short';
        }
        return true;
      }
    }
  );
  
  if (email !== null) {
    // Process the email
    console.log('Email:', email);
  }
}

// Example: Password input with validation
async function changePassword() {
  const password = await popupSystem.prompt(
    'Enter new password:',
    'Change Password',
    '',
    {
      inputType: 'password',
      required: true,
      minLength: 8,
      validator: (value) => {
        if (value.length < 8) {
          return 'Password must be at least 8 characters long';
        }
        if (!/[A-Z]/.test(value)) {
          return 'Password must contain at least one uppercase letter';
        }
        if (!/[0-9]/.test(value)) {
          return 'Password must contain at least one number';
        }
        return true;
      }
    }
  );
  
  if (password !== null) {
    // Process the password
    console.log('Password changed');
  }
}

// ============================================================================
// NOTIFICATION EXAMPLES
// ============================================================================

// Example: Progress notifications
function showProgressNotifications() {
  popupSystem.info('Starting process...');
  
  setTimeout(() => {
    popupSystem.info('Processing data...');
  }, 1000);
  
  setTimeout(() => {
    popupSystem.success('Process completed!');
  }, 3000);
}

// Example: Warning before action
function warnBeforeAction() {
  popupSystem.warning('This action will affect all users', 'Warning');
  
  setTimeout(async () => {
    const proceed = await popupSystem.confirm(
      'Do you want to continue?',
      'Confirm Action',
      { type: 'warning' }
    );
    
    if (proceed) {
      popupSystem.info('Action executed');
    }
  }, 2000);
}

// ============================================================================
// ADVANCED EXAMPLES
// ============================================================================

// Example: Multi-step process with different popup types
async function multiStepProcess() {
  // Step 1: Get user input
  const projectName = await popupSystem.prompt(
    'Enter project name:',
    'Create Project',
    '',
    { required: true, minLength: 3 }
  );
  
  if (projectName === null) return; // User cancelled
  
  // Step 2: Confirm action
  const confirmed = await popupSystem.confirm(
    `Create project "${projectName}"?`,
    'Confirm Creation'
  );
  
  if (!confirmed) return;
  
  // Step 3: Show progress
  popupSystem.info('Creating project...');
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    // Step 4: Show success
    popupSystem.success(`Project "${projectName}" created successfully!`);
  } catch (error) {
    // Step 4: Show error
    popupSystem.error('Failed to create project: ' + error.message);
  }
}

// Example: Batch operations with notifications
async function batchDelete(items) {
  const confirmed = await popupSystem.confirm(
    `Delete ${items.length} items?`,
    'Batch Delete',
    {
      confirmText: 'Delete All',
      type: 'danger'
    }
  );
  
  if (!confirmed) return;
  
  let successCount = 0;
  let errorCount = 0;
  
  for (const item of items) {
    try {
      await deleteItem(item.id);
      successCount++;
    } catch (error) {
      errorCount++;
    }
  }
  
  // Show summary
  if (errorCount === 0) {
    popupSystem.success(`Successfully deleted ${successCount} items`);
  } else if (successCount === 0) {
    popupSystem.error(`Failed to delete ${errorCount} items`);
  } else {
    popupSystem.warning(
      `Deleted ${successCount} items, ${errorCount} failed`,
      'Partial Success'
    );
  }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

// Clear all notifications
function clearAllNotifications() {
  popupSystem.clearNotifications();
}

// Show multiple notifications for testing
function testNotifications() {
  popupSystem.info('Info notification');
  popupSystem.success('Success notification');
  popupSystem.warning('Warning notification');
  popupSystem.error('Error notification');
}
