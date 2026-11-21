<script setup>
import { inject, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Sparkles, UserPlus } from 'lucide-vue-next';
import { register } from '../../services/auth';
import { useTheme } from '../../composables/useTheme';
import { globalFeedbackKey } from '../../symbols/inject';
import BaseLabel from '../../components/form/BaseLabel.vue';
import BaseInput from '../../components/form/BaseInput.vue';
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

async function handleSubmit() {
  if (isLoading.value) return;

  isLoading.value = true;
  fieldErrors.value = {};
  feedback.clear();

  try {
    // Limpiamos puntos o espacios si el paciente escribió el DNI formateado
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
            <BaseInput
              id="register-password"
              v-model="form.password"
              type="password"
              autocomplete="new-password"
              placeholder="Mínimo 6 caracteres"
              :has-error="Boolean(fieldErrors.password)"
              required
            />
            <p v-if="fieldErrors.password" class="mt-1 text-xs font-medium text-game-error">
              {{ fieldErrors.password[0] }}
            </p>
          </div>

          <div>
            <BaseLabel for="register-password-confirmation">Confirmar contraseña</BaseLabel>
            <BaseInput
              id="register-password-confirmation"
              v-model="form.password_confirmation"
              type="password"
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