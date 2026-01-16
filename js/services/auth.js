import { apiCall } from '../api/client.js';
import { eventBus } from '../app.js';

export const auth = {
  async login(email, password, userType = 'user') {
    const endpoint = `api/auth.php?action=${userType}_login`;
    const result = await apiCall(endpoint, 'POST', { email, password });
    if (result.status === 'success') {
      eventBus.emit('auth:login', result.user);
    }
    return result;
  },

  async logout() {
    try {
        await apiCall('api/auth.php?action=logout', 'POST');
        // Clear client-side session
        this.clearSession();
        // Redirect to login page with dynamic base path
        const basePath = document.querySelector('base')?.href?.replace(/\/$/, '') || '';
        window.location.href = `${basePath}/index.php`;
    } catch (error) {
        console.error('Logout error:', error);
        const basePath = document.querySelector('base')?.href?.replace(/\/$/, '') || '';
        window.location.href = `${basePath}/index.php`;
    }
  },

  clearSession() {
    // Clear any stored tokens or data
    localStorage.removeItem('user_settings');
    sessionStorage.clear();
  },

  async changePassword(currentPassword, newPassword) {
    return await apiCall('api/auth.php?action=change_password', 'POST', {
      current_password: currentPassword,
      new_password: newPassword
    });
  },

  async resetPassword(token, newPassword) {
    return await apiCall('api/auth.php?action=reset_password', 'POST', {
      token,
      new_password: newPassword
    });
  }
};
