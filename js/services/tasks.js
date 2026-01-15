import { apiCall } from '../api/client.js';
import { eventBus } from '../app.js';

export const tasks = {
  async getAll(filter = 'all', search = '') {
    const result = await apiCall(`api/user.php?action=get_tasks&filter=${filter}&search=${search}`);
    eventBus.emit('tasks:loaded', result.tasks);
    return result;
  },

  async create(taskData) {
    const result = await apiCall('api/user.php?action=create_task', 'POST', taskData);
    eventBus.emit('task:created', result.task);
    return result;
  },

  async update(taskId, data) {
    const result = await apiCall(`api/user.php?action=update_task&task_id=${taskId}`, 'PUT', data);
    eventBus.emit('task:updated', result.task);
    return result;
  },

  async delete(taskId) {
    const result = await apiCall(`api/user.php?action=delete_task&task_id=${taskId}`, 'DELETE');
    eventBus.emit('task:deleted', taskId);
    return result;
  }
};
