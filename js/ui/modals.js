import { dom } from './dom.js';

class ModalManager {
  constructor() {
    this.activeModals = new Set();
    this.initializeBackdrop();
  }

  initializeBackdrop() {
    this.backdrop = dom.create('div', {
      class: 'fixed inset-0 bg-black bg-opacity-50 z-40 hidden'
    });
    document.body.appendChild(this.backdrop);
  }

  show(id) {
    const modal = document.getElementById(id);
    if (modal) {
      dom.show(modal);
      dom.show(this.backdrop);
      this.activeModals.add(modal);
    }
  }

  hide(id) {
    const modal = document.getElementById(id);
    if (modal) {
      dom.hide(modal);
      this.activeModals.delete(modal);
      if (this.activeModals.size === 0) {
        dom.hide(this.backdrop);
      }
    }
  }
}

export const modals = new ModalManager();

export function initModals() {
  // Initialize close buttons
  document.querySelectorAll('[data-modal-close]').forEach(button => {
    button.addEventListener('click', () => {
      const modalId = button.closest('[id]').id;
      modals.hide(modalId);
    });
  });
}
