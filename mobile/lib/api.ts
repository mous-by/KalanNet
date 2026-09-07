import axios from 'axios';
import { clearToken, getToken } from './storage';

// Set EXPO_PUBLIC_API_URL in mobile/.env for a real device/emulator (it
// must be reachable from the phone, so "localhost" only works for the web
// preview — use your machine's LAN IP or a tunnel for Android/iOS).
export const API_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1';

export const api = axios.create({
  baseURL: API_URL,
  headers: { Accept: 'application/json' },
});

api.interceptors.request.use(async (config) => {
  const token = await getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Set by AuthContext so a 401 anywhere (expired/revoked token) forces a
// clean logout instead of leaving the app stuck on a failed screen.
let onUnauthorized: (() => void) | null = null;

export function registerUnauthorizedHandler(handler: () => void) {
  onUnauthorized = handler;
}

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      await clearToken();
      onUnauthorized?.();
    }
    return Promise.reject(error);
  }
);

export function isSubscriptionBlockedError(error: unknown): boolean {
  return axios.isAxiosError(error) && error.response?.status === 403 && error.response.data?.subscription_blocked === true;
}

export function apiErrorMessage(error: unknown, fallback = 'Une erreur est survenue.'): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data;
    if (data?.errors) {
      const firstField = Object.values(data.errors)[0];
      if (Array.isArray(firstField) && firstField.length > 0) return firstField[0] as string;
    }
    if (data?.message) return data.message as string;
  }
  return fallback;
}
