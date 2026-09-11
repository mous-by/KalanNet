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

// Set by AuthContext so a maintenance response from ANY request (not just
// one specific screen) immediately swaps the whole app to a full-screen
// notice — mirrors the web's CheckMaintenanceMode middleware, which replaces
// every page the same way regardless of which one was requested.
let onMaintenance: ((message: string) => void) | null = null;

export function registerMaintenanceHandler(handler: (message: string) => void) {
  onMaintenance = handler;
}

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      await clearToken();
      onUnauthorized?.();
    }
    if (error.response?.status === 503 && error.response.data?.maintenance === true) {
      onMaintenance?.(error.response.data?.message || 'KalanNet est actuellement en maintenance.');
    }
    return Promise.reject(error);
  }
);

export function isSubscriptionBlockedError(error: unknown): boolean {
  return axios.isAxiosError(error) && error.response?.status === 403 && error.response.data?.subscription_blocked === true;
}

// No response reached the app at all (offline, DNS/LAN unreachable, timeout)
// as opposed to a server-returned error (4xx/5xx), which needs a real fix.
export function isNetworkError(error: unknown): boolean {
  return axios.isAxiosError(error) && !error.response;
}

export function apiErrorMessage(error: unknown, fallback = 'Une erreur est survenue.'): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data;

    // A Laravel debug-mode crash dump (uncaught exception): its "message" is a
    // raw PHP/technical string, not something meant for an end user — always
    // fall back to French rather than surface it verbatim.
    if (data?.exception) return fallback;

    if (data?.errors) {
      const firstField = Object.values(data.errors)[0];
      if (Array.isArray(firstField) && firstField.length > 0) return firstField[0] as string;
    }
    if (data?.message) return data.message as string;
  }
  return fallback;
}
