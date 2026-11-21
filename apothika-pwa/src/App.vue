<script setup>
import { Sparkles, ShieldCheck } from 'lucide-vue-next';
</script>

<template>
  <div class="min-h-dvh bg-brand-surface text-brand-text flex items-center justify-center p-6">
    <main class="card-duo max-w-lg w-full p-8 text-center space-y-5">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-game-gold/30 text-brand-primary font-bold text-sm">
        <Sparkles class="w-4 h-4" aria-hidden="true" />
        <span>Motor White-Label + PWA activo</span>
      </div>

  <h1 class="text-3xl font-extrabold text-brand-primary">
  APOTHIKA
</h1>
<p class="text-base font-bold text-brand-secondary">
  Farmacia Dalton
</p>

      <h2 class="text-lg font-semibold text-brand-muted">
        Base visual lista para conectar con Laravel
      </h2>

      <p class="text-sm leading-relaxed text-brand-muted">
        Tipografías curadas, variables dinámicas de marca blanca, paleta de gamificación y Service Worker verificados.
      </p>

      <div class="flex flex-wrap justify-center gap-3 pt-2">
        <button
          type="button"
          class="btn-duo bg-brand-secondary text-white font-bold px-6 py-3 rounded-xl cursor-pointer inline-flex items-center gap-2"
        >
          <ShieldCheck class="w-5 h-5" aria-hidden="true" />
          <span>Botón táctil B2C</span>
        </button>

        <button
          type="button"
          class="btn-duo bg-apothika-emerald text-white font-bold px-6 py-3 rounded-xl cursor-pointer"
        >
          Panel APOTHIKA B2B
        </button>
      </div>
    </main>
  </div>
</template>