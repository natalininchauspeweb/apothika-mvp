import { apiRequest } from './api';

export const DEFAULT_PHARMACY_SLUG =
  import.meta.env.VITE_DEFAULT_PHARMACY_SLUG || 'farmacia-dalton';

/**
 * Identidad por defecto del caso piloto (Farmacia Dalton).
 */
export const DEFAULT_B2C_BRANDING = {
  id: 1,
  name: 'Farmacia Dalton',
  slug: DEFAULT_PHARMACY_SLUG,
  logo_url: null,
  primary_color: '#002D3A',
  secondary_color: '#28A2A5',
  surface_color: '#F2F4F6',
  text_color: '#002D3A',
  font_preset: 'dalton',
  points_per_1000_ars: 10,
};

/**
 * Identidad fija del panel de administración de comercios (APOTHIKA B2B).
 */
export const APOTHIKA_B2B_BRANDING = {
  name: 'APOTHIKA',
  primary_color: '#165A4B',
  secondary_color: '#00D084',
  surface_color: '#FBF9FA',
  text_color: '#212529',
  font_preset: 'apothika',
};

export const FONT_PRESET_OPTIONS = [
  {
    value: 'dalton',
    label: 'Clínica Moderna (Dalton)',
    description: 'Montserrat en títulos + Poppins en textos',
  },
  {
    value: 'apothika',
    label: 'Botánica Tech (APOTHIKA)',
    description: 'Albert Sans en títulos + Source Sans en textos',
  },
  {
    value: 'editorial',
    label: 'Editorial Amigable',
    description: 'Plus Jakarta Sans + Inter',
  },
  {
    value: 'accessible',
    label: 'Máxima Accesibilidad',
    description: 'Atkinson Hyperlegible (alta legibilidad para adultos mayores)',
  },
];

export async function fetchPublicBranding(slug = DEFAULT_PHARMACY_SLUG) {
  const data = await apiRequest(`/pharmacies/${slug}/branding`, { auth: false });
  return data.pharmacy;
}

/**
 * Inyecta los tokens de marca en el nodo raíz (:root) sin usar estilos inline dispersos.
 */
export function applyBrandingTokens(branding) {
  const tokens = { ...DEFAULT_B2C_BRANDING, ...branding };
  const root = document.documentElement;

  root.style.setProperty('--brand-primary', tokens.primary_color);
  root.style.setProperty('--brand-secondary', tokens.secondary_color);
  root.style.setProperty('--brand-surface', tokens.surface_color);
  root.style.setProperty('--brand-text', tokens.text_color);
  root.setAttribute('data-font', tokens.font_preset || 'dalton');

  // Actualiza el color de la barra superior del navegador en celulares (PWA)
  const themeMeta = document.querySelector('meta[name="theme-color"]');
  if (themeMeta) {
    themeMeta.setAttribute('content', tokens.primary_color);
  }
}