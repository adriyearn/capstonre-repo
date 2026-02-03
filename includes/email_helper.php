<?php
/**
 * Email Notification Helper
 * Sends email notifications to users
 */

/**
 * Send email notification
 * 
 * @param string $email - Recipient email address
 * @param string $subject - Email subject
 * @param string $body - HTML email body
 * @return bool - Success status
 */
function send_email_notification($email, $subject, $body) {
  try {
    // Email headers for HTML content
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@capstonerepo.local" . "\r\n";
    $headers .= "Reply-To: support@capstonerepo.local" . "\r\n";
    
    // Send email
    $result = @mail($email, $subject, $body, $headers);
    
    if (!$result) {
      error_log("Failed to send email to: $email");
      return false;
    }
    
    return true;
  } catch (Exception $e) {
    error_log("Email error: " . $e->getMessage());
    return false;
  }
}

/**
 * Send project submission notification email
 * 
 * @param string $faculty_email - Faculty member's email
 * @param string $student_name - Student who submitted
 * @param string $project_title - Project title
 * @return bool
 */
function send_project_submission_email($faculty_email, $student_name, $project_title) {
  $subject = "New Project Submission: $project_title";
  
  $body = "
    <html>
      <head>
        <style>
          body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #333; }
          .container { max-width: 600px; margin: 0 auto; padding: 20px; }
          .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; text-align: center; margin-bottom: 30px; }
          .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
          .content { background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
          .content p { margin: 10px 0; line-height: 1.6; }
          .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: 600; }
          .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        </style>
      </head>
      <body>
        <div class='container'>
          <div class='header'>
            <h1>📥 New Project Submission</h1>
          </div>
          
          <div class='content'>
            <p>Dear Faculty Member,</p>
            <p>A student has submitted a new capstone project for review:</p>
            <p><strong>Student Name:</strong> $student_name</p>
            <p><strong>Project Title:</strong> $project_title</p>
            <p>Please log in to the Capstone Portal to review the submission.</p>
            <center>
              <a href='http://localhost/capstone-repo/faculty/faculty_dashboard.php' class='button'>View Submission</a>
            </center>
            <p>Best regards,<br>Capstone Project Management System</p>
          </div>
          
          <div class='footer'>
            <p>This is an automated notification. Please do not reply to this email.</p>
          </div>
        </div>
      </body>
    </html>
  ";
  
  return send_email_notification($faculty_email, $subject, $body);
}

/**
 * Send project review notification email
 * 
 * @param string $student_email - Student's email
 * @param string $student_name - Student's name
 * @param string $project_title - Project title
 * @param string $decision - approve, request_revision, or reject
 * @param string $comment - Reviewer's comment
 * @param string $reviewer_name - Faculty reviewer name
 * @return bool
 */
function send_project_review_email($student_email, $student_name, $project_title, $decision, $comment, $reviewer_name) {
  $statusEmoji = match($decision) {
    'approve' => '✅',
    'request_revision' => '📝',
    'reject' => '❌',
    default => '📋'
  };
  
  $statusLabel = match($decision) {
    'approve' => 'APPROVED',
    'request_revision' => 'REVISION REQUESTED',
    'reject' => 'REJECTED',
    default => 'REVIEWED'
  };
  
  $statusColor = match($decision) {
    'approve' => '#059669',
    'request_revision' => '#f59e0b',
    'reject' => '#dc2626',
    default => '#667eea'
  };
  
  $darkColor = match($decision) {
    'approve' => '#047857',
    'request_revision' => '#d97706',
    'reject' => '#991b1b',
    default => '#764ba2'
  };
  
  $subject = "[$statusLabel] Your Project Review: $project_title";
  
  $body = "
    <html>
      <head>
        <style>
          body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #333; }
          .container { max-width: 600px; margin: 0 auto; padding: 20px; }
          .header { background: linear-gradient(135deg, $statusColor 0%, $darkColor 100%); color: white; padding: 30px; border-radius: 8px; text-align: center; margin-bottom: 30px; }
          .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
          .status-badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; margin-top: 10px; font-weight: 600; }
          .content { background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
          .content p { margin: 10px 0; line-height: 1.6; }
          .feedback { background: white; padding: 15px; border-left: 4px solid $statusColor; margin: 20px 0; border-radius: 4px; }
          .feedback strong { color: $statusColor; display: block; margin-bottom: 10px; }
          .button { display: inline-block; background: linear-gradient(135deg, $statusColor 0%, $darkColor 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: 600; }
          .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        </style>
      </head>
      <body>
        <div class='container'>
          <div class='header'>
            <h1>$statusEmoji Project Review</h1>
            <div class='status-badge'>$statusLabel</div>
          </div>
          
          <div class='content'>
            <p>Dear $student_name,</p>
            <p>Your capstone project has been reviewed. Here are the details:</p>
            <p><strong>Project Title:</strong> $project_title</p>
            <p><strong>Reviewed by:</strong> $reviewer_name</p>
            <p><strong>Decision:</strong> <span style='color: $statusColor; font-weight: 600;'>$statusLabel</span></p>
            
            <div class='feedback'>
              <strong>Feedback:</strong>
              " . nl2br(htmlspecialchars($comment)) . "
            </div>
            
            <p>Log in to the Capstone Portal to see the full details and take any necessary action.</p>
            
            <center>
              <a href='http://localhost/capstone-repo/student/student_dashboard.php' class='button'>View Review</a>
            </center>
            
            <p>Best regards,<br>Capstone Project Management System</p>
          </div>
          
          <div class='footer'>
            <p>This is an automated notification. Please do not reply to this email.</p>
          </div>
        </div>
      </body>
    </html>
  ";
  
  return send_email_notification($student_email, $subject, $body);
}

/**
 * Send admin notification email
 * 
 * @param string $admin_email - Admin email
 * @param string $subject - Email subject
 * @param string $message - Email message
 * @param string $action_text - Button text
 * @param string $action_url - Button URL
 * @return bool
 */
function send_admin_notification_email($admin_email, $subject, $message, $action_text = 'View Details', $action_url = '#') {
  $body = "
    <html>
      <head>
        <style>
          body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #333; }
          .container { max-width: 600px; margin: 0 auto; padding: 20px; }
          .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; text-align: center; margin-bottom: 30px; }
          .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
          .content { background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
          .content p { margin: 10px 0; line-height: 1.6; }
          .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: 600; }
          .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        </style>
      </head>
      <body>
        <div class='container'>
          <div class='header'>
            <h1>⚙️ System Notification</h1>
          </div>
          
          <div class='content'>
            <p>Dear Administrator,</p>
            <p>$message</p>
            <center>
              <a href='$action_url' class='button'>$action_text</a>
            </center>
            <p>Best regards,<br>Capstone Project Management System</p>
          </div>
          
          <div class='footer'>
            <p>This is an automated notification. Please do not reply to this email.</p>
          </div>
        </div>
      </body>
    </html>
  ";
  
  return send_email_notification($admin_email, $subject, $body);
}
?>
