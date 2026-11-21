const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';
const TOKEN_STORAGE_KEY = 'apothika_token';

export function getStoredToken() {
  return localStorage.getItem(TOKEN_STORAGE_KEY);
}

export function setStoredToken(token) {
  if (token) {
    localStorage.setItem(TOKEN_STORAGE_KEY, token);
  } else {
    localStorage.removeItem(TOKEN_STORAGE_KEY);
  }
}

/**
 * Cliente HTTP unificado para toda la PWA.
 * Soporta tanto JSON como FormData (para subida de logos e imágenes de premios).
 */
export async function apiRequest(endpoint, { method = 'GET', body = null, auth = true } = {}) {
  const headers = {
    Accept: 'application/json',
  };

  if (auth) {
    const token = getStoredToken();
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }
  }

  const isFormData = body instanceof FormData;
  if (body && !isFormData) {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    method,
    headers,
    body: body ? (isFormData ? body : JSON.stringify(body)) : null,
  });

  // Intentamos parsear JSON de forma segura incluso si la respuesta está vacía
  let data = null;
  try {
    data = await response.json();
  } catch {
    data = null;
  }

  if (!response.ok) {
    const error = new Error(data?.message || 'Ocurrió un error al comunicarse con el servidor.');
    error.status = response.status;
    error.errors = data?.errors || null;
    throw error;
  }

  return data;
}