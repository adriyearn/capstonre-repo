# Notification System Documentation

## Overview
Your notification system is now fully integrated into the capstone portal. It includes:
- Frontend notification bell with dropdown menu
- Real-time notification polling (every 30 seconds)
- Mark notifications as read on click
- Automatic navigation to related pages
- PHP backend API endpoints

## Frontend Components

### Notification Bell
- Located in navbar of all dashboards (student, faculty, admin)
- Shows unread count badge
- Bell icon with SVG design
- Dropdown menu with latest 10 notifications

### JavaScript Features
**File:** `/assets/notifications.js`

- **Auto-polling:** Fetches new notifications every 30 seconds
- **Toggle dropdown:** Click bell icon to show/hide notifications
- **Mark as read:** Click any notification to mark it as read
- **Navigation:** Notifications with URLs auto-navigate when clicked
- **HTML escaping:** Prevents XSS attacks by escaping notification text

## Backend API Endpoints

### 1. Get Unread Notifications
**Endpoint:** `/capstone-repo/public/api/notifications_unread.php`  
**Method:** GET  
**Auth:** Required (session)

**Response:**
```json
{
  "count": 3,
  "items": [
    {
      "notification_id": 1,
      "type": "review",
      "title": "New Review",
      "message": "Your project has been reviewed",
      "url": "/capstone-repo/student/student_dashboard.php",
      "is_read": 0,
      "created_at": "2026-01-27 10:30:00"
    }
  ]
}
```

### 2. Mark Notification as Read
**Endpoint:** `/capstone-repo/public/api/notifications_mark_read.php`  
**Method:** POST  
**Auth:** Required (session)  
**Parameters:**
- `nid` (int): Notification ID

**Response:** HTTP 204 (No Content)

## Database Schema

Required table structure:
```sql
CREATE TABLE notifications (
  notification_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  type VARCHAR(50),
  title VARCHAR(255) NOT NULL,
  message TEXT,
  url VARCHAR(255),
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX (user_id, is_read),
  INDEX (created_at)
);
```

## Using the Notification Helper

### Import the Helper
```php
<?php
require_once __DIR__ . '/../includes/notification_helper.php';
```

### Create Single Notification
```php
$notification_id = create_notification(
  $user_id,
  'New Review',
  'Your project has been reviewed',
  '/capstone-repo/student/student_dashboard.php',
  'review'
);
```

### Create Bulk Notifications
```php
$user_ids = [1, 2, 3, 4, 5];
$count = create_bulk_notifications(
  $user_ids,
  'System Update',
  'The portal has been updated',
  '/capstone-repo/admin/admin_dashboard.php',
  'system'
);
echo "Created $count notifications";
```

### Get Unread Count
```php
$unread = get_unread_count($user_id);
echo "You have $unread unread notifications";
```

### Mark as Read
```php
mark_notification_read($notification_id);
```

### Clean Up Old Notifications
```php
// Delete notifications older than 30 days
$deleted = cleanup_old_notifications(30);
echo "Deleted $deleted old notifications";
```

## Integration Examples

### Example 1: Project Review Submission
```php
<?php
require_once __DIR__ . '/../includes/notification_helper.php';

// After submitting a review
$notification_id = create_notification(
  $student_id,
  'Project Review Complete',
  "Faculty $faculty_name has reviewed your project. " . $review_status,
  '/capstone-repo/student/student_dashboard.php',
  'review'
);

// Notify student
if ($notification_id) {
  echo "Notification sent to student";
}
?>
```

### Example 2: Project Status Change
```php
<?php
require_once __DIR__ . '/../includes/notification_helper.php';

// When admin approves a project
if ($project_status === 'approved') {
  create_notification(
    $student_id,
    'Project Approved!',
    'Congratulations! Your capstone project has been approved by the admin.',
    '/capstone-repo/student/student_dashboard.php',
    'approval'
  );
}
?>
```

### Example 3: Bulk Notification
```php
<?php
require_once __DIR__ . '/../includes/notification_helper.php';

// Notify all faculty about new submission
$pdo = DB::getConnection();
$faculty_ids = $pdo->query("SELECT user_id FROM users WHERE role = 'faculty'")
  ->fetchAll(PDO::FETCH_COLUMN);

create_bulk_notifications(
  $faculty_ids,
  'New Project Submitted',
  "A student has submitted a new capstone project for review",
  '/capstone-repo/faculty/faculty_dashboard.php',
  'submission'
);
?>
```

## Notification Types

Standard notification types (customize as needed):
- `review` - Project review notifications
- `approval` - Approval notifications
- `rejection` - Rejection notifications
- `revision` - Revision request notifications
- `comment` - Comment notifications
- `submission` - New submission notifications
- `system` - System notifications
- `info` - General information

## Styling

Notifications are styled with:
- **Unread:** Light blue background with left border accent
- **Read:** White background
- **Hover:** Gray background highlight
- **Badge:** Red unread count badge in corner
- **Dropdown:** White box with shadow, scrollable, 350px width

All styles are in `/assets/custom.css` under "Notification Bell Styles" section.

## Browser Compatibility

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- Mobile browsers: Full support with touch-friendly click targets

## Performance Considerations

- Polls every 30 seconds (adjustable in `notifications.js`)
- Fetches only latest 10 notifications per poll
- Uses indexes on user_id, is_read, and created_at for fast queries
- Automatically hides unread badge when count is 0
- Consider archiving old notifications for large user bases

## Security

- All endpoints require authentication (`auth_check.php`)
- User can only see their own notifications
- HTML content is escaped to prevent XSS
- CSRF protection available via POST parameters
- Notifications stored server-side (no client-side data leak)

## Troubleshooting

**Notifications not appearing:**
1. Check browser console for JavaScript errors
2. Verify database table exists with correct schema
3. Check user is authenticated (session valid)
4. Verify API endpoint URLs are correct

**Notifications not marking as read:**
1. Check browser network tab for POST errors
2. Verify notification ID in database exists
3. Check user_id matches notification owner

**Badge not updating:**
1. Clear browser cache
2. Check polling is running (check network tab)
3. Verify JSON response format is correct

## Future Enhancements

Possible additions:
- Sound/browser notifications
- Email notification integration
- Notification preferences/settings
- Notification categories/filtering
- Real-time updates with WebSockets
- Notification history/archive
- User notification preferences (on/off by type)
