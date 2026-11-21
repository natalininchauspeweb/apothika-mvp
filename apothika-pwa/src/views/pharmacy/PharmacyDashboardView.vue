<script setup>
import { ShieldCheck, Store } from 'lucide-vue-next';
import { useAuth } from '../../composables/useAuth';

const { loggedUser } = useAuth();
</script>

<template>
  <main class="max-w-5xl mx-auto w-full px-4 py-6 space-y-6">
    <section class="card-duo p-6 space-y-3 border-apothika-emerald/25">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-apothika-mint/20 text-apothika-emerald font-bold text-xs uppercase tracking-wide">
        <Store class="w-4 h-4" aria-hidden="true" />
        <span>Panel B2B APOTHIKA</span>
      </div>

      <h1 class="text-2xl sm:text-3xl font-extrabold text-apothika-emerald">
        {{ loggedUser.pharmacy?.name || 'Panel de Farmacia' }}
      </h1>

      <p class="text-sm text-brand-muted">
        Sesión iniciada como <strong>{{ loggedUser.name }}</strong> ({{ loggedUser.email }}).
      </p>
    </section>

    <section class="card-duo p-6 space-y-2">
      <div class="inline-flex items-center gap-2 text-apothika-emerald font-bold text-base">
        <ShieldCheck class="w-5 h-5 text-apothika-mint" aria-hidden="true" />
        <h2>Identidad B2B y Guard de rol activos</h2>
      </div>
      <p class="text-sm text-brand-muted leading-relaxed">
        Notá cómo la paleta cambió automáticamente a <strong>APOTHIKA</strong> (Verde Esmeralda, Verde Menta y Albert Sans) al entrar como farmacia, manteniendo intacta la marca blanca que ven tus pacientes. En los próximos pasos conectamos las métricas, escáner QR/DNI, validador de cupones, CRUD de beneficios y editor de marca blanca.
      </p>
    </section>
  </main>
</template>