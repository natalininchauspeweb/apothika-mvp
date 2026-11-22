<script setup>
import { computed, inject, onMounted, onUnmounted, ref } from 'vue';
import {
  Check,
  Coins,
  ImagePlus,
  Palette,
  RotateCcw,
  Save,
  Smartphone,
  Sparkles,
  Type,
} from 'lucide-vue-next';
import { fetchPharmacyDashboard, updatePharmacyBranding } from '../../services/pharmacy';
import { DEFAULT_B2C_BRANDING, FONT_PRESET_OPTIONS } from '../../services/branding';
import { updateLocalPharmacy } from '../../services/auth';
import { useTheme } from '../../composables/useTheme';
import { globalFeedbackKey } from '../../symbols/inject';
import BaseLoader from '../../components/BaseLoader.vue';
import BaseLabel from '../../components/form/BaseLabel.vue';
import BaseInput from '../../components/form/BaseInput.vue';
import LoaderButton from '../../components/form/LoaderButton.vue';

const feedback = inject(globalFeedbackKey);
const { applyPharmacyB2BTheme } = useTheme();

const isLoading = ref(true);
const isSaving = ref(false);
const fieldErrors = ref({});

const form = ref({
  name: DEFAULT_B2C_BRANDING.name,
  primary_color: DEFAULT_B2C_BRANDING.primary_color,
  secondary_color: DEFAULT_B2C_BRANDING.secondary_color,
  surface_color: DEFAULT_B2C_BRANDING.surface_color,
  text_color: DEFAULT_B2C_BRANDING.text_color,
  font_preset: DEFAULT_B2C_BRANDING.font_preset,
  points_per_1000_ars: String(DEFAULT_B2C_BRANDING.points_per_1000_ars),
});

const existingLogoUrl = ref('');
const selectedLogoFile = ref(null);
const localLogoPreview = ref('');
const logoInputRef = ref(null);

const COLOR_PRESETS = [
  {
    label: 'Farmacia Dalton',
    primary_color: '#002D3A',
    secondary_color: '#28A2A5',
    surface_color: '#F2F4F6',
    text_color: '#002D3A',
    font_preset: 'dalton',
  },
  {
    label: 'Botánica Natural',
    primary_color: '#1B4332',
    secondary_color: '#2D6A4F',
    surface_color: '#F4F8F5',
    text_color: '#1B4332',
    font_preset: 'apothika',
  },
  {
    label: 'Clínica Azul',
    primary_color: '#1E3A5F',
    secondary_color: '#2563EB',
    surface_color: '#F3F6FA',
    text_color: '#172554',
    font_preset: 'editorial',
  },
];

const PREVIEW_FONTS = {
  dalton: {
    heading: "'Montserrat', system-ui, sans-serif",
    body: "'Poppins', system-ui, sans-serif",
  },
  apothika: {
    heading: "'Albert Sans', system-ui, sans-serif",
    body: "'Source Sans 3', system-ui, sans-serif",
  },
  editorial: {
    heading: "'Plus Jakarta Sans', system-ui, sans-serif",
    body: "'Inter', system-ui, sans-serif",
  },
  accessible: {
    heading: "'Atkinson Hyperlegible', system-ui, sans-serif",
    body: "'Atkinson Hyperlegible', system-ui, sans-serif",
  },
};

const activeLogoUrl = computed(() => localLogoPreview.value || existingLogoUrl.value);
const activePreviewFont = computed(
  () => PREVIEW_FONTS[form.value.font_preset] || PREVIEW_FONTS.dalton
);

function revokeLogoPreview() {
  if (localLogoPreview.value && localLogoPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(localLogoPreview.value);
  }
  localLogoPreview.value = '';
}

function hydrateForm(pharmacy) {
  if (!pharmacy) return;
  form.value = {
    name: pharmacy.name || DEFAULT_B2C_BRANDING.name,
    primary_color: pharmacy.primary_color || DEFAULT_B2C_BRANDING.primary_color,
    secondary_color: pharmacy.secondary_color || DEFAULT_B2C_BRANDING.secondary_color,
    surface_color: pharmacy.surface_color || DEFAULT_B2C_BRANDING.surface_color,
    text_color: pharmacy.text_color || DEFAULT_B2C_BRANDING.text_color,
    font_preset: pharmacy.font_preset || DEFAULT_B2C_BRANDING.font_preset,
    points_per_1000_ars: String(pharmacy.points_per_1000_ars ?? 10),
  };
  existingLogoUrl.value = pharmacy.logo_url || '';
}

