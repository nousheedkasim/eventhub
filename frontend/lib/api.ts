import axios from 'axios';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Only redirect to login on 401 for authenticated endpoints
    // Public endpoints like events should not trigger redirect
    if (error.response?.status === 401 && error.config?.headers?.Authorization) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export const authAPI = {
  register: (data: any) =>
    api.post('/api/register', data),
  login: (data: { email: string; password: string }) =>
    api.post('/api/login', data),
  me: () => api.get('/api/user'),
};

export const eventsAPI = {
  getAll: (params?: any) => api.get('/api/v1/events', { params }),
  getById: (id: number) => api.get(`/api/v1/events/${id}`),
  create: (data: any) => api.post('/api/v1/events', data),
  update: (id: number, data: any) => api.put(`/api/v1/events/${id}`, data),
  delete: (id: number) => api.delete(`/api/v1/events/${id}`),
};

export const ticketTypesAPI = {
  create: (data: any) => api.post('/api/v1/ticket-type', data),
  getByEvent: (eventId: number) => api.get(`/api/v1/events/${eventId}/ticket-types`),
};

export const ordersAPI = {
  create: (data: any) => api.post('/api/v1/orders', data),
  getAll: (params?: any) => api.get('/api/v1/orders', { params }),
  getById: (id: number) => api.get(`/api/v1/orders/${id}`),
};

export const paymentsAPI = {
  create: (data: { order_id: number; gateway: string }) =>
    api.post('/api/v1/payments', data),
};

export const vendorsAPI = {
  getAll: () => api.get('/api/v1/vendors'),
  getById: (id: number) => api.get(`/api/v1/vendors/${id}`),
  approve: (id: number) => api.post(`/api/v1/vendors/${id}/approve`),
  reject: (id: number) => api.post(`/api/v1/vendors/${id}/reject`),
};

export const payoutsAPI = {
  getAll: (params?: any) => api.get('/api/v1/payouts', { params }),
  create: (data: any) => api.post('/api/v1/payouts', data),
};

export const disputesAPI = {
  getAll: () => api.get('/api/v1/disputes'),
  resolve: (id: number, data: { status: string; resolution: string }) =>
    api.post(`/api/v1/disputes/${id}/resolve`, data),
};

export default api;
