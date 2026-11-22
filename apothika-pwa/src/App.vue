<script setup>
import { computed, onMounted, provide, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  Gift,
  Home,
  LayoutDashboard,
  LogOut,
  Palette,
  QrCode,
  Sparkles,
  Store,
  Ticket,
  User,
} from 'lucide-vue-next';
import { logout, syncAuthSession } from './services/auth';
import { useAuth } from './composables/useAuth';
import { useTheme } from './composables/useTheme';
import { globalFeedbackKey } from './symbols/inject';
import NotificationBox from './components/NotificationBox.vue';

const router = useRouter();
const route = useRoute();
const { loggedUser } = useAuth();
const { pharmacyBranding, loadPublicBranding, applyPatientTheme, applyPharmacyB2BTheme } = useTheme();

const PATIENT_NAV_ITEMS = [
  { name: 'patient.home', label: 'Inicio', icon: Home },
  { name: 'patient.trivias', label: 'Trivias', icon: Sparkles },
  { name: 'patient.rewards', label: 'Beneficios', icon: Gift },
  { name: 'patient.coupons', label: 'Cupones', icon: Ticket },
];

const PHARMACY_NAV_ITEMS = [
  { name: 'pharmacy.dashboard', label: 'Resumen', icon: LayoutDashboard },
  { name: 'pharmacy.counter', label: 'Mostrador', icon: QrCode },
  { name: 'pharmacy.rewards', label: 'Beneficios', icon: Gift },
  { name: 'pharmacy.branding', label: 'Marca', icon: Palette },
];

const activeNavItems = computed(() =>
  loggedUser.value.role === 'pharmacy' ? PHARMACY_NAV_ITEMS : PATIENT_NAV_ITEMS
);

const feedback = ref({
  message: null,
  type: 'info',
  closable: true,
});

let feedbackTimer = null;

function clearFeedback() {
  if (feedbackTimer) {
    clearTimeout(feedbackTimer);
    feedbackTimer = null;
  }
  feedback.value.message = null;
}

function showFeedback({ message, type = 'info', closable = true, duration = 5000 }) {
  clearFeedback();
  feedback.value = { message, type, closable };

  if (duration > 0) {
    feedbackTimer = setTimeout(() => {
      feedback.value.message = null;
    }, duration);
  }
}

provide(globalFeedbackKey, {
  show: showFeedback,
  clear: clearFeedback,
});

async function handleLogout() {
  await logout();
  showFeedback({
    type: 'info',
    message: 'Cerraste sesión correctamente.',
  });
  await router.push({ name: 'login' });
}

onMounted(async () => {
  await loadPublicBranding();

  const freshUser = await syncAuthSession();

  if (freshUser) {
    if (route.meta.theme === 'b2b' || freshUser.role === 'pharmacy') {
      applyPharmacyB2BTheme(freshUser.pharmacy);
      document.title = route.meta.title ? `${route.meta.title} — APOTHIKA` : 'APOTHIKA';
    } else {
      applyPatientTheme(freshUser.pharmacy);
      const brandName = freshUser.pharmacy?.name || pharmacyBranding.value.name || 'APOTHIKA';
      document.title = route.meta.title ? `${route.meta.title} — ${brandName}` : brandName;
    }
  } else if (route.meta.requiresAuth) {
    await router.push({ name: 'login' });
  }
});
</script>

