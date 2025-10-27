import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:3001/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Add auth token if available
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Handle unauthorized access
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Dashboard API
export const dashboardApi = {
  getSummary: () => api.get('/dashboard/summary'),
  getOnts: (params = {}) => api.get('/dashboard/onts', { params }),
  getPowerStats: () => api.get('/dashboard/power-stats'),
  getEvents: (hours = 24) => api.get('/dashboard/events', { params: { hours } }),
};

// OLT API
export const oltApi = {
  getAll: () => api.get('/olts'),
  getById: (id) => api.get(`/olts/${id}`),
  create: (data) => api.post('/olts', data),
  update: (id, data) => api.put(`/olts/${id}`, data),
  delete: (id) => api.delete(`/olts/${id}`),
  test: (id) => api.post(`/olts/${id}/test`),
  testConnection: (data) => api.post('/olts/test-connection', data),
  getSnmpInfo: (id) => api.get(`/olts/${id}/snmp-info`),
};

// ONT API
export const ontApi = {
  getAll: (params = {}) => api.get('/onts', { params }),
  getById: (id) => api.get(`/onts/${id}`),
  update: (id, data) => api.put(`/onts/${id}`, data),
  getPowerHistory: (id, hours = 24) => api.get(`/onts/${id}/power-history`, { params: { hours } }),
  getByOlt: (oltId) => api.get(`/onts/by-olt/${oltId}`),
};

// Events API
export const eventsApi = {
  getAll: (params = {}) => api.get('/events', { params }),
  getStats: (hours = 24) => api.get('/events/stats', { params: { hours } }),
  markNotified: (eventIds) => api.put('/events/mark-notified', { event_ids: eventIds }),
  cleanup: (days = 30) => api.delete('/events/cleanup', { params: { days } }),
};

// Settings API
export const settingsApi = {
  getAll: () => api.get('/settings'),
  update: (data) => api.put('/settings', data),
  testTelegram: (data) => api.post('/settings/test-telegram', data),
  getTelegramInfo: () => api.get('/settings/telegram-info'),
  getStats: () => api.get('/settings/stats'),
};

// Telegram Users API
export const telegramUsersApi = {
  getAll: () => api.get('/telegram-users'),
  add: (data) => api.post('/telegram-users', data),
  update: (id, data) => api.put(`/telegram-users/${id}`, data),
  delete: (id) => api.delete(`/telegram-users/${id}`),
  checkAccess: (chatId) => api.get(`/telegram-users/check/${chatId}`),
};

// Telegram Chat API
export const telegramChatApi = {
  getAll: (params = {}) => api.get('/telegram-chat', { params }),
  getStats: () => api.get('/telegram-chat/stats'),
  add: (data) => api.post('/telegram-chat', data),
  cleanup: (days = 30) => api.delete('/telegram-chat/cleanup', { params: { days } }),
};

// Health check
export const healthApi = {
  check: () => api.get('/health'),
};

export default api;