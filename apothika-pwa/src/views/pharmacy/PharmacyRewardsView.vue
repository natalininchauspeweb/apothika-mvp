<script setup>
import { computed, inject, onMounted, onUnmounted, ref } from 'vue';
import {
  Check,
  Coins,
  Eye,
  EyeOff,
  Gift,
  ImagePlus,
  Pencil,
  Plus,
  Sparkles,
  Trash2,
  X,
} from 'lucide-vue-next';
import {
  createPharmacyReward,
  deletePharmacyReward,
  fetchPharmacyRewards,
  togglePharmacyReward,
  updatePharmacyReward,
} from '../../services/pharmacy';
import { globalFeedbackKey } from '../../symbols/inject';
import BaseLoader from '../../components/BaseLoader.vue';
import BaseLabel from '../../components/form/BaseLabel.vue';
import BaseInput from '../../components/form/BaseInput.vue';
import LoaderButton from '../../components/form/LoaderButton.vue';

const feedback = inject(globalFeedbackKey);

const rewards = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);
const togglingId = ref(null);
const deletingId = ref(null);
const confirmingDeleteId = ref(null);

// Estado del formulario (creación / edición)
const showForm = ref(false);
const editingRewardId = ref(null);
const form = ref({
  title: '',
  description: '',
  points_cost: '',
  is_active: true,
});
const selectedFile = ref(null);
const previewUrl = ref('');
const existingImageUrl = ref('');
const fieldErrors = ref({});
const fileInputRef = ref(null);

const isEditing = computed(() => editingRewardId.value !== null);
const activeCount = computed(() => rewards.value.filter((r) => r.is_active).length);

function revokeLocalPreview() {
  if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(previewUrl.value);
  }
  previewUrl.value = '';
}

function resetForm() {
  revokeLocalPreview();
  editingRewardId.value = null;
  form.value = {
    title: '',
    description: '',
    points_cost: '',
    is_active: true,
  };
  selectedFile.value = null;
  existingImageUrl.value = '';
  fieldErrors.value = {};
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
}

function openCreateForm() {
  resetForm();
  showForm.value = true;
}

function openEditForm(reward) {
  resetForm();
  editingRewardId.value = reward.id;
  form.value = {
    title: reward.title || '',
    description: reward.description || '',
    points_cost: String(reward.points_cost ?? ''),
    is_active: Boolean(reward.is_active),
  };
  existingImageUrl.value = reward.image_url || '';
  showForm.value = true;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function closeForm() {
  resetForm();
  showForm.value = false;
}

function handleFileChange(event) {
  const file = event.target.files?.[0] || null;
  revokeLocalPreview();
  selectedFile.value = file;

  if (file) {
    previewUrl.value = URL.createObjectURL(file);
  }
}

function clearSelectedFile() {
  revokeLocalPreview();
  selectedFile.value = null;
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
}

async function loadRewards() {
  isLoading.value = true;
  try {
    const data = await fetchPharmacyRewards();
    rewards.value = data.rewards || [];
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos cargar el catálogo de beneficios.',
    });
  } finally {
    isLoading.value = false;
  }
}

async function handleSubmit() {
  if (isSaving.value) return;

  isSaving.value = true;
  fieldErrors.value = {};
  feedback.clear();

  const formData = new FormData();
  formData.append('title', form.value.title.trim());
  formData.append('description', form.value.description.trim());
  formData.append('points_cost', String(form.value.points_cost).trim());
  formData.append('is_active', form.value.is_active ? '1' : '0');

  if (selectedFile.value) {
    formData.append('image', selectedFile.value);
  }

  try {
    if (isEditing.value) {
      const result = await updatePharmacyReward(editingRewardId.value, formData);
      rewards.value = rewards.value.map((item) =>
        item.id === editingRewardId.value ? result.reward : item
      );
      feedback.show({
        type: 'success',
        message: result.message || 'Beneficio actualizado correctamente.',
      });
    } else {
      const result = await createPharmacyReward(formData);
      rewards.value = [result.reward, ...rewards.value];
      feedback.show({
        type: 'success',
        message: result.message || 'Nuevo beneficio publicado en el catálogo.',
      });
    }

    closeForm();
  } catch (error) {
    fieldErrors.value = error.errors || {};
    feedback.show({
      type: 'error',
      message: error.message || 'Revisá los campos del beneficio antes de guardar.',
    });
  } finally {
    isSaving.value = false;
  }
}

