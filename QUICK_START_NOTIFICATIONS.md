# Quick Start: Testing Notifications

## Step 1: Test Notifications Automatically
Visit this URL in your browser (while logged in):
```
http://localhost/capstone-repo/test_notifications.php
```

This page allows you to:
- ✅ Create test notifications
- ✅ View all notifications in database
- ✅ Check system status
- ✅ Test different notification types

## Step 2: Test Real Workflow

### For Students:
1. Login to student account
2. Upload a project (public/upload_project.php)
3. Go to Student Dashboard
4. Look at the notification bell - you should NOT get a notification (students don't get notified on upload)
5. Check if faculty/admin got notifications

### For Faculty:
1. Login to faculty account
2. Go to Faculty Dashboard
3. Click the notification bell - you should see "New Project Submission" notification
4. Review a project by commenting and selecting decision
5. Submit the review

### For Students (after review):
1. Login to student account
2. Go to Student Dashboard
3. Click the notification bell - you should see "Project Approved/Revision/Rejected" notification

## Step 3: What to Look For

### ✅ Success Indicators:
- [ ] Bell icon appears in navbar of all dashboards
- [ ] Notification bell shows red badge with unread count
- [ ] Clicking bell opens dropdown with notifications
- [ ] Notifications show title, message, and date
- [ ] Clicking notification marks it as read
- [ ] Clicking notification navigates to appropriate page

### 🔍 Debug Information:
- Open browser Developer Tools (F12)
- Go to Network tab
- Look for requests to `/public/api/notifications_unread.php`
- Response should show JSON with notifications

## Step 4: Verify in Database

```sql
-- Check notifications were created
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;

-- Check notifications by user
SELECT n.*, u.username FROM notifications n 
JOIN users u ON n.user_id = u.user_id 
ORDER BY n.created_at DESC;

-- Check unread count
SELECT user_id, COUNT(*) as unread FROM notifications 
WHERE is_read = 0 GROUP BY user_id;
```

## Files to Know About

| File | Purpose |
|------|---------|
| `/assets/notifications.js` | Frontend notification logic |
| `/assets/custom.css` | Styling for notification bell |
| `/includes/notification_helper.php` | PHP functions to create notifications |
| `/public/api/notifications_unread.php` | API to fetch notifications |
| `/public/api/notifications_mark_read.php` | API to mark as read |
| `/test_notifications.php` | Testing interface |

## Common Issues & Solutions

### Issue: Bell shows but no notifications
**Solution:**
1. Refresh page (Ctrl+R)
2. Check browser console for JS errors
3. Check Network tab for API errors
4. Visit test_notifications.php to create test notification

### Issue: API returns 404
**Solution:**
- Verify paths in notifications.js match your URL structure
- Check that `/public/api/` directory exists
- Verify URLs don't have `/capstone-repo` duplicated

### Issue: Notifications not being created
**Solution:**
1. Check `notifications` table exists: `SHOW TABLES LIKE 'notifications';`
2. Verify create_notification() function works at test page
3. Check database user permissions

### Issue: Old notifications piling up
**Solution:**
- Run cleanup: `cleanup_old_notifications(30);` in code
- Or manually: `DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);`

## After Testing

When you're confident everything works:
1. Delete `/test_notifications.php`
2. Delete this quick start guide
3. Notifications will work automatically in your workflow

## Questions?

Check [NOTIFICATION_IMPLEMENTATION.md](NOTIFICATION_IMPLEMENTATION.md) for full documentation.
