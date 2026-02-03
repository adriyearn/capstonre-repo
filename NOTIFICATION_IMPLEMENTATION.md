# Notification System - Complete Implementation

## What Was Fixed

✅ **API Path Issues** - Fixed incorrect include paths in:
- `/public/api/notifications_unread.php` 
- `/public/api/notifications_mark_read.php`

✅ **Integrated Notifications into Workflow** - Added automatic notifications:
- **When project is uploaded** - Faculty & Admin get notified
- **When project is reviewed** - Student gets notified with status (Approved/Revision/Rejected)
- **When review is submitted** - Other faculty & admin get notified

✅ **Created Test Page** - Visit `/capstone-repo/test_notifications.php` to test the system

## How It Works Now

### 1. Student Uploads Project
```php
// In public/upload_project.php
- Creates project record
- Sends notification to all faculty: "New Project Submission"
- Sends notification to all admins: "New Project Submitted"
```

### 2. Faculty Reviews Project
```php
// In public/review_submit.php
- Creates review record
- Sends notification to student with status (Approved/Revision/Rejected)
- Sends notification to other faculty: "New Review Submitted"
- Sends notification to admins: "Project Review Submitted"
```

### 3. Student Sees Notifications
```javascript
// In assets/notifications.js
- Bell icon polls every 30 seconds for new notifications
- Shows unread count badge
- Displays notifications in dropdown
- Mark as read when clicked
- Navigate to related page when clicked
```

## Files Modified

1. **public/review_submit.php**
   - Added notification_helper.php include
   - Creates notifications for students, faculty, and admins after review

2. **public/upload_project.php**
   - Added notification_helper.php include
   - Creates notifications for faculty and admins after upload

3. **public/api/notifications_unread.php**
   - Fixed include paths (was `/../` should be `/../../`)

4. **public/api/notifications_mark_read.php**
   - Fixed include paths (was `/../` should be `/../../`)

## Files Already Created

- **assets/notifications.js** - Frontend notification handler
- **assets/custom.css** - Notification bell styling
- **includes/notification_helper.php** - Helper functions
- **student/student_dashboard.php** - Added notification bell
- **faculty/faculty_dashboard.php** - Added notification bell
- **admin/admin_dashboard.php** - Added notification bell

## Testing the System

### Option 1: Use Test Page
1. Go to: `http://localhost/capstone-repo/test_notifications.php`
2. Click buttons to create test notifications
3. Go to your dashboard
4. See notifications in the bell icon

### Option 2: Use Manual Workflow
1. Login as **Student** - Upload a project
2. Login as **Faculty** - You should see notification
3. Go to Faculty Dashboard - Review the project
4. Login as **Student** - You should see review notification

### Option 3: Check Database
```sql
SELECT * FROM notifications ORDER BY created_at DESC;
```

## Notification Types

The system creates notifications with these types:
- `submission` - When project is submitted
- `review` - When project is reviewed
- `test` - Test notifications

## API Endpoints

### Get Notifications
```
GET /capstone-repo/public/api/notifications_unread.php
Response: {
  "count": 3,
  "items": [
    {
      "notification_id": 1,
      "type": "review",
      "title": "Project Approved!",
      "message": "Your project has been approved",
      "url": "/capstone-repo/student/student_dashboard.php",
      "is_read": 0,
      "created_at": "2026-01-27 10:30:00"
    }
  ]
}
```

### Mark as Read
```
POST /capstone-repo/public/api/notifications_mark_read.php
Parameters: nid = notification_id
Response: HTTP 204 No Content
```

## Frontend Features

✅ Bell icon in navbar (all dashboards)
✅ Unread count badge
✅ Dropdown with latest 10 notifications
✅ Auto-polling every 30 seconds
✅ Click notification to mark read
✅ Auto-navigate to related page
✅ XSS protection (HTML escaping)
✅ Mobile-friendly
✅ Dark/light mode compatible

## Troubleshooting

### Notifications not appearing?
1. Check database: `SELECT COUNT(*) FROM notifications;`
2. Check permissions on tables
3. Verify user is logged in (session active)
4. Check browser console for JavaScript errors
5. Try test page at `/capstone-repo/test_notifications.php`

### Bell not updating?
1. Clear browser cache (Ctrl+Shift+Del)
2. Check Network tab - API calls should show
3. Verify `notifications.js` is loaded
4. Check user_id in session

### API returning errors?
1. Check file paths in API endpoints
2. Verify authentication is working
3. Check database connection

## Next Steps

1. **Visit test page**: `/capstone-repo/test_notifications.php`
2. **Create test notifications** to verify system works
3. **Test workflows**:
   - Upload a project as student
   - Review as faculty
   - See notifications appear
4. **Delete test file** after verification: `rm test_notifications.php`

## Database Table

Notifications are stored in the `notifications` table with:
- notification_id (PK)
- user_id (FK to users)
- type (varchar)
- title (varchar)
- message (text)
- url (varchar)
- is_read (tinyint)
- created_at (timestamp)

Indexes on: user_id, is_read, created_at for fast queries
