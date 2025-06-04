# Custom Popup System

This custom popup system replaces native JavaScript `alert()`, `confirm()`, and `prompt()` functions with modern, styled components.

## Features

- **Notification Cards**: Small informational cards in bottom-right corner
- **Alert Dialogs**: Full-featured popups for important messages
- **Confirm Dialogs**: User confirmation dialogs with customizable buttons
- **Input Dialogs**: User input collection with validation
- **Automatic fallback**: Overrides native functions for backward compatibility

## Usage

### Basic Functions (Backward Compatible)

```javascript
// These work exactly like native functions but with better styling
alert('This is an alert message');
confirm('Are you sure you want to delete this?');
prompt('Enter your name:', 'Default value');
```

### Advanced Usage

#### Notification Cards (Bottom-right corner)
```javascript
// Success notification
popupSystem.success('Item saved successfully!');

// Error notification
popupSystem.error('Failed to save item');

// Warning notification
popupSystem.warning('This action cannot be undone');

// Info notification
popupSystem.info('New update available');

// Custom notification
popupSystem.showNotification('Custom message', 'info', 'Custom Title', 5000);
```

#### Alert Dialogs
```javascript
// Basic alert
popupSystem.alert('Operation completed successfully!');

// Alert with custom title and type
popupSystem.alert('Database connection failed', 'Connection Error', 'error');

// With callback
popupSystem.alert('Welcome to the system!', 'Welcome', 'success').then(() => {
  console.log('User acknowledged the alert');
});
```

#### Confirm Dialogs
```javascript
// Basic confirmation
popupSystem.confirm('Are you sure you want to delete this item?').then(result => {
  if (result) {
    console.log('User confirmed');
  } else {
    console.log('User cancelled');
  }
});

// Custom confirmation with options
popupSystem.confirm('Delete this room permanently?', 'Confirm Deletion', {
  confirmText: 'Delete',
  cancelText: 'Keep',
  type: 'danger'
}).then(result => {
  if (result) {
    // Proceed with deletion
  }
});
```

#### Input Dialogs
```javascript
// Basic input
popupSystem.prompt('Enter room name:').then(result => {
  if (result !== null) {
    console.log('User entered:', result);
  }
});

// Advanced input with validation
popupSystem.prompt('Enter email address:', 'Email Required', '', {
  inputType: 'email',
  placeholder: 'user@example.com',
  required: true,
  validator: (value) => {
    if (!value.includes('@')) {
      return 'Please enter a valid email address';
    }
    return true;
  }
}).then(result => {
  if (result !== null) {
    console.log('Valid email entered:', result);
  }
});
```

## Replacing Existing alert() Calls

### Before (Old Code)
```javascript
// Old alert usage
alert('Purpose created successfully!');
alert('Error: ' + (result.error || 'Unknown error'));

// Old confirm usage
if (!confirm('Are you sure you want to delete this room?')) return;
```

### After (New Code)
```javascript
// New notification for success
popupSystem.success('Purpose created successfully!');

// New notification for errors
popupSystem.error(result.error || 'Unknown error');

// New confirm dialog
const confirmed = await popupSystem.confirm('Are you sure you want to delete this room?');
if (!confirmed) return;
```

## Configuration Options

### Notification Options
- `message`: The notification message
- `type`: 'info', 'success', 'warning', 'error'
- `title`: Custom title (optional)
- `duration`: Auto-hide duration in milliseconds (0 = no auto-hide)

### Confirm Dialog Options
- `confirmText`: Text for confirm button (default: 'Yes')
- `cancelText`: Text for cancel button (default: 'No')
- `type`: 'warning', 'danger', 'info'
- `confirmClass`: CSS class for confirm button
- `cancelClass`: CSS class for cancel button

### Input Dialog Options
- `placeholder`: Input placeholder text
- `inputType`: HTML input type ('text', 'email', 'password', etc.)
- `required`: Whether input is required
- `maxLength`: Maximum input length
- `minLength`: Minimum input length
- `pattern`: Regex pattern for validation
- `validator`: Custom validation function
- `confirmText`: Text for OK button
- `cancelText`: Text for Cancel button

## Styling

The popup system uses CSS classes that can be customized:
- `.popup-overlay`: Main overlay background
- `.popup-content`: Popup content container
- `.notification-card`: Notification card styling
- `.popup-btn-*`: Button styling classes

## Browser Support

- Modern browsers with ES6+ support
- Graceful fallback to native dialogs if JavaScript fails
- Responsive design for mobile devices
