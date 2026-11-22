import { apiRequest } from './api';

/**
 * Obtiene las métricas generales y las últimas 10 transacciones de la farmacia.
 */
export async function fetchPharmacyDashboard() {
  return apiRequest('/pharmacy/dashboard');
}

/**
 * Busca un paciente de la farmacia por código QR, DNI o correo electrónico.
 */
export async function lookupPharmacyPatient(query) {
  const params = new URLSearchParams({ q: query.trim() });
  return apiRequest(`/pharmacy/patients/lookup?${params.toString()}`);
}

/**
 * Acredita puntos a un paciente por compra de venta libre (OTC) en mostrador.
 */
export async function awardOtcPurchasePoints(payload) {
  return apiRequest('/pharmacy/points/award', {
    method: 'POST',
    body: payload,
  });
}

/**
 * Valida y marca como utilizado un cupón presentado en caja.
 */
export async function validatePharmacyCoupon(redeemCode) {
  return apiRequest('/pharmacy/coupons/validate', {
    method: 'POST',
    body: {
      redeem_code: redeemCode.trim().toUpperCase(),
    },
  });
}

/**
 * Lista todos los beneficios de la farmacia (activos y pausados) para administración.
 */
export async function fetchPharmacyRewards() {
  return apiRequest('/pharmacy/rewards');
}

/**
 * Crea un nuevo beneficio (soporta subida de imagen vía FormData).
 */
export async function createPharmacyReward(formData) {
  return apiRequest('/pharmacy/rewards', {
    method: 'POST',
    body: formData,
  });
}

/**
 * Actualiza un beneficio existente (usa POST para compatibilidad con multipart/form-data en PHP).
 */
export async function updatePharmacyReward(rewardId, formData) {
  return apiRequest(`/pharmacy/rewards/${rewardId}`, {
    method: 'POST',
    body: formData,
  });
}

/**
 * Alterna rápidamente el estado activo/pausado de un beneficio.
 */
export async function togglePharmacyReward(rewardId) {
  return apiRequest(`/pharmacy/rewards/${rewardId}/toggle`, {
    method: 'PATCH',
  });
}

/**
 * Elimina un beneficio y su imagen asociada en File Storage.
 */
export async function deletePharmacyReward(rewardId) {
  return apiRequest(`/pharmacy/rewards/${rewardId}`, {
    method: 'DELETE',
  });
}

/**
 * Actualiza la identidad visual White-Label y la tasa de puntos de la farmacia.
 */
export async function updatePharmacyBranding(formData) {
  return apiRequest('/pharmacy/branding', {
    method: 'POST',
    body: formData,
  });
}