import { createApp } from 'vue';
import './style.css';
import App from './App.vue';
import router from './router';

const app = createApp(App);
app.use(router);
app.mount('#app');

// Registro del Service Worker para habilitar la instalación como PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch((error) => {
      console.error('[PWA] Error al registrar Service Worker:', error);
    });
  });
}

// Registramos el Service Worker solo en producción para no interferir con Vite HMR en desarrollo
if ('serviceWorker' in navigator && import.meta.env.PROD) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch((error) => {
      console.error('[PWA] Error al registrar Service Worker:', error);
    });
  });
}