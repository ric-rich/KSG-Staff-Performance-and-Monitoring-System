import { config } from '../config/index.js';

export class ApiError extends Error {
  constructor(message, status, code) {
    super(message);
    this.status = status;
    this.code = code;
  }
}

const defaultOptions = {
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
};

async function fetchWithTimeout(url, options = {}) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), config.api.timeout);

  try {
    const response = await fetch(url, {
      ...options,
      signal: controller.signal
    });
    return response;
  } finally {
    clearTimeout(timeout);
  }
}

async function retryRequest(url, options, retries = config.api.retries) {
  let lastError;
  
  for (let i = 0; i < retries; i++) {
    try {
      const response = await fetchWithTimeout(url, options);
      
      if (!response.ok) {
        if (response.status >= 500 && i < retries - 1) {
          await new Promise(resolve => 
            setTimeout(resolve, config.api.retryDelay * Math.pow(2, i))
          );
          continue;
        }
        throw new ApiError(
          response.statusText,
          response.status,
          'API_ERROR'
        );
      }

      return await response.json();
    } catch (error) {
      lastError = error;
      if (error.name === 'AbortError') {
        throw new ApiError('Request timeout', 408, 'TIMEOUT');
      }
      if (i === retries - 1) break;
    }
  }
  
  throw lastError;
}

export async function apiCall(endpoint, method = 'GET', data = null) {
  const url = new URL(endpoint, config.baseURL);
  const options = { ...defaultOptions, method };

  if (data) {
    if (data instanceof FormData) {
      delete options.headers['Content-Type'];
      options.body = data;
    } else {
      options.body = JSON.stringify(data);
    }
  }

  try {
    return await retryRequest(url, options);
  } catch (error) {
    console.error(`API Error (${endpoint}):`, error);
    throw error;
  }
}
