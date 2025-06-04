# Migration Guide: From Native Alerts to Custom Popup System

This guide helps you migrate from native JavaScript `alert()`, `confirm()`, and `prompt()` functions to the new custom popup system.

## Quick Migration Reference

### Alert Replacements

| Old Code | New Code |
|----------|----------|
| `alert('Success!')` | `popupSystem.success('Success!')` |
| `alert('Error: ' + msg)` | `popupSystem.error(msg)` |
| `alert('Warning!')` | `popupSystem.warning('Warning!')` |
| `alert('Info message')` | `popupSystem.info('Info message')` |

### Confirm Replacements

| Old Code | New Code |
|----------|----------|
| `if (confirm('Delete?')) { ... }` | `if (await popupSystem.confirm('Delete?')) { ... }` |
| `confirm('Are you sure?')` | `await popupSystem.confirm('Are you sure?')` |

### Prompt Replacements

| Old Code | New Code |
|----------|----------|
| `prompt('Enter name:')` | `await popupSystem.prompt('Enter name:')` |
| `prompt('Enter:', 'default')` | `await popupSystem.prompt('Enter:', 'Input', 'default')` |

## Step-by-Step Migration

### 1. Simple Alert Messages

**Before:**
```javascript
try {
  // some operation
  alert('Operation successful!');
} catch (error) {
  alert('Error: ' + error.message);
}
```

**After:**
```javascript
try {
  // some operation
  popupSystem.success('Operation successful!');
} catch (error) {
  popupSystem.error('Error: ' + error.message);
}
```

### 2. Confirmation Dialogs

**Before:**
```javascript
function deleteItem(id) {
  if (!confirm('Are you sure you want to delete this item?')) {
    return;
  }
  
  // delete logic
}
```

**After:**
```javascript
async function deleteItem(id) {
  const confirmed = await popupSystem.confirm('Are you sure you want to delete this item?');
  if (!confirmed) {
    return;
  }
  
  // delete logic
}
```

### 3. Input Collection

**Before:**
```javascript
function renameItem() {
  const newName = prompt('Enter new name:');
  if (newName && newName.trim()) {
    // process name
  }
}
```

**After:**
```javascript
async function renameItem() {
  const newName = await popupSystem.prompt('Enter new name:');
  if (newName !== null && newName.trim()) {
    // process name
  }
}
```

### 4. Form Submission Patterns

**Before:**
```javascript
fetch('/api/submit', {
  method: 'POST',
  body: JSON.stringify(data)
})
.then(response => response.json())
.then(result => {
  if (result.success) {
    alert('Saved successfully!');
  } else {
    alert('Error: ' + result.error);
  }
})
.catch(error => {
  alert('Network error: ' + error.message);
});
```

**After:**
```javascript
fetch('/api/submit', {
  method: 'POST',
  body: JSON.stringify(data)
})
.then(response => response.json())
.then(result => {
  if (result.success) {
    popupSystem.success('Saved successfully!');
  } else {
    popupSystem.error(result.error);
  }
})
.catch(error => {
  popupSystem.error('Network error: ' + error.message);
});
```

## Advanced Migration Patterns

### 1. Enhanced Delete Confirmations

**Before:**
```javascript
if (confirm('Delete this room?')) {
  // delete
}
```

**After:**
```javascript
const confirmed = await popupSystem.confirm(
  'Are you sure you want to delete this room? This action cannot be undone.',
  'Delete Room',
  {
    confirmText: 'Delete',
    cancelText: 'Cancel',
    type: 'danger'
  }
);

if (confirmed) {
  // delete
}
```

### 2. Input with Validation

**Before:**
```javascript
const email = prompt('Enter email:');
if (email && email.includes('@')) {
  // process email
} else {
  alert('Invalid email!');
}
```

**After:**
```javascript
const email = await popupSystem.prompt(
  'Enter your email address:',
  'Email Required',
  '',
  {
    inputType: 'email',
    required: true,
    validator: (value) => {
      if (!value.includes('@')) {
        return 'Please enter a valid email address';
      }
      return true;
    }
  }
);

if (email !== null) {
  // process email
}
```

### 3. Progress Notifications

**Before:**
```javascript
// No equivalent - had to use console.log or custom solutions
console.log('Processing...');
```

**After:**
```javascript
popupSystem.info('Processing...');

// Later...
popupSystem.success('Processing complete!');
```

## Common Gotchas

### 1. Async/Await Required

**❌ Wrong:**
```javascript
if (popupSystem.confirm('Delete?')) {
  // This won't work - confirm returns a Promise
}
```

**✅ Correct:**
```javascript
if (await popupSystem.confirm('Delete?')) {
  // This works
}
```

### 2. Function Must Be Async

**❌ Wrong:**
```javascript
function handleDelete() {
  if (await popupSystem.confirm('Delete?')) {
    // SyntaxError: await outside async function
  }
}
```

**✅ Correct:**
```javascript
async function handleDelete() {
  if (await popupSystem.confirm('Delete?')) {
    // This works
  }
}
```

### 3. Null Check for Prompts

**❌ Wrong:**
```javascript
const name = await popupSystem.prompt('Name:');
if (name) {
  // This fails if user enters empty string
}
```

**✅ Correct:**
```javascript
const name = await popupSystem.prompt('Name:');
if (name !== null) {
  // This correctly handles cancellation vs empty input
}
```

## Files Already Migrated

The following files have been updated to use the new popup system:

- ✅ `templates/CreatePurpose.php`
- ✅ `templates/CreateReservation.php`
- ✅ `templates/Permissions.php`
- ✅ `templates/PermissionChanges.php`
- ✅ `templates/ProfileVerification.php`
- ✅ `components/delete/room.php`
- ✅ `index.php` (verification warning)

## Files That Still Need Migration

Search for these patterns in your codebase:

```bash
# Find remaining alert() calls
grep -r "alert(" --include="*.php" --include="*.js" .

# Find remaining confirm() calls
grep -r "confirm(" --include="*.php" --include="*.js" .

# Find remaining prompt() calls
grep -r "prompt(" --include="*.php" --include="*.js" .
```

## Testing Your Migration

1. Test all success scenarios with the new success notifications
2. Test all error scenarios with the new error notifications
3. Test all confirmation dialogs to ensure they work as expected
4. Test all input dialogs with various inputs (valid, invalid, cancelled)
5. Test on mobile devices to ensure responsive behavior

## Rollback Plan

If you need to temporarily rollback to native functions, comment out these lines in `index.php`:

```javascript
// window.alert = function(message) {
//   return window.popupSystem.alert(message);
// };

// window.confirm = function(message) {
//   return window.popupSystem.confirm(message);
// };

// window.prompt = function(message, defaultValue = '') {
//   return window.popupSystem.prompt(message, 'Input Required', defaultValue);
// };
```