function applyPreset(preset) {
  form.value.primary_color = preset.primary_color;
  form.value.secondary_color = preset.secondary_color;
  form.value.surface_color = preset.surface_color;
  form.value.text_color = preset.text_color;
  form.value.font_preset = preset.font_preset;
}

function handleLogoChange(event) {
  const file = event.target.files?.[0] || null;
  revokeLogoPreview();
  selectedLogoFile.value = file;
  if (file) {
    localLogoPreview.value = URL.createObjectURL(file);
  }
}

function clearLogoFile() {
  revokeLogoPreview();
  selectedLogoFile.value = null;
  if (logoInputRef.value) {
    logoInputRef.value.value = '';
  }
}

async function loadBranding() {
  isLoading.value = true;
  try {
    const data = await fetchPharmacyDashboard();
    hydrateForm(data.pharmacy);
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos cargar la configuración de marca blanca.',
    });
  } finally {
    isLoading.value = false;
  }
}

async function handleSave() {
  if (isSaving.value) return;

  isSaving.value = true;
  fieldErrors.value = {};
  feedback.clear();

  const formData = new FormData();
  formData.append('name', form.value.name.trim());
  formData.append('primary_color', form.value.primary_color.trim());
  formData.append('secondary_color', form.value.secondary_color.trim());
  formData.append('surface_color', form.value.surface_color.trim());
  formData.append('text_color', form.value.text_color.trim());
  formData.append('font_preset', form.value.font_preset);
  formData.append('points_per_1000_ars', String(form.value.points_per_1000_ars).trim());

  if (selectedLogoFile.value) {
    formData.append('logo', selectedLogoFile.value);
  }

  try {
    const result = await updatePharmacyBranding(formData);
    hydrateForm(result.pharmacy);
    clearLogoFile();

    // Actualizamos el estado global de branding B2C manteniendo el panel B2B en APOTHIKA
    updateLocalPharmacy(result.pharmacy);
    applyPharmacyB2BTheme(result.pharmacy);

    feedback.show({
      type: 'success',
      message:
        result.message ||
        'Identidad de marca blanca guardada. Ya se refleja en la app de tus pacientes.',
    });
  } catch (error) {
    fieldErrors.value = error.errors || {};
    feedback.show({
      type: 'error',
      message: error.message || 'Revisá los valores de identidad visual antes de guardar.',
    });
  } finally {
    isSaving.value = false;
  }
}

onMounted(() => {
  loadBranding();
});

onUnmounted(() => {
  revokeLogoPreview();
});
</script>

