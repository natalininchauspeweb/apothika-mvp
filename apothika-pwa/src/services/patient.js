import { apiRequest } from './api';

/**
 * Obtiene el saldo actual, código QR, tasa OTC y el historial paginado del paciente.
 */
export async function fetchPatientPoints({ type = '', page = 1 } = {}) {
  const params = new URLSearchParams();

  if (type) {
    params.set('type', type);
  }
  if (page > 1) {
    params.set('page', String(page));
  }

  const query = params.toString();
  return apiRequest(`/patient/points${query ? `?${query}` : ''}`);
}

/**
 * Lista las trivias activas indicando cuáles ya completó el paciente.
 */
export async function fetchPatientTrivias() {
  return apiRequest('/patient/trivias');
}

/**
 * Envía la opción elegida de una trivia para validación en el servidor.
 */
export async function answerPatientTrivia(triviaId, selectedOptionIndex) {
  return apiRequest(`/patient/trivias/${triviaId}/answer`, {
    method: 'POST',
    body: {
      selected_option_index: selectedOptionIndex,
    },
  });
}

/**
 * Lista los beneficios activos de la farmacia del paciente.
 */
export async function fetchPatientRewards() {
  return apiRequest('/patient/rewards');
}

/**
 * Canjea puntos del paciente por un cupón del catálogo.
 */
export async function redeemPatientReward(rewardId) {
  return apiRequest(`/patient/rewards/${rewardId}/redeem`, {
    method: 'POST',
  });
}

/**
 * Lista los cupones canjeados por el paciente (disponibles y usados).
 */
export async function fetchPatientCoupons() {
  return apiRequest('/patient/coupons');
}