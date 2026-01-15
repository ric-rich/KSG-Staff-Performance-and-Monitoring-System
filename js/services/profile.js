import { apiCall } from '../api/client.js';
import { eventBus } from '../app.js';

export const profile = {
  async get() {
    const result = await apiCall('api/user.php?action=get_profile');
    eventBus.emit('profile:loaded', result.data);
    return result;
  },

  async update(profileData) {
    const result = await apiCall('api/user.php?action=update_profile', 'PUT', profileData);
    eventBus.emit('profile:updated', result.data);
    return result;
  },

  async updateAvatar(file) {
    const formData = new FormData();
    formData.append('profile_picture', file);
    
    const result = await apiCall('api/user.php?action=upload_profile_picture', 'POST', formData);
    eventBus.emit('profile:avatar-updated', result.data);
    return result;
  }
};
