import { config } from '../config/index.js';
import { dom } from './dom.js';

class ToastManager {
  constructor() {
    this.container = this.createContainer();
  }

  createContainer() {
    const container = dom.create('div', {
      class: 'fixed top-5 right-5 z-[70] space-y-2'
    });
    document.body.appendChild(container);
    return container;
  }

  show(message, type = 'info') {
    const toast = this.createToast(message, type);
    this.container.appendChild(toast);
    setTimeout(() => toast.remove(), config.ui.toastDuration);
  }

  createToast(message, type) {
    const bgColor = type === 'error' ? 'bg-red-500' : 
                   type === 'success' ? 'bg-green-500' : 
                   'bg-blue-500';
    
    return dom.create('div', {
      class: `p-4 rounded-lg shadow-lg text-white ${bgColor}`
    }, [message]);
  }

  success(message) { this.show(message, 'success'); }
  error(message) { this.show(message, 'error'); }
  info(message) { this.show(message, 'info'); }
}

export const toast = new ToastManager();
