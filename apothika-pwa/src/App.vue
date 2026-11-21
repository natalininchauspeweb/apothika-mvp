<script setup>
import { onMounted, provide, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { LogOut, Store, User } from 'lucide-vue-next';
import { logout, syncAuthSession } from './services/auth';
import { useAuth } from './composables/useAuth';
import { useTheme } from './composables/useTheme';
import { globalFeedbackKey } from './symbols/inject';
import NotificationBox from './components/NotificationBox.vue';

const router = useRouter();
const route = useRoute();
const { loggedUser } = useAuth();
const { pharmacyBranding, loadPublicBranding, applyPatientTheme, applyPharmacyB2BTheme } = useTheme();

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

// Proveemos el sistema de notificaciones globales mediante Symbol (sin prop drilling)
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
  // 1. Cargamos el branding público desde la API (caso piloto: Farmacia Dalton)
  await loadPublicBranding();

  // 2. Si había un token guardado en localStorage, verificamos contra /api/auth/me
  const freshUser = await syncAuthSession();

  if (freshUser) {
    if (route.meta.theme === 'b2b' || freshUser.role === 'pharmacy') {
      applyPharmacyB2BTheme(freshUser.pharmacy);
    } else {
      applyPatientTheme(freshUser.pharmacy);
    }
  } else if (route.meta.requiresAuth) {
    // Si el token venció o fue revocado, redirigimos a login
    await router.push({ name: 'login' });
  }
});
</script>

<template>
  <div class="min-h-dvh flex flex-col bg-brand-surface text-brand-text transition-colors duration-200">
    <!-- Encabezado superior visible únicamente cuando hay sesión iniciada -->
    <header
      v-if="loggedUser.id"
      class="sticky top-0 z-30 border-b-2 border-black/10"
      :class="loggedUser.role === 'pharmacy' ? 'bg-apothika-emerald text-white' : 'bg-brand-primary text-white'"
    >
      <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
        <RouterLink
          :to="loggedUser.role === 'pharmacy' ? { name: 'pharmacy.dashboard' } : { name: 'patient.home' }"
          class="inline-flex items-center gap-2.5 font-heading font-extrabold text-lg tracking-tight"
        >
          <span
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

        <nav aria-label="Acciones de cuenta" class="flex items-center gap-3">
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
        </nav>
      </div>
    </header>

    <!-- Notificación global inyectada vía provide/inject -->
    <div
      v-if="feedback.message"
      class="max-w-3xl w-full mx-auto px-4 pt-4"
    >
      <NotificationBox :content="feedback" @close="clearFeedback" />
    </div>

    <!-- Vista activa según Vue Router -->
    <RouterView />
  </div>
</template>