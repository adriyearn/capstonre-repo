/**
 * Capstone Project Management System - Main JavaScript
 */

// Form validation and enhancements
document.addEventListener('DOMContentLoaded', function() {
  const forms = document.querySelectorAll('form');
  
  forms.forEach(form => {
    form.addEventListener('submit', function(e) {
      // Basic client-side validation
      const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
      let isValid = true;
      
      inputs.forEach(input => {
        if (!input.value.trim()) {
          isValid = false;
          input.style.borderColor = '#ef4444';
          input.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
        } else {
          input.style.borderColor = '';
          input.style.boxShadow = '';
        }
      });
      
      if (!isValid) {
        e.preventDefault();
        showNotification('Please fill in all required fields', 'error');
      }
    });
  });

  // Email validation
  const emailInputs = document.querySelectorAll('input[type="email"]');
  emailInputs.forEach(input => {
    input.addEventListener('blur', function() {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (this.value && !emailRegex.test(this.value)) {
        this.style.borderColor = '#ef4444';
        showNotification('Please enter a valid email address', 'error');
      } else {
        this.style.borderColor = '';
      }
    });
  });

  // Password strength indicator
  const passwordInputs = document.querySelectorAll('input[name="password"]');
  passwordInputs.forEach(input => {
    input.addEventListener('input', function() {
      const strength = calculatePasswordStrength(this.value);
      const strengthText = document.createElement('p');
      strengthText.style.fontSize = '0.875rem';
      strengthText.style.marginTop = '0.25rem';
      
      if (strength < 2) {
        strengthText.textContent = '⚠️ Weak password';
        strengthText.style.color = '#ef4444';
      } else if (strength < 3) {
        strengthText.textContent = '→ Fair password';
        strengthText.style.color = '#f59e0b';
      } else {
        strengthText.textContent = '✓ Strong password';
        strengthText.style.color = '#10b981';
      }
      
      const existing = this.parentElement.querySelector('.password-strength');
      if (existing) existing.remove();
      
      strengthText.className = 'password-strength';
      this.parentElement.appendChild(strengthText);
    });
  });

  // File input preview
  const fileInputs = document.querySelectorAll('input[type="file"]');
  fileInputs.forEach(input => {
    input.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
        const fileInfo = document.createElement('p');
        fileInfo.style.fontSize = '0.875rem';
        fileInfo.style.marginTop = '0.5rem';
        fileInfo.style.color = '#6b7280';
        fileInfo.textContent = `📄 ${file.name} (${sizeInMB} MB)`;
        
        const existing = this.parentElement.querySelector('.file-info');
        if (existing) existing.remove();
        
        fileInfo.className = 'file-info';
        this.parentElement.appendChild(fileInfo);
      }
    });
  });

  // Add smooth scrolling to all links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });
});

/**
 * Calculate password strength (0-4)
 */
function calculatePasswordStrength(password) {
  let strength = 0;
  if (password.length >= 8) strength++;
  if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
  if (password.match(/[0-9]/)) strength++;
  if (password.match(/[^a-zA-Z0-9]/)) strength++;
  return strength;
}

/**
 * Show notification toast
 */
function showNotification(message, type = 'info', duration = 3000) {
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 6px;
    font-weight: 500;
    z-index: 9999;
    animation: slideIn 0.3s ease;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  `;
  
  const colors = {
    info: { bg: '#dbeafe', text: '#1e40af', border: '#3b82f6' },
    error: { bg: '#fee2e2', text: '#991b1b', border: '#ef4444' },
    success: { bg: '#dcfce7', text: '#166534', border: '#10b981' },
    warning: { bg: '#fef3c7', text: '#92400e', border: '#f59e0b' }
  };
  
  const color = colors[type] || colors.info;
  notification.style.backgroundColor = color.bg;
  notification.style.color = color.text;
  notification.style.borderLeft = `4px solid ${color.border}`;
  notification.textContent = message;
  
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, duration);
}

/**
 * Confirm action dialog
 */
function confirmAction(msg) {
  return confirm(msg || 'Are you sure?');
}

/**
 * Add animations to document
 */
if (!document.querySelector('style[data-animations]')) {
  const style = document.createElement('style');
  style.setAttribute('data-animations', '');
  style.textContent = `
    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(style);
}