<script setup>
import { Coins, QrCode, ShieldCheck } from 'lucide-vue-next';
import { useAuth } from '../../composables/useAuth';
import { useTheme } from '../../composables/useTheme';

const { loggedUser } = useAuth();
const { pharmacyBranding } = useTheme();
</script>

<template>
  <main class="max-w-3xl mx-auto w-full px-4 py-6 space-y-6">
    <section class="card-duo p-6 space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-1">
          <p class="text-xs font-bold uppercase tracking-wider text-brand-secondary">
            {{ pharmacyBranding.name }} · Club de Beneficios
          </p>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-primary">
            Hola, {{ loggedUser.name }}
          </h1>
        </div>

        <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-game-gold/30 border-2 border-game-gold text-brand-primary font-extrabold text-lg">
          <Coins class="w-5 h-5" aria-hidden="true" />
          <span>{{ loggedUser.points_balance }} pts</span>
        </div>
      </div>

      <p class="text-sm text-brand-muted">
        Presentá tu pase digital en el mostrador al comprar productos de venta libre (OTC), dermocosmética o perfumería para seguir acumulando puntos.
      </p>
    </section>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <section class="card-duo p-5 space-y-2">
        <div class="inline-flex items-center gap-2 text-brand-secondary font-bold text-sm">
          <QrCode class="w-5 h-5" aria-hidden="true" />
          <h2>Tu pase identificador</h2>
        </div>
        <p class="font-mono text-base font-bold text-brand-primary bg-brand-surface px-3 py-2 rounded-xl">
          {{ loggedUser.qr_code }}
        </p>
        <p class="text-xs text-brand-muted">
          DNI registrado: {{ loggedUser.dni }}
        </p>
      </section>

      <section class="card-duo p-5 space-y-2">
        <div class="inline-flex items-center gap-2 text-game-success font-bold text-sm">
          <ShieldCheck class="w-5 h-5" aria-hidden="true" />
          <h2>Acceso B2C verificado</h2>
        </div>
        <p class="text-sm text-brand-muted leading-relaxed">
          Sesión protegida por token y tema dinámico de <strong>{{ pharmacyBranding.name }}</strong> activo. En el próximo paso sumamos el QR visual, historial, trivias y catálogo de canje.
        </p>
      </section>
    </div>
  </main>
</template>