<template>
  <div
    class="min-h-dvh flex flex-col bg-brand-surface text-brand-text transition-colors duration-200"
    :class="loggedUser.id ? 'pb-20 md:pb-8' : 'pb-8'"
  >
    <!-- Encabezado superior -->
    <header
      v-if="loggedUser.id"
      class="sticky top-0 z-30 border-b-2 border-black/10"
      :class="loggedUser.role === 'pharmacy' ? 'bg-apothika-emerald text-white' : 'bg-brand-primary text-white'"
    >
      <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
        <RouterLink
          :to="loggedUser.role === 'pharmacy' ? { name: 'pharmacy.dashboard' } : { name: 'patient.home' }"
          class="inline-flex items-center gap-2.5 font-heading font-extrabold text-lg tracking-tight shrink-0"
        >
          <!-- Logo dinámico en vista Paciente (B2C) -->
          <span
            v-if="loggedUser.role !== 'pharmacy' && pharmacyBranding.logo_url"
            class="h-9 px-1.5 py-1 rounded-xl bg-white inline-flex items-center justify-center shrink-0 shadow-xs"
          >
            <img
              :src="pharmacyBranding.logo_url"
              :alt="`Logo de ${pharmacyBranding.name}`"
              class="h-full w-auto max-w-24 object-contain"
            />
          </span>

          <!-- Ícono de respaldo si no hay logo o si está en panel B2B APOTHIKA -->
          <span
            v-else
            class="w-8 h-8 rounded-xl inline-flex items-center justify-center shrink-0"
            :class="loggedUser.role === 'pharmacy' ? 'bg-apothika-mint text-apothika-emerald' : 'bg-brand-secondary text-white'"
          >
            <Store v-if="loggedUser.role === 'pharmacy'" class="w-4 h-4" aria-hidden="true" />
            <User v-else class="w-4 h-4" aria-hidden="true" />
          </span>

          <span>
            {{ loggedUser.role === 'pharmacy' ? 'APOTHIKA' : pharmacyBranding.name }}
          </span>
        </RouterLink>

        <!-- Navegación de escritorio (B2C y B2B) -->
        <nav
          aria-label="Navegación principal"
          class="hidden md:flex items-center gap-1"
        >
          <RouterLink
            v-for="item in activeNavItems"
            :key="item.name"
            :to="{ name: item.name }"
            :aria-current="route.name === item.name ? 'page' : undefined"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition"
            :class="
              route.name === item.name
                ? loggedUser.role === 'pharmacy'
                  ? 'bg-apothika-mint text-apothika-emerald'
                  : 'bg-brand-secondary text-white'
                : 'text-white/80 hover:bg-white/10 hover:text-white'
            "
          >
            <component :is="item.icon" class="w-4 h-4" aria-hidden="true" />
            <span>{{ item.label }}</span>
          </RouterLink>
        </nav>

        <div class="flex items-center gap-3 shrink-0">
          <span class="hidden sm:inline text-xs font-medium text-white/85">
            {{ loggedUser.name }}
          </span>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-xs font-bold transition cursor-pointer"
            @click="handleLogout"
          >
            <LogOut class="w-4 h-4" aria-hidden="true" />
            <span>Salir</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Notificación global flotante (siempre visible sin importar el scroll) -->
    <div
      v-if="feedback.message"
      class="fixed top-20 inset-x-0 z-40 px-4 pointer-events-none"
    >
      <div class="max-w-3xl mx-auto pointer-events-auto shadow-lg rounded-2xl bg-white">
        <NotificationBox :content="feedback" @close="clearFeedback" />
      </div>
    </div>

    <!-- Vista activa -->
    <RouterView />

    <!-- Barra de navegación inferior móvil (B2C y B2B) -->
    <nav
      v-if="loggedUser.id"
      aria-label="Navegación móvil"
      class="md:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t-2 border-gray-200 px-2 py-1.5"
    >
      <ul class="grid grid-cols-4 gap-1 max-w-md mx-auto">
        <li v-for="item in activeNavItems" :key="item.name">
          <RouterLink
            :to="{ name: item.name }"
            :aria-current="route.name === item.name ? 'page' : undefined"
            class="flex flex-col items-center justify-center gap-0.5 py-1.5 rounded-xl text-[11px] font-bold transition"
            :class="
              route.name === item.name
                ? loggedUser.role === 'pharmacy'
                  ? 'text-apothika-emerald bg-apothika-mint/20'
                  : 'text-brand-secondary bg-brand-secondary/10'
                : 'text-brand-muted hover:text-brand-primary'
            "
          >
            <component :is="item.icon" class="w-5 h-5" aria-hidden="true" />
            <span>{{ item.label }}</span>
          </RouterLink>
        </li>
      </ul>
    </nav>
  </div>
</template>