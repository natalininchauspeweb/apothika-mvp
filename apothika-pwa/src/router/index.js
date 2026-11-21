import { createRouter, createWebHistory } from 'vue-router';
import { getLoggedUserSnapshot } from '../services/auth';
import { useTheme } from '../composables/useTheme';

import LoginView from '../views/auth/LoginView.vue';
import RegisterView from '../views/auth/RegisterView.vue';
import PatientHomeView from '../views/patient/PatientHomeView.vue';
import PharmacyDashboardView from '../views/pharmacy/PharmacyDashboardView.vue';

const routes = [
  {
    path: '/',
    name: 'root',
    redirect: () => {
      const user = getLoggedUserSnapshot();
      if (!user.id) return { name: 'login' };
      return user.role === 'pharmacy'
        ? { name: 'pharmacy.dashboard' }
        : { name: 'patient.home' };
    },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: {
      guestOnly: true,
      theme: 'b2c',
      title: 'Iniciar sesión',
    },
  },
  {
    path: '/registro',
    name: 'register',
    component: RegisterView,
    meta: {
      guestOnly: true,
      theme: 'b2c',
      title: 'Crear cuenta',
    },
  },
  {
    path: '/paciente',
    name: 'patient.home',
    component: PatientHomeView,
    meta: {
      requiresAuth: true,
      role: 'patient',
      theme: 'b2c',
      title: 'Mi Club',
    },
  },
  {
    path: '/farmacia',
    name: 'pharmacy.dashboard',
    component: PharmacyDashboardView,
    meta: {
      requiresAuth: true,
      role: 'pharmacy',
      theme: 'b2b',
      title: 'Panel Farmacia',
    },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

/**
 * Navigation Guard global:
 * 1. Bloquea rutas privadas si no hay sesión activa.
 * 2. Evita que usuarios logueados vuelvan a /login o /registro.
 * 3. Impide accesos cruzados entre rol 'patient' (B2C) y 'pharmacy' (B2B).
 */
router.beforeEach((to) => {
  const user = getLoggedUserSnapshot();
  const isAuthenticated = Boolean(user.id);

  if (to.meta.requiresAuth && !isAuthenticated) {
    return { name: 'login' };
  }

  if (to.meta.guestOnly && isAuthenticated) {
    return user.role === 'pharmacy'
      ? { name: 'pharmacy.dashboard' }
      : { name: 'patient.home' };
  }

  if (to.meta.role && user.role !== to.meta.role) {
    return user.role === 'pharmacy'
      ? { name: 'pharmacy.dashboard' }
      : { name: 'patient.home' };
  }

  return true;
});

/**
 * Aplica el motor de marca blanca (B2C) o identidad fija APOTHIKA (B2B)
 * y actualiza el título del documento según la ruta activa.
 */
router.afterEach((to) => {
  const user = getLoggedUserSnapshot();
  const { applyPatientTheme, applyPharmacyB2BTheme, pharmacyBranding } = useTheme();

  if (to.meta.theme === 'b2b') {
    applyPharmacyB2BTheme(user.pharmacy || null);
    document.title = to.meta.title ? `${to.meta.title} — APOTHIKA` : 'APOTHIKA';
  } else {
    applyPatientTheme(user.pharmacy || null);
    const brandName = user.pharmacy?.name || pharmacyBranding.value.name || 'APOTHIKA';
    document.title = to.meta.title ? `${to.meta.title} — ${brandName}` : brandName;
  }
});

export default router;