import { createRouter, createWebHistory } from 'vue-router';
import { getLoggedUserSnapshot } from '../services/auth';
import { useTheme } from '../composables/useTheme';

import LoginView from '../views/auth/LoginView.vue';
import RegisterView from '../views/auth/RegisterView.vue';
import PatientHomeView from '../views/patient/PatientHomeView.vue';
import PatientTriviasView from '../views/patient/PatientTriviasView.vue';
import PatientRewardsView from '../views/patient/PatientRewardsView.vue';
import PatientCouponsView from '../views/patient/PatientCouponsView.vue';
import PharmacyDashboardView from '../views/pharmacy/PharmacyDashboardView.vue';
import PharmacyCounterView from '../views/pharmacy/PharmacyCounterView.vue';
import PharmacyRewardsView from '../views/pharmacy/PharmacyRewardsView.vue';
import PharmacyBrandingView from '../views/pharmacy/PharmacyBrandingView.vue';

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
    path: '/paciente/trivias',
    name: 'patient.trivias',
    component: PatientTriviasView,
    meta: {
      requiresAuth: true,
      role: 'patient',
      theme: 'b2c',
      title: 'Trivias de bienestar',
    },
  },
  {
    path: '/paciente/beneficios',
    name: 'patient.rewards',
    component: PatientRewardsView,
    meta: {
      requiresAuth: true,
      role: 'patient',
      theme: 'b2c',
      title: 'Catálogo de beneficios',
    },
  },
  {
    path: '/paciente/cupones',
    name: 'patient.coupons',
    component: PatientCouponsView,
    meta: {
      requiresAuth: true,
      role: 'patient',
      theme: 'b2c',
      title: 'Mis cupones',
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
    path: '/farmacia/mostrador',
    name: 'pharmacy.counter',
    component: PharmacyCounterView,
    meta: {
      requiresAuth: true,
      role: 'pharmacy',
      theme: 'b2b',
      title: 'Mostrador',
    },
  },
  {
    path: '/farmacia/beneficios',
    name: 'pharmacy.rewards',
    component: PharmacyRewardsView,
    meta: {
      requiresAuth: true,
      role: 'pharmacy',
      theme: 'b2b',
      title: 'Beneficios',
    },
  },
  {
    path: '/farmacia/marca',
    name: 'pharmacy.branding',
    component: PharmacyBrandingView,
    meta: {
      requiresAuth: true,
      role: 'pharmacy',
      theme: 'b2b',
      title: 'Marca Blanca',
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