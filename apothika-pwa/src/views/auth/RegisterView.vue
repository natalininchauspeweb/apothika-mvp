<script setup>
import { computed, inject, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Check, Circle, Sparkles, UserPlus } from 'lucide-vue-next';
import { register } from '../../services/auth';
import { useTheme } from '../../composables/useTheme';
import { globalFeedbackKey } from '../../symbols/inject';
import BaseLabel from '../../components/form/BaseLabel.vue';
import BaseInput from '../../components/form/BaseInput.vue';
import BasePasswordInput from '../../components/form/BasePasswordInput.vue';
import LoaderButton from '../../components/form/LoaderButton.vue';

const router = useRouter();
const { pharmacyBranding } = useTheme();
const feedback = inject(globalFeedbackKey);

const form = ref({
  name: '',
  dni: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const fieldErrors = ref({});
const isLoading = ref(false);

const passwordChecks = computed(() => {
  const pwd = form.value.password || '';
  return [
    { label: 'Al menos 8 caracteres', met: pwd.length >= 8 },
    { label: 'Al menos una letra y un número', met: /[a-zA-Z]/.test(pwd) && /[0-9]/.test(pwd) },
  ];
});

async function handleSubmit() {
  if (isLoading.value) return;

  isLoading.value = true;
  fieldErrors.value = {};
  feedback.clear();

  try {
    const cleanDni = form.value.dni.replace(/[^0-9]/g, '');

    const user = await register({
      ...form.value,
      dni: cleanDni,
      pharmacy_slug: pharmacyBranding.value.slug,
    });

    feedback.show({
      type: 'success',
      message: `¡Bienvenido/a, ${user.name}! Ya tenés tu pase digital activo.`,
    });

    await router.push({ name: 'patient.home' });
  } catch (error) {
    fieldErrors.value = error.errors || {};
    feedback.show({
      type: 'error',
      message: error.message || 'Revisá los datos del formulario antes de continuar.',
    });
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <main class="flex-1 flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-md space-y-6">
      <section class="card-duo p-6 sm:p-8 space-y-6">
        <div class="text-center space-y-3">
          <div
            class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-secondary/15 text-brand-primary font-bold text-xs tracking-wide uppercase"
          >
            <Sparkles class="w-4 h-4 text-brand-secondary" aria-hidden="true" />
            <span>{{ pharmacyBranding.name }}</span>
          </div>

          <div class="space-y-1">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-primary">
              Creá tu cuenta
            </h1>
            <p class="text-sm text-brand-muted">
              Obtené tu código QR personal para sumar puntos en cada compra de venta libre.
            </p>
          </div>
        </div>

        <form class="space-y-4" novalidate @submit.prevent="handleSubmit">
          <div>
            <BaseLabel for="register-name">Nombre y apellido</BaseLabel>
            <BaseInput
              id="register-name"
              v-model="form.name"
              type="text"
              autocomplete="name"
              placeholder="Ej: Valentina Pérez"
              :has-error="Boolean(fieldErrors.name)"
              required
            />
            <p v-if="fieldErrors.name" class="mt-1 text-xs font-medium text-game-error">
              {{ fieldErrors.name[0] }}
            </p>
          </div>

          <div>
            <BaseLabel for="register-dni">DNI (sin puntos)</BaseLabel>
            <BaseInput
              id="register-dni"
              v-model="form.dni"
              type="text"
              inputmode="numeric"
              placeholder="Ej: 39123456"
              :has-error="Boolean(fieldErrors.dni)"
              required
            />
            <p v-if="fieldErrors.dni" class="mt-1 text-xs font-medium text-game-error">
              {{ fieldErrors.dni[0] }}
            </p>
          </div>

          <div>
            <BaseLabel for="register-email">Correo electrónico</BaseLabel>
            <BaseInput
              id="register-email"
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
            <BaseLabel for="register-password">Contraseña</BaseLabel>
            <BasePasswordInput
              id="register-password"
              v-model="form.password"
              autocomplete="new-password"
              placeholder="Mínimo 8 caracteres, letras y números"
              aria-describedby="password-requirements"
              :has-error="Boolean(fieldErrors.password)"
              required
            />
            <ul id="password-requirements" class="mt-2 space-y-1">
              <li
                v-for="check in passwordChecks"
                :key="check.label"
                class="flex items-center gap-1.5 text-xs transition-colors"
                :class="check.met ? 'text-game-success font-semibold' : 'text-brand-muted'"
              >
                <Check v-if="check.met" class="w-3.5 h-3.5 shrink-0" aria-hidden="true" />
                <Circle v-else class="w-3.5 h-3.5 shrink-0 opacity-50" aria-hidden="true" />
                <span>{{ check.label }}</span>
              </li>
            </ul>
            <p v-if="fieldErrors.password" class="mt-1 text-xs font-medium text-game-error">
              {{ fieldErrors.password[0] }}
            </p>
          </div>

          <div>
            <BaseLabel for="register-password-confirmation">Confirmar contraseña</BaseLabel>
            <BasePasswordInput
              id="register-password-confirmation"
              v-model="form.password_confirmation"
              autocomplete="new-password"
              placeholder="Repetí tu contraseña"
              :has-error="Boolean(fieldErrors.password)"
              required
            />
          </div>

          <div class="pt-2">
            <LoaderButton :loading="isLoading" variant="primary" full-width>
              <UserPlus class="w-4 h-4" aria-hidden="true" />
              <span>Registrarme en el club</span>
            </LoaderButton>
          </div>
        </form>

        <p class="text-center text-sm text-brand-muted">
          ¿Ya tenés cuenta?
          <RouterLink
            :to="{ name: 'login' }"
            class="font-bold text-brand-secondary hover:underline"
          >
            Iniciá sesión acá
          </RouterLink>
        </p>
      </section>
    </div>
  </main>
</template>