<template>
  <main class="max-w-5xl mx-auto w-full px-4 py-6 space-y-6">
    <!-- Cabecera -->
    <section class="card-duo p-6 border-apothika-emerald/25 space-y-1.5">
      <p class="text-xs font-bold uppercase tracking-wider text-apothika-emerald inline-flex items-center gap-1.5">
        <Palette class="w-4 h-4" aria-hidden="true" />
        <span>Motor White-Label B2C</span>
      </p>
      <h1 class="text-2xl sm:text-3xl font-extrabold text-apothika-emerald">
        Identidad y Marca Blanca
      </h1>
      <p class="text-sm text-brand-muted">
        Personalizá los colores, el logo, la tipografía accesible y la tasa de puntos OTC que ven tus pacientes. Tu panel operativo mantiene siempre la estética limpia de APOTHIKA.
      </p>
    </section>

    <div v-if="isLoading" class="card-duo p-10 flex flex-col items-center gap-3 text-sm text-brand-muted">
      <BaseLoader color="#165A4B" size="md" />
      <p>Cargando identidad de tu farmacia...</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- Columna izquierda: Formulario de personalización -->
      <section aria-labelledby="branding-form-heading" class="lg:col-span-7 card-duo p-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 id="branding-form-heading" class="text-lg font-extrabold text-apothika-charcoal">
            Configuración de tu farmacia
          </h2>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 text-xs font-bold text-apothika-emerald hover:underline cursor-pointer"
            @click="applyPreset(COLOR_PRESETS[0])"
          >
            <RotateCcw class="w-3.5 h-3.5" aria-hidden="true" />
            <span>Restaurar Dalton</span>
          </button>
        </div>

        <form class="space-y-6" novalidate @submit.prevent="handleSave">
          <!-- Datos generales y tasa de puntos -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
              <BaseLabel for="branding-name">Nombre visible para pacientes</BaseLabel>
              <BaseInput
                id="branding-name"
                v-model="form.name"
                type="text"
                maxlength="100"
                :has-error="Boolean(fieldErrors.name)"
                required
              />
              <p v-if="fieldErrors.name" class="mt-1 text-xs font-medium text-game-error">
                {{ fieldErrors.name[0] }}
              </p>
            </div>

            <div>
              <BaseLabel for="branding-rate">Pts cada $1.000 ARS</BaseLabel>
              <BaseInput
                id="branding-rate"
                v-model="form.points_per_1000_ars"
                type="number"
                inputmode="numeric"
                min="1"
                max="500"
                :has-error="Boolean(fieldErrors.points_per_1000_ars)"
                required
              />
              <p v-if="fieldErrors.points_per_1000_ars" class="mt-1 text-xs font-medium text-game-error">
                {{ fieldErrors.points_per_1000_ars[0] }}
              </p>
            </div>
          </div>

          <!-- Logo -->
          <div class="space-y-2">
            <p id="branding-logo-label" class="block text-sm font-semibold text-brand-text">
              Logo de la farmacia (PNG, SVG, JPG o WebP)
            </p>
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
              <div class="w-24 h-16 rounded-xl border-2 border-gray-200 bg-white p-2 flex items-center justify-center shrink-0 overflow-hidden">
                <img
                  v-if="activeLogoUrl"
                  :src="activeLogoUrl"
                  :alt="`Logo de ${form.name}`"
                  class="max-h-full max-w-full object-contain"
                />
                <ImagePlus v-else class="w-6 h-6 text-gray-400" aria-hidden="true" />
              </div>

              <div class="space-y-1.5">
                <input
                  id="branding-logo"
                  ref="logoInputRef"
                  type="file"
                  accept="image/png,image/jpeg,image/webp,image/svg+xml"
                  aria-labelledby="branding-logo-label"
                  class="hidden"
                  @change="handleLogoChange"
                />

                <div class="flex flex-wrap items-center gap-2.5">
                  <button
                    type="button"
                    class="inline-flex items-center gap-2 py-2 px-4 rounded-xl text-xs font-bold bg-apothika-emerald/10 text-apothika-emerald hover:bg-apothika-emerald/20 transition cursor-pointer"
                    @click="logoInputRef?.click()"
                  >
                    <ImagePlus class="w-4 h-4" aria-hidden="true" />
                    <span>{{ activeLogoUrl ? 'Cambiar logo' : 'Seleccionar logo' }}</span>
                  </button>

                  <span class="text-xs text-brand-muted truncate max-w-48">
                    {{ selectedLogoFile ? selectedLogoFile.name : 'Ningún archivo nuevo' }}
                  </span>
                </div>

                <button
                  v-if="selectedLogoFile"
                  type="button"
                  class="text-xs font-bold text-game-error hover:underline cursor-pointer"
                  @click="clearLogoFile"
                >
                  Descartar logo nuevo
                </button>
                <p v-if="fieldErrors.logo" class="text-xs font-medium text-game-error">
                  {{ fieldErrors.logo[0] }}
                </p>
              </div>
            </div>
          </div>

          <!-- Paletas rápidas -->
          <div class="space-y-3 pt-2 border-t border-gray-100">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <h3 class="text-sm font-extrabold text-apothika-charcoal">
                Colores de marca
              </h3>

              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="preset in COLOR_PRESETS"
                  :key="preset.label"
                  type="button"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-gray-200 bg-white hover:border-apothika-emerald text-[11px] font-bold text-apothika-charcoal transition cursor-pointer"
                  @click="applyPreset(preset)"
                >
                  <span
                    class="w-3 h-3 rounded-full border border-black/10"
                    :style="{ backgroundColor: preset.primary_color }"
                    aria-hidden="true"
                  />
                  <span
                    class="w-3 h-3 rounded-full border border-black/10"
                    :style="{ backgroundColor: preset.secondary_color }"
                    aria-hidden="true"
                  />
                  <span>{{ preset.label }}</span>
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <BaseLabel for="color-primary">Color primario (barra y tarjetas)</BaseLabel>
                <div class="flex items-center gap-2">
                  <input
                    id="color-primary-picker"
                    v-model="form.primary_color"
                    type="color"
                    aria-label="Selector visual de color primario"
                    class="w-11 h-11 rounded-xl border-2 border-gray-200 p-1 bg-white cursor-pointer shrink-0"
                  />
                  <BaseInput
                    id="color-primary"
                    v-model="form.primary_color"
                    type="text"
                    maxlength="7"
                    class="font-mono uppercase"
                    :has-error="Boolean(fieldErrors.primary_color)"
                  />
                </div>
              </div>

              <div>
                <BaseLabel for="color-secondary">Color secundario (botones y acentos)</BaseLabel>
                <div class="flex items-center gap-2">
                  <input
                    id="color-secondary-picker"
                    v-model="form.secondary_color"
                    type="color"
                    aria-label="Selector visual de color secundario"
                    class="w-11 h-11 rounded-xl border-2 border-gray-200 p-1 bg-white cursor-pointer shrink-0"
                  />
                  <BaseInput
                    id="color-secondary"
                    v-model="form.secondary_color"
                    type="text"
                    maxlength="7"
                    class="font-mono uppercase"
                    :has-error="Boolean(fieldErrors.secondary_color)"
                  />
                </div>
              </div>

              <div>
                <BaseLabel for="color-surface">Fondo general de la app</BaseLabel>
                <div class="flex items-center gap-2">
                  <input
                    id="color-surface-picker"
                    v-model="form.surface_color"
                    type="color"
                    aria-label="Selector visual de color de fondo"
                    class="w-11 h-11 rounded-xl border-2 border-gray-200 p-1 bg-white cursor-pointer shrink-0"
                  />
                  <BaseInput
                    id="color-surface"
                    v-model="form.surface_color"
                    type="text"
                    maxlength="7"
                    class="font-mono uppercase"
                    :has-error="Boolean(fieldErrors.surface_color)"
                  />
                </div>
              </div>

              <div>
                <BaseLabel for="color-text">Color de texto principal</BaseLabel>
                <div class="flex items-center gap-2">
                  <input
                    id="color-text-picker"
                    v-model="form.text_color"
                    type="color"
                    aria-label="Selector visual de color de texto"
                    class="w-11 h-11 rounded-xl border-2 border-gray-200 p-1 bg-white cursor-pointer shrink-0"
                  />
                  <BaseInput
                    id="color-text"
                    v-model="form.text_color"
                    type="text"
                    maxlength="7"
                    class="font-mono uppercase"
                    :has-error="Boolean(fieldErrors.text_color)"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Presets tipográficos curados -->
          <div class="space-y-3 pt-2 border-t border-gray-100">
            <h3 class="text-sm font-extrabold text-apothika-charcoal inline-flex items-center gap-1.5">
              <Type class="w-4 h-4 text-apothika-emerald" aria-hidden="true" />
              <span>Tipografía curada (accesibilidad garantizada)</span>
            </h3>

            <div role="radiogroup" aria-label="Preset tipográfico de la farmacia" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <button
                v-for="option in FONT_PRESET_OPTIONS"
                :key="option.value"
                type="button"
                role="radio"
                :aria-checked="form.font_preset === option.value"
                class="text-left p-3.5 rounded-2xl border-2 transition cursor-pointer flex items-start justify-between gap-2"
                :class="
                  form.font_preset === option.value
                    ? 'border-apothika-emerald bg-apothika-mint/12'
                    : 'border-gray-200 bg-white hover:border-apothika-emerald/50'
                "
                @click="form.font_preset = option.value"
              >
                <div class="space-y-0.5">
                  <p class="text-xs font-extrabold text-apothika-charcoal">
                    {{ option.label }}
                  </p>
                  <p class="text-[11px] text-brand-muted leading-snug">
                    {{ option.description }}
                  </p>
                </div>
                <Check
                  v-if="form.font_preset === option.value"
                  class="w-4 h-4 text-apothika-emerald shrink-0 mt-0.5"
                  aria-hidden="true"
                />
              </button>
            </div>
          </div>

          <LoaderButton :loading="isSaving" variant="apothika" full-width>
            <Save class="w-4 h-4" aria-hidden="true" />
            <span>Guardar identidad en vivo</span>
          </LoaderButton>
        </form>
      </section>

      <!-- Columna derecha: Vista previa móvil encapsulada -->
      <aside aria-labelledby="branding-preview-heading" class="lg:col-span-5 lg:sticky lg:top-20 space-y-3">
        <div class="flex items-center gap-2">
          <Smartphone class="w-5 h-5 text-apothika-emerald" aria-hidden="true" />
          <h2 id="branding-preview-heading" class="text-base font-extrabold text-apothika-charcoal">
            Vista previa móvil del paciente
          </h2>
        </div>

        <!-- Marco simulado de celular -->
        <div class="rounded-[2rem] border-8 border-apothika-charcoal bg-white shadow-md overflow-hidden">
          <div
            class="min-h-[430px] flex flex-col transition-colors duration-200"
            :style="{
              backgroundColor: form.surface_color,
              color: form.text_color,
              fontFamily: activePreviewFont.body,
            }"
          >
            <!-- Barra superior simulada -->
            <div
              class="px-4 py-3.5 flex items-center justify-between text-white"
              :style="{ backgroundColor: form.primary_color }"
            >
              <div class="flex items-center gap-2 min-w-0">
                <img
                  v-if="activeLogoUrl"
                  :src="activeLogoUrl"
                  :alt="`Vista previa logo de ${form.name}`"
                  class="h-7 w-auto max-w-24 object-contain bg-white/95 rounded-md px-1.5 py-0.5"
                />
                <span
                  v-else
                  class="w-7 h-7 rounded-lg inline-flex items-center justify-center shrink-0"
                  :style="{ backgroundColor: form.secondary_color }"
                >
                  <Sparkles class="w-4 h-4 text-white" aria-hidden="true" />
                </span>
                <span
                  class="font-extrabold text-sm truncate"
                  :style="{ fontFamily: activePreviewFont.heading }"
                >
                  {{ form.name || 'Tu Farmacia' }}
                </span>
              </div>

              <span class="text-[11px] font-bold bg-white/15 px-2.5 py-1 rounded-lg">
                Salir
              </span>
            </div>

            <!-- Contenido simulado del paciente -->
            <div class="p-4 space-y-3.5 flex-1">
              <div class="bg-white rounded-2xl border-2 border-gray-200 border-b-4 p-4 space-y-3">
                <div>
                  <p
                    class="text-[10px] font-bold uppercase tracking-wider"
                    :style="{ color: form.secondary_color }"
                  >
                    {{ form.name || 'Tu Farmacia' }} · Club de Beneficios
                  </p>
                  <p
                    class="text-lg font-extrabold leading-tight mt-0.5"
                    :style="{ color: form.primary_color, fontFamily: activePreviewFont.heading }"
                  >
                    Hola, Camila Gómez
                  </p>
                </div>

                <div
                  class="rounded-xl p-3.5 text-white space-y-1"
                  :style="{ backgroundColor: form.primary_color }"
                >
                  <p class="text-[10px] uppercase tracking-wider text-white/75 font-semibold">
                    Saldo disponible
                  </p>
                  <p
                    class="text-2xl font-extrabold inline-flex items-center gap-1.5"
                    :style="{ fontFamily: activePreviewFont.heading }"
                  >
                    <Coins class="w-5 h-5 text-game-gold" aria-hidden="true" />
                    <span>485 pts</span>
                  </p>
                  <p class="text-[11px] text-white/85">
                    +{{ form.points_per_1000_ars || 10 }} pts cada $1.000 ARS en venta libre
                  </p>
                </div>

                <div
                  class="btn-duo w-full py-2.5 px-4 rounded-xl text-center text-xs font-bold text-white"
                  :style="{ backgroundColor: form.secondary_color }"
                >
                  Canjear beneficios
                </div>
              </div>

              <div class="bg-white rounded-2xl border-2 border-gray-200 p-3.5 space-y-1.5">
                <div class="flex items-center justify-between text-xs font-bold">
                  <span :style="{ color: form.primary_color, fontFamily: activePreviewFont.heading }">
                    Nivel 2 · Bienestar Plus
                  </span>
                  <span :style="{ color: form.secondary_color }">1,25x OTC</span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-gray-200 overflow-hidden">
                  <div
                    class="h-full rounded-full w-3/5"
                    :style="{ backgroundColor: form.secondary_color }"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <p class="text-xs text-brand-muted leading-relaxed">
          Al guardar, cualquier paciente que recargue la app o inicie sesión verá automáticamente esta identidad visual.
        </p>
      </aside>
    </div>
  </main>
</template>