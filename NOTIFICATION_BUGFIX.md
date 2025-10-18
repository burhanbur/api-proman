# 🔧 Notification System - Bug Fixes

## Bug Fixed: str_replace() Type Error

### Issue
Ketika create comment, muncul error:
```
str_replace(): Argument #2 ($replace) must be of type string when argument #1 ($search) is a string
```

### Root Causes

#### 1. **NotificationTemplate.php - renderTemplate()**
Method `renderTemplate()` tidak handle tipe data non-string dengan baik. Ketika ada value berupa array atau null, `str_replace()` akan error.

**Before:**
```php
private function renderTemplate(string $template, array $data): string
{
    $result = $template;
    
    foreach ($data as $key => $value) {
        $placeholder = '{{' . $key . '}}';
        $result = str_replace($placeholder, $value, $result);
    }
    
    return $result;
}
```

**After:**
```php
private function renderTemplate(string $template, array $data): string
{
    $result = $template;
    
    foreach ($data as $key => $value) {
        $placeholder = '{{' . $key . '}}';
        
        // Convert value to string safely
        if (is_array($value)) {
            // Skip arrays, don't replace
            continue;
        } elseif (is_null($value)) {
            $stringValue = '';
        } elseif (is_bool($value)) {
            $stringValue = $value ? 'true' : 'false';
        } elseif (is_object($value)) {
            // Skip objects, don't replace
            continue;
        } else {
            $stringValue = (string) $value;
        }
        
        $result = str_replace($placeholder, $stringValue, $result);
    }
    
    return $result;
}
```

**Fix:**
- ✅ Handle array → skip replacement
- ✅ Handle null → replace with empty string
- ✅ Handle boolean → convert to 'true'/'false'
- ✅ Handle object → skip replacement
- ✅ Cast other types to string

---

#### 2. **CommentController.php - Missing Relationship**
Task tidak load relationship `assignees`, sehingga `$task->assignees` return null.

**Before:**
```php
$task = Task::with(['attachments.createdBy', 'project'])
    ->where('id', $data['task_id'])
    ->first();
```

**After:**
```php
$task = Task::with(['attachments.createdBy', 'project', 'assignees'])
    ->where('id', $data['task_id'])
    ->first();
```

**Fix:**
- ✅ Load `assignees` relationship
- ✅ Sekarang `$task->assignees->pluck('id')->toArray()` akan return array ID yang valid

---

## Testing

### Test Case 1: Create Comment
```bash
POST /api/comments
{
  "task_id": 1,
  "comment": "This is a test comment"
}
```

**Expected Result:**
- ✅ Comment created successfully
- ✅ Notification "comment_added" sent to assignees & creator
- ✅ No str_replace() error

### Test Case 2: Create Comment with Mention
```bash
POST /api/comments
{
  "task_id": 1,
  "comment": "Hey @john, please review this"
}
```

**Expected Result:**
- ✅ Comment created successfully
- ✅ Notification "comment_added" sent to assignees & creator
- ✅ Notification "comment_mentioned" sent to @john
- ✅ No str_replace() error

### Test Case 3: Comment on Task without Assignees
```bash
POST /api/comments
{
  "task_id": 2,  # Task with no assignees
  "comment": "Test"
}
```

**Expected Result:**
- ✅ Comment created successfully
- ✅ `assignee_id` will be empty array `[]`
- ✅ Array safely skipped in template rendering
- ✅ No error

---

## Files Modified

1. ✅ `app/Models/NotificationTemplate.php`
   - Enhanced `renderTemplate()` with type-safe string conversion

2. ✅ `app/Http/Controllers/Api/CommentController.php`
   - Added `assignees` to eager load in `store()` method

---

## Lessons Learned

### 1. **Always Handle Different Data Types**
Ketika ada dynamic template rendering, pastikan handle:
- Arrays
- Null values
- Objects
- Booleans

### 2. **Always Load Required Relationships**
Pastikan load semua relationships yang digunakan untuk avoid null reference errors.

### 3. **Use Try-Catch for Each Notification**
NotificationService sudah implement try-catch per user untuk avoid cascade failures:
```php
foreach ($userIds as $userId) {
    try {
        Notification::create([...]);
    } catch (\Exception $e) {
        \Log::error("Failed to create notification for user {$userId}: " . $e->getMessage());
    }
}
```

---

## Prevention for Future

### Checklist when Adding New Notifications:

- [ ] Pastikan semua relationships di-load dengan `with()`
- [ ] Pastikan data yang dikirim ke trigger() adalah scalar values atau null
- [ ] Jika ada array (seperti assignee_id), pastikan template tidak menggunakan placeholder tersebut
- [ ] Test dengan data yang berbeda (with/without assignees, with/without mentions, etc)

### Template Placeholder Guidelines:

✅ **Good - Use for scalar values:**
```php
'{{task_title}}'      // string
'{{user_name}}'       // string
'{{task_id}}'         // integer
'{{priority_level}}'  // string
```

❌ **Bad - Don't use for arrays/objects:**
```php
'{{assignee_id}}'     // array - will be skipped
'{{assignees}}'       // collection - will be skipped
'{{task}}'            // object - will be skipped
```

---

## Status

✅ **FIXED** - Comment creation now works properly with notifications!

Date Fixed: October 18, 2025
