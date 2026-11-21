<script setup>
import { inject, ref } from 'vue';
import { useRouter } from 'vue-router';
import { LogIn, Sparkles, Store, User } from 'lucide-vue-next';
import { login } from '../../services/auth';
import { useTheme } from '../../composables/useTheme';
import { globalFeedbackKey } from '../../symbols/inject';
import BaseLabel from '../../components/form/BaseLabel.vue';
import BaseInput from '../../components/form/BaseInput.vue';
import LoaderButton from '../../components/form/LoaderButton.vue';
import BasePasswordInput from '../../components/form/BasePasswordInput.vue';

const router = useRouter();
const { pharmacyBranding } = useTheme();
const feedback = inject(globalFeedbackKey);

const form = ref({
  email: '',
  password: '',
});

const fieldErrors = ref({});
const isLoading = ref(false);

async function handleSubmit() {
  if (isLoading.value) return;

  isLoading.value = true;
  fieldErrors.value = {};
  feedback.clear();

  try {
    const user = await login(form.value);

    feedback.show({
      type: 'success',
      message: `¡Hola, ${user.name}! Sesión iniciada correctamente.`,
    });

    if (user.role === 'pharmacy') {
      await router.push({ name: 'pharmacy.dashboard' });
    } else {
      await router.push({ name: 'patient.home' });
    }
  } catch (error) {
    fieldErrors.value = error.errors || {};
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos iniciar sesión. Revisá tus datos.',
    });
  } finally {
    isLoading.value = false;
  }
}

function fillDemoCredentials(email, password) {
  form.value.email = email;
  form.value.password = password;
  fieldErrors.value = {};
}
</script>

<template>
  <main class="flex-1 flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-md space-y-6">
      <section class="card-duo p-6 sm:p-8 space-y-6">
        <div class="text-center space-y-3">
          <div class="inline-flex items-center justify-center">
            <img
              v-if="pharmacyBranding.logo_url"
              :src="pharmacyBranding.logo_url"
              :alt="`Logo de ${pharmacyBranding.name}`"
              class="h-14 w-auto object-contain"
            />
            <div
              v-else
              class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-secondary/15 text-brand-primary font-bold text-xs tracking-wide uppercase"
            >
              <Sparkles class="w-4 h-4 text-brand-secondary" aria-hidden="true" />
              <span>{{ pharmacyBranding.name }}</span>
            </div>
          </div>

          <div class="space-y-1">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-primary">
              Iniciar sesión
            </h1>
            <p class="text-sm text-brand-muted">
              Sumá puntos por tus compras de venta libre y trivias de bienestar.
            </p>
          </div>
        </div>

        <form class="space-y-4" novalidate @submit.prevent="handleSubmit">
          <div>
            <BaseLabel for="login-email">Correo electrónico</BaseLabel>
            <BaseInput
              id="login-email"
              v-model="form.email"
              type="email"
              autocomplete="email"
              placeholder="tu@correo.com"
              :has-error="Boolean(fieldErrors.email)"
              required
            />
            <p v-if="fieldErrors.email" class="mt-1 text-xs font-medium text-game-error">
              {{ fieldErrors.email[0] }}
            </p>
          </div>

        <div>
            <BaseLabel for="login-password">Contraseña</BaseLabel>
            <BasePasswordInput
              id="login-password"
              v-model="form.password"
              autocomplete="current-password"
              placeholder="Ingresá tu contraseña"
              :has-error="Boolean(fieldErrors.password)"
              required
            />
            <p v-if="fieldErrors.password" class="mt-1 text-xs font-medium text-game-error">
              {{ fieldErrors.password[0] }}
            </p>
          </div>

          <div class="pt-2">
            <LoaderButton :loading="isLoading" variant="primary" full-width>
              <LogIn class="w-4 h-4" aria-hidden="true" />
              <span>Ingresar</span>
            </LoaderButton>
          </div>
        </form>

        <p class="text-center text-sm text-brand-muted">
          ¿Todavía no sos parte del club?
          <RouterLink
            :to="{ name: 'register' }"
            class="font-bold text-brand-secondary hover:underline"
          >
            Creá tu cuenta gratis
          </RouterLink>
        </p>
      </section>

      <!-- Accesos rápidos para pruebas del MVP (Paciente B2C y Farmacia B2B) -->
      <section class="rounded-2xl border-2 border-dashed border-gray-300 bg-white/75 p-4 space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-brand-muted text-center">
          Accesos rápidos de demostración
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
          <button
            type="button"
            class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-xs font-bold text-brand-primary hover:border-brand-secondary transition cursor-pointer"
            @click="fillDemoCredentials('paciente@dalton.com.ar', 'paciente123')"
          >
            <User class="w-4 h-4 text-brand-secondary" aria-hidden="true" />
            <span>Paciente (Camila)</span>
          </button>

          <button
            type="button"
            class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-xs font-bold text-apothika-emerald hover:border-apothika-emerald transition cursor-pointer"
            @click="fillDemoCredentials('farmacia@dalton.com.ar', 'dalton123')"
          >
            <Store class="w-4 h-4 text-apothika-emerald" aria-hidden="true" />
            <span>Farmacia Dalton (B2B)</span>
          </button>
        </div>
      </section>

      <p class="text-center text-xs text-brand-muted">
        Impulsado por <strong class="font-bold text-apothika-emerald">APOTHIKA</strong>
      </p>
    </div>
  </main>
</template>