async function handleToggle(reward) {
  if (togglingId.value) return;

  togglingId.value = reward.id;
  feedback.clear();

  try {
    const result = await togglePharmacyReward(reward.id);
    rewards.value = rewards.value.map((item) =>
      item.id === reward.id ? result.reward : item
    );
    feedback.show({
      type: 'info',
      message: result.message || 'Estado del beneficio actualizado.',
    });
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos cambiar el estado del beneficio.',
    });
  } finally {
    togglingId.value = null;
  }
}

async function handleConfirmDelete(reward) {
  if (deletingId.value) return;

  deletingId.value = reward.id;
  feedback.clear();

  try {
    const result = await deletePharmacyReward(reward.id);
    rewards.value = rewards.value.filter((item) => item.id !== reward.id);
    confirmingDeleteId.value = null;

    if (editingRewardId.value === reward.id) {
      closeForm();
    }

    feedback.show({
      type: 'success',
      message: result.message || 'Beneficio eliminado del catálogo.',
    });
  } catch (error) {
    feedback.show({
      type: 'error',
      message: error.message || 'No pudimos eliminar este beneficio.',
    });
  } finally {
    deletingId.value = null;
  }
}

onMounted(() => {
  loadRewards();
});

onUnmounted(() => {
  revokeLocalPreview();
});
</script>

