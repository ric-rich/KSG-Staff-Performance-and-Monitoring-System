export const config = {
  baseURL: document.baseURI,
  api: {
    timeout: 30000, // 30 seconds
    retries: 3,
    retryDelay: 1000,
  },
  features: {
    analytics: true,
    fileUploads: true,
    darkMode: true
  },
  security: {
    maxFileSize: 2 * 1024 * 1024, // 2MB
    allowedFileTypes: ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'],
    inputDebounce: 300,
  },
  ui: {
    toastDuration: 4000,
    animationDuration: 300,
  }
};
