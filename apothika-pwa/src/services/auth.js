import { apiRequest, getStoredToken, setStoredToken } from './api';

const USER_STORAGE_KEY = 'apothika_user';

const EMPTY_USER = {
  id: null,
  pharmacy_id: null,
  name: null,
  email: null,
  role: null,
  dni: null,
  points_balance: 0,
  qr_code: null,
  pharmacy: null,
};

let loggedUser = loadUserFromStorage();
let observers = [];

function loadUserFromStorage() {
  try {
    const raw = localStorage.getItem(USER_STORAGE_KEY);
    if (!raw || !getStoredToken()) {
      return { ...EMPTY_USER };
    }
    return { ...EMPTY_USER, ...JSON.parse(raw) };
  } catch {
    return { ...EMPTY_USER };
  }
}

/**
 * Función centralizada para actualizar el usuario en memoria, sincronizar
 * localStorage y notificar a todos los observers suscriptos.
 */
function setLoggedUser(userData) {
  if (!userData || !userData.id) {
    loggedUser = { ...EMPTY_USER };
    localStorage.removeItem(USER_STORAGE_KEY);
  } else {
    loggedUser = { ...EMPTY_USER, ...userData };
    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(loggedUser));
  }

  notifyAll();
}

function notify(callback) {
  callback({ ...loggedUser });
}

function notifyAll() {
  observers.forEach((callback) => notify(callback));
}

/**
 * Patrón Observer: suscribe un callback a los cambios de autenticación
 * y devuelve la función para cancelar dicha suscripción (evita memory leaks).
 */
export function subscribeToAuth(callback) {
  observers.push(callback);
  notify(callback);

  return () => {
    observers = observers.filter((obs) => obs !== callback);
  };
}

export function getLoggedUserSnapshot() {
  return { ...loggedUser };
}

/**
 * Sincroniza los datos del usuario autenticado contra el backend (/auth/me).
 * Si el token expiró o fue revocado (401), limpia la sesión local.
 */
export async function syncAuthSession() {
  if (!getStoredToken()) {
    setLoggedUser(null);
    return null;
  }

  try {
    const data = await apiRequest('/auth/me');
    setLoggedUser(data.user);
    return data.user;
  } catch (error) {
    if (error.status === 401) {
      setStoredToken(null);
      setLoggedUser(null);
    }
    return null;
  }
}

export async function login({ email, password }) {
  const data = await apiRequest('/auth/login', {
    method: 'POST',
    body: { email, password },
    auth: false,
  });

  setStoredToken(data.token);
  setLoggedUser(data.user);
  return data.user;
}

export async function register({ name, email, dni, password, password_confirmation, pharmacy_slug }) {
  const data = await apiRequest('/auth/register', {
    method: 'POST',
    body: {
      name,
      email,
      dni,
      password,
      password_confirmation,
      pharmacy_slug: pharmacy_slug || import.meta.env.VITE_DEFAULT_PHARMACY_SLUG || 'farmacia-dalton',
    },
    auth: false,
  });

  setStoredToken(data.token);
  setLoggedUser(data.user);
  return data.user;
}

export async function logout() {
  try {
    if (getStoredToken()) {
      await apiRequest('/auth/logout', { method: 'POST' });
    }
  } finally {
    setStoredToken(null);
    setLoggedUser(null);
  }
}

/**
 * Actualiza el saldo de puntos en tiempo real en toda la UI (header, perfil, catálogo)
 * luego de responder una trivia o canjear un cupón.
 */
export function updateLocalPoints(newBalance) {
  if (!loggedUser.id) return;
  setLoggedUser({
    ...loggedUser,
    points_balance: newBalance,
  });
}

/**
 * Actualiza los datos de marca blanca guardados en el usuario de la farmacia.
 */
export function updateLocalPharmacy(pharmacy) {
  if (!loggedUser.id) return;
  setLoggedUser({
    ...loggedUser,
    pharmacy,
  });
}