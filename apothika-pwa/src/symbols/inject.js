/**
 * Clave única basada en Symbol para proveer/inyectar el sistema
 * de notificaciones globales sin riesgo de colisiones de strings.
 */
export const globalFeedbackKey = Symbol('globalFeedbackKey');