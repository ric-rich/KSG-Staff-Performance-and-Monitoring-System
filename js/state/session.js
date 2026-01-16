import { apiCall } from '../api/client.js';
import { eventBus } from '../app.js';

class SessionState {
  constructor() {
    this.user = null;
    this.isLoggedIn = false;
    this.userType = null;
    this.isAdmin = false;

    // Listen for auth events
    eventBus.on('auth:login', this.handleLogin.bind(this));
    eventBus.on('auth:logout', this.handleLogout.bind(this));
  }

  async check() {
    try {
      const result = await apiCall('api/auth.php?action=check_session');
      if (result.status === 'success' && result.logged_in) {
        this.handleLogin(result.user);
      }
      return result;
    } catch (error) {
      this.handleLogout();
      throw error;
    }
  }

  async logout() {
    try {
      // Clear server-side session
      await apiCall('api/auth.php?action=logout', 'POST');

      // Clear client-side session
      this.clearSession();

      // Force reload to clear any cached state - use dynamic base path
      const basePath = document.querySelector('base')?.href?.replace(/\/$/, '') || '';
      window.location.href = `${basePath}/index.php`;
    } catch (error) {
      console.error('Logout error:', error);
      // Force logout anyway
      this.clearSession();
      const basePath = document.querySelector('base')?.href?.replace(/\/$/, '') || '';
      window.location.href = `${basePath}/index.php`;
    }
  }

  clearSession() {
    // Clear all session data
    this.user = null;
    this.isLoggedIn = false;
    this.userType = null;
    this.isAdmin = false;

    // Clear any stored tokens or data
    localStorage.removeItem('user_settings');
    sessionStorage.clear();

    // Emit logout event
    eventBus.emit('session:updated', this);
  }

  handleLogin(user) {
    this.user = user;
    this.isLoggedIn = true;
    this.userType = user.user_type;
    this.isAdmin = user.user_type === 'admin';
    eventBus.emit('session:updated', this);
  }

  handleLogout() {
    this.clearSession();
    // Redirect will be handled by logout method
  }
}

export const session = new SessionState();
