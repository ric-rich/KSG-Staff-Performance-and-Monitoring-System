import { config } from '../config/index.js';

export const dom = {
  create(tagName, attributes = {}, children = []) {
    const element = document.createElement(tagName);
    
    Object.entries(attributes).forEach(([key, value]) => {
      if (key.startsWith('on') && typeof value === 'function') {
        element.addEventListener(key.slice(2).toLowerCase(), value);
      } else {
        element.setAttribute(key, value);
      }
    });
    
    children.forEach(child => {
      if (typeof child === 'string') {
        element.appendChild(document.createTextNode(child));
      } else {
        element.appendChild(child);
      }
    });
    
    return element;
  },

  setContent(element, text) {
    element.textContent = text;
  },

  debounce(fn, delay = config.security.inputDebounce) {
    let timeoutId;
    return (...args) => {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => fn(...args), delay);
    };
  },

  sanitizeFileName(name) {
    return name.replace(/[^a-zA-Z0-9.-]/g, '_');
  },

  show(element) {
    element.classList.remove('hidden');
  },

  hide(element) {
    element.classList.add('hidden');
  },

  toggle(element, show) {
    element.classList.toggle('hidden', !show);
  }
};