<template>
  <main class="max-w-5xl mx-auto w-full px-4 py-6 space-y-6">
    <!-- Cabecera -->
    <section class="card-duo p-6 border-apothika-emerald/25 flex flex-wrap items-center justify-between gap-4">
      <div class="space-y-1">
        <p class="text-xs font-bold uppercase tracking-wider text-apothika-emerald inline-flex items-center gap-1.5">
          <Gift class="w-4 h-4" aria-hidden="true" />
          <span>Gestión de catálogo B2B</span>
        </p>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-apothika-emerald">
          Beneficios y premios
        </h1>
        <p class="text-sm text-brand-muted">
          Administrá los descuentos y servicios que tus pacientes pueden canjear con sus puntos.
        </p>
      </div>

      <button
        v-if="!showForm"
        type="button"
        class="btn-duo bg-apothika-emerald text-white text-sm font-bold px-5 py-2.5 rounded-xl inline-flex items-center gap-2 cursor-pointer"
        @click="openCreateForm"
      >
        <Plus class="w-4 h-4" aria-hidden="true" />
        <span>Nuevo beneficio</span>
      </button>
    </section>

    <!-- Formulario de creación / edición -->
    <section v-if="showForm" aria-labelledby="reward-form-heading" class="card-duo p-6 border-apothika-emerald/40 space-y-5">
      <div class="flex items-center justify-between gap-3">
        <h2 id="reward-form-heading" class="text-lg font-extrabold text-apothika-charcoal">
          {{ isEditing ? 'Editar beneficio' : 'Publicar nuevo beneficio' }}
        </h2>

        <button
          type="button"
          aria-label="Cerrar formulario"
          class="p-2 rounded-xl border-2 border-gray-200 text-gray-600 hover:bg-gray-100 transition cursor-pointer"
          @click="closeForm"
        >
          <X class="w-4 h-4" aria-hidden="true" />
        </button>
      </div>

      <form class="space-y-4" novalidate @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="md:col-span-2">
            <BaseLabel for="reward-title">Título del beneficio</BaseLabel>
            <BaseInput
              id="reward-title"
              v-model="form.title"
              type="text"
              maxlength="120"
              placeholder="Ej: 20% OFF en Protectores Solares"
              :has-error="Boolean(fieldErrors.title)"
              required
            />
            <p v-if="fieldErrors.title" class="mt-1 text-xs font-medium text-game-error">
              {{ fieldErrors.title[0] }}
            </p>
          </div>

          <div>
            <BaseLabel for="reward-points-cost">Costo en puntos</BaseLabel>
            <BaseInput
              id="reward-points-cost"
              v-model="form.points_cost"
              type="number"
              inputmode="numeric"
              min="10"
              step="1"
              placeholder="Ej: 150"
              :has-error="Boolean(fieldErrors.points_cost)"
              required
            />
            <p v-if="fieldErrors.points_cost" class="mt-1 text-xs font-medium text-game-error">
              {{ fieldErrors.points_cost[0] }}
            </p>
          </div>
        </div>

        <div>
          <BaseLabel for="reward-description">Descripción y condiciones en mostrador</BaseLabel>
          <textarea
            id="reward-description"
            v-model="form.description"
            rows="3"
            maxlength="400"
            placeholder="Indicá sobre qué productos o servicios de venta libre aplica este beneficio..."
            :aria-invalid="fieldErrors.description ? 'true' : undefined"
            class="w-full rounded-xl border-2 bg-white px-4 py-2.5 text-sm text-brand-text placeholder:text-gray-400 transition focus:outline-none"
            :class="fieldErrors.description ? 'border-game-error focus:border-game-error' : 'border-gray-200 focus:border-apothika-emerald'"
            required
          />
          <p v-if="fieldErrors.description" class="mt-1 text-xs font-medium text-game-error">
            {{ fieldErrors.description[0] }}
          </p>
        </div>

        <!-- Carga de imagen con previsualización -->
        <div class="space-y-2">
          <p id="reward-image-label" class="block text-sm font-semibold text-brand-text">
            Imagen ilustrativa (opcional · JPG, PNG o WebP hasta 2 MB)
          </p>

          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="w-32 h-24 rounded-xl border-2 border-gray-200 bg-apothika-offwhite overflow-hidden flex items-center justify-center shrink-0">
              <img
                v-if="previewUrl || existingImageUrl"
                :src="previewUrl || existingImageUrl"
                alt="Vista previa del beneficio"
                class="w-full h-full object-cover"
              />
              <ImagePlus v-else class="w-7 h-7 text-gray-400" aria-hidden="true" />
            </div>

            <div class="space-y-2">
              <input
                id="reward-image"
                ref="fileInputRef"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                aria-labelledby="reward-image-label"
                class="hidden"
                @change="handleFileChange"
              />

              <div class="flex flex-wrap items-center gap-2.5">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 py-2 px-4 rounded-xl text-xs font-bold bg-apothika-emerald/10 text-apothika-emerald hover:bg-apothika-emerald/20 transition cursor-pointer"
                  @click="fileInputRef?.click()"
                >
                  <ImagePlus class="w-4 h-4" aria-hidden="true" />
                  <span>{{ previewUrl || existingImageUrl ? 'Cambiar imagen' : 'Seleccionar imagen' }}</span>
                </button>

                <span class="text-xs text-brand-muted truncate max-w-48">
                  {{ selectedFile ? selectedFile.name : 'Ningún archivo seleccionado' }}
                </span>
              </div>

              <button
                v-if="selectedFile"
                type="button"
                class="text-xs font-bold text-game-error hover:underline cursor-pointer"
                @click="clearSelectedFile"
              >
                Quitar archivo seleccionado
              </button>
              <p v-if="fieldErrors.image" class="text-xs font-medium text-game-error">
                {{ fieldErrors.image[0] }}
              </p>
            </div>
          </div>
        </div>

        <!-- Estado inicial activo/pausado -->
        <label for="reward-is-active" class="inline-flex items-center gap-2.5 cursor-pointer pt-1">
          <input
            id="reward-is-active"
            v-model="form.is_active"
            type="checkbox"
            class="w-4 h-4 accent-apothika-emerald rounded cursor-pointer"
          />
          <span class="text-sm font-semibold text-apothika-charcoal">
            Visible y disponible para canje en la app del paciente
          </span>
        </label>

        <div class="flex flex-wrap justify-end gap-2.5 pt-2 border-t border-gray-100">
          <button
            type="button"
            class="px-5 py-2.5 rounded-xl border-2 border-gray-300 bg-white hover:bg-gray-50 text-sm font-bold text-apothika-charcoal transition cursor-pointer"
            :disabled="isSaving"
            @click="closeForm"
          >
            Cancelar
          </button>

          <LoaderButton :loading="isSaving" variant="apothika">
            <Check class="w-4 h-4" aria-hidden="true" />
            <span>{{ isEditing ? 'Guardar cambios' : 'Publicar beneficio' }}</span>
          </LoaderButton>
        </div>
      </form>
    </section>

    <!-- Listado de beneficios -->
    <div v-if="isLoading" class="card-duo p-10 flex flex-col items-center gap-3 text-sm text-brand-muted">
      <BaseLoader color="#165A4B" size="md" />
      <p>Cargando beneficios de tu farmacia...</p>
    </div>

    <section v-else-if="rewards.length === 0" class="card-duo p-10 text-center space-y-3">
      <h2 class="text-lg font-extrabold text-apothika-charcoal">
        Todavía no publicaste beneficios
      </h2>
      <p class="text-sm text-brand-muted max-w-md mx-auto">
        Creá tu primer descuento en dermocosmética, perfumería o servicio de gabinete para que tus pacientes empiecen a canjear puntos.
      </p>
    </section>

    <section v-else aria-labelledby="pharmacy-rewards-list-heading" class="space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 id="pharmacy-rewards-list-heading" class="text-base font-extrabold text-apothika-charcoal">
          Beneficios publicados ({{ rewards.length }})
        </h2>
        <span class="text-xs font-semibold text-brand-muted">
          {{ activeCount }} activos · {{ rewards.length - activeCount }} pausados
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <article
          v-for="reward in rewards"
          :key="reward.id"
          class="card-duo overflow-hidden flex flex-col justify-between"
          :class="!reward.is_active ? 'opacity-80 bg-gray-50' : ''"
        >
          <div>
            <div class="h-40 bg-apothika-offwhite flex items-center justify-center overflow-hidden relative">
              <img
                v-if="reward.image_url"
                :src="reward.image_url"
                :alt="reward.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="flex flex-col items-center gap-1.5 text-apothika-emerald/55">
                <Sparkles class="w-8 h-8" aria-hidden="true" />
                <span class="text-xs font-bold uppercase tracking-wider">Sin imagen</span>
              </div>

              <span
                class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wide"
                :class="
                  reward.is_active
                    ? 'bg-game-success text-white'
                    : 'bg-gray-700 text-white'
                "
              >
                {{ reward.is_active ? 'Activo' : 'Pausado' }}
              </span>

              <span class="absolute top-3 right-3 inline-flex items-center gap-1 px-3 py-1 rounded-full bg-apothika-emerald text-white font-extrabold text-xs">
                <Coins class="w-3.5 h-3.5 text-apothika-mint" aria-hidden="true" />
                <span>{{ reward.points_cost }} pts</span>
              </span>
            </div>

            <div class="p-5 space-y-2">
              <h3 class="text-base font-extrabold text-apothika-charcoal leading-snug">
                {{ reward.title }}
              </h3>
              <p class="text-xs text-brand-muted leading-relaxed">
                {{ reward.description }}
              </p>
            </div>
          </div>

          <!-- Acciones del CRUD -->
          <div class="px-5 pb-5 pt-2 border-t border-gray-100">
            <div
              v-if="confirmingDeleteId === reward.id"
              class="rounded-xl bg-game-error/10 border border-game-error/30 p-3 space-y-2"
            >
              <p class="text-xs font-bold text-red-950 text-center">
                ¿Eliminar este beneficio del catálogo?
              </p>
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-bold text-apothika-charcoal cursor-pointer"
                  :disabled="deletingId === reward.id"
                  @click="confirmingDeleteId = null"
                >
                  Cancelar
                </button>
                <LoaderButton
                  type="button"
                  variant="danger"
                  :loading="deletingId === reward.id"
                  @click="handleConfirmDelete(reward)"
                >
                  Sí, eliminar
                </LoaderButton>
              </div>
            </div>

            <div v-else class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border-2 border-gray-200 bg-white hover:border-apothika-emerald text-xs font-bold text-apothika-charcoal transition cursor-pointer"
                  @click="openEditForm(reward)"
                >
                  <Pencil class="w-3.5 h-3.5 text-apothika-emerald" aria-hidden="true" />
                  <span>Editar</span>
                </button>

                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border-2 border-gray-200 bg-white hover:border-apothika-emerald text-xs font-bold text-apothika-charcoal transition cursor-pointer disabled:opacity-50"
                  :disabled="togglingId === reward.id"
                  @click="handleToggle(reward)"
                >
                  <EyeOff v-if="reward.is_active" class="w-3.5 h-3.5 text-amber-600" aria-hidden="true" />
                  <Eye v-else class="w-3.5 h-3.5 text-game-success" aria-hidden="true" />
                  <span>{{ reward.is_active ? 'Pausar' : 'Activar' }}</span>
                </button>
              </div>

              <button
                type="button"
                aria-label="Eliminar beneficio"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl border-2 border-transparent hover:border-game-error/30 hover:bg-game-error/10 text-xs font-bold text-game-error transition cursor-pointer"
                @click="confirmingDeleteId = reward.id"
              >
                <Trash2 class="w-3.5 h-3.5" aria-hidden="true" />
                <span>Eliminar</span>
              </button>
            </div>
          </div>
        </article>
      </div>
    </section>
  </main>
</template>