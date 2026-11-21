import { onUnmounted, ref } from 'vue';
import { subscribeToAuth } from '../services/auth';

/**
 * Composable reutilizable para consumir el usuario autenticado de forma reactiva
 * y limpiar automáticamente la suscripción al desmontar el componente.
 */
export function useAuth() {
  const loggedUser = ref({
    id: null,
    pharmacy_id: null,
    name: null,
    email: null,
    role: null,
    dni: null,
    points_balance: 0,
    qr_code: null,
    pharmacy: null,
  });

  const unsubscribeFromAuth = subscribeToAuth((userData) => {
    loggedUser.value = userData;
  });

  onUnmounted(() => {
    unsubscribeFromAuth();
  });

  return {
    loggedUser,
  };
}