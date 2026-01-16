import { config } from './config/index.js';
import { apiCall } from './api/client.js';
import { dom } from './ui/dom.js';
import { initModals, modals } from './ui/modals.js';
import { toast } from './ui/toast.js';
import { session } from './state/session.js';
import { auth } from './services/auth.js';
import { tasks } from './services/tasks.js';
import { profile } from './services/profile.js';

// Event bus with unsubscribe support
export const eventBus = {
  listeners: new Map(),
  
  on(event, callback) {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, new Set());
    }
    this.listeners.get(event).add(callback);
    return () => this.listeners.get(event)?.delete(callback);
  },
  
  emit(event, data) {
    if (this.listeners.has(event)) {
      this.listeners.get(event).forEach(callback => callback(data));
    }
  }
};

// Initialize event listeners
function initEventListeners() {
  eventBus.on('session:updated', (sessionState) => {
    if (!sessionState.isLoggedIn) {
      const basePath = document.querySelector('base')?.href?.replace(/\/$/, '') || '';
      window.location.href = `${basePath}/index.php`;
    }
  });

  eventBus.on('auth:login', async (user) => {
    // Use dynamic base path from <base> tag
    const basePath = document.querySelector('base')?.href?.replace(/\/$/, '') 
      || ''; // Empty string for root domain deployment
      
    const redirectPath = user.user_type === 'admin' ? `${basePath}/admin/dashboard.php` : `${basePath}/dashboard.php`;
    window.location.href = redirectPath;
  });

  eventBus.on('task:created', () => {
    toast.success('Task created successfully');
    tasks.getAll(); // Refresh task list
  });

  eventBus.on('profile:updated', () => {
    toast.success('Profile updated successfully');
  });

  eventBus.on('api-call', ({ endpoint, duration }) => {
    performance.mark(`${endpoint}-end`);
    performance.measure(`API Call - ${endpoint}`, null, null, { duration });
  });
}

// Initialize app
async function initApp() {
  try {
    initEventListeners();
    initModals();
    
    await session.check();
    
    if (session.isLoggedIn) {
      if (document.getElementById('userDashboard')) {
        await tasks.getAll();
        await profile.get();
      }
    }

    if (config.features.analytics) {
      initAnalytics();
    }
  } catch (error) {
    console.error('App initialization error:', error);
    toast.error('Failed to initialize application');
  }
}

// Initialize analytics and monitoring
function initAnalytics() {
  const observer = new PerformanceObserver((list) => {
    list.getEntries().forEach((entry) => {
      console.log(`${entry.name}: ${entry.duration}ms`);
    });
  });

  observer.observe({ entryTypes: ['measure'] });
}

// Start the application when DOM is ready
document.addEventListener('DOMContentLoaded', initApp);

// Export necessary functions for legacy code compatibility
export const legacyHelpers = {
  showMessage: toast.show,
  showErrorMessage: toast.error,
  showSuccessMessage: toast.success,
  showModal: modals.show.bind(modals),
  hideModal: modals.hide.bind(modals),
  login: auth.login,
  logout: auth.logout,
  getTasks: tasks.getAll,
  updateProfile: profile.update,
  apiCall
};
