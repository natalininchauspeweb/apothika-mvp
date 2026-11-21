import { createApp } from 'vue';
import './style.css';
import App from './App.vue';

createApp(App).mount('#app');

// Registro del Service Worker para habilitar la instalación como PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch((error) => {
      console.error('[PWA] Error al registrar Service Worker:', error);
    });
  });
}