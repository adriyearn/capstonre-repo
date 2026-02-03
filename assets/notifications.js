/**
 * Notification System
 * Ultra-compact notification manager with real-time updates
 */

class NotificationManager {
  constructor() {
    this.maxNotifications = 3; // Limit to 3 items max
    this.pollInterval = 30000; // 30 seconds
    
    // DOM Elements
    this.system = document.getElementById('notification-system');
    this.bell = document.getElementById('notification-bell');
    this.badge = document.getElementById('notification-badge');
    this.panel = document.getElementById('notification-panel');
    this.closeBtn = document.getElementById('notification-close');
    this.itemsContainer = document.getElementById('notification-items');
    
    // Initialize
    if (this.bell && this.panel && this.itemsContainer) {
      this.setupEventListeners();
      this.fetchNotifications();
      setInterval(() => this.fetchNotifications(), this.pollInterval);
    }
  }

  setupEventListeners() {
    // Bell click to toggle panel
    this.bell.addEventListener('click', (e) => {
      e.stopPropagation();
      this.togglePanel();
    });

    // Close button
    this.closeBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      this.closePanel();
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
      if (this.panel && !this.panel.contains(e.target) && e.target !== this.bell) {
        this.closePanel();
      }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !this.panel.classList.contains('hidden')) {
        this.closePanel();
      }
    });
  }

  togglePanel() {
    const isHidden = this.panel.classList.contains('hidden');
    if (isHidden) {
      this.panel.classList.remove('hidden');
    } else {
      this.panel.classList.add('hidden');
    }
  }

  closePanel() {
    this.panel.classList.add('hidden');
  }

  async fetchNotifications() {
    try {
      const response = await fetch('/capstone-repo/public/api/notifications_unread.php');
      if (!response.ok) throw new Error('Failed to fetch notifications');
      
      const data = await response.json();
      this.render(data);
    } catch (error) {
      console.error('Notification fetch error:', error);
    }
  }

  render(data) {
    // Update badge
    const count = data.count || 0;
    if (count > 0) {
      this.badge.textContent = count > 99 ? '99+' : count;
      this.badge.classList.remove('hidden');
    } else {
      this.badge.classList.add('hidden');
    }

    // Render items (max 3)
    if (!data.items || data.items.length === 0) {
      this.itemsContainer.innerHTML = this.getEmptyState();
    } else {
      const limited = data.items.slice(0, this.maxNotifications);
      this.itemsContainer.innerHTML = limited.map(item => this.createItemHTML(item)).join('');
      this.attachItemListeners();
    }
  }

  createItemHTML(notif) {
    const timeStr = this.formatTime(notif.created_at);
    const icon = this.getIcon(notif.type);
    const typeClass = notif.type || 'info';
    const unreadClass = notif.is_read ? '' : 'unread';
    
    const title = this.escapeHtml(notif.title);
    const message = this.escapeHtml(notif.message);
    
    return `
      <div class="notification-item ${typeClass} ${unreadClass}" 
           data-id="${notif.notification_id}" 
           data-url="${notif.url || ''}"
           role="button" 
           tabindex="0">
        <div class="notification-icon">${icon}</div>
        <div class="notification-content">
          <div class="notification-title">
            ${title}
            ${!notif.is_read ? '<div class="notification-unread-dot"></div>' : ''}
          </div>
          <p class="notification-message">${message}</p>
          <div class="notification-time">${timeStr}</div>
        </div>
      </div>
    `;
  }

  getEmptyState() {
    return `
      <div class="notification-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <p>No notifications</p>
        <p>You're all caught up!</p>
      </div>
    `;
  }

  attachItemListeners() {
    this.itemsContainer.querySelectorAll('.notification-item').forEach(item => {
      item.addEventListener('click', () => this.handleItemClick(item));
      item.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.handleItemClick(item);
        }
      });
    });
  }

  async handleItemClick(item) {
    const id = item.getAttribute('data-id');
    const url = item.getAttribute('data-url');
    
    try {
      const formData = new FormData();
      formData.append('nid', id);
      
      const response = await fetch('/capstone-repo/public/api/notifications_mark_read.php', {
        method: 'POST',
        body: formData
      });

      if (response.ok || response.status === 204) {
        this.fetchNotifications();
        if (url && url !== 'null' && url !== '') {
          setTimeout(() => {
            window.location.href = url;
          }, 100);
        }
      }
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  }

  formatTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins}m`;
    if (diffHours < 24) return `${diffHours}h`;
    if (diffDays < 7) return `${diffDays}d`;
    
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric' 
    });
  }

  getIcon(type) {
    const icons = {
      'review': '📋',
      'submission': '📥',
      'approval': '✅',
      'rejection': '❌',
      'revision': '📝',
      'comment': '💬',
      'system': '⚙️',
      'test': '🧪'
    };
    return icons[type] || '📢';
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  new NotificationManager();
});

