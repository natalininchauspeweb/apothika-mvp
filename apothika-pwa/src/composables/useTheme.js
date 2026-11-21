import { ref } from 'vue';
import {
  APOTHIKA_B2B_BRANDING,
  DEFAULT_B2C_BRANDING,
  DEFAULT_PHARMACY_SLUG,
  applyBrandingTokens,
  fetchPublicBranding,
} from '../services/branding';

// Estado reactivo compartido del branding actual de la farmacia
const pharmacyBranding = ref({ ...DEFAULT_B2C_BRANDING });
const currentMode = ref('b2c'); // 'b2c' (marca blanca) | 'b2b' (APOTHIKA)

export function useTheme() {
  async function loadPublicBranding(slug = DEFAULT_PHARMACY_SLUG) {
    try {
      const data = await fetchPublicBranding(slug);
      pharmacyBranding.value = { ...DEFAULT_B2C_BRANDING, ...data };
    } catch {
      pharmacyBranding.value = { ...DEFAULT_B2C_BRANDING };
    }

    if (currentMode.value === 'b2c') {
      applyBrandingTokens(pharmacyBranding.value);
    }

    return pharmacyBranding.value;
  }

  function applyPatientTheme(customPharmacy = null) {
    currentMode.value = 'b2c';
    if (customPharmacy) {
      pharmacyBranding.value = { ...DEFAULT_B2C_BRANDING, ...customPharmacy };
    }
    applyBrandingTokens(pharmacyBranding.value);
  }

  function applyPharmacyB2BTheme(customPharmacy = null) {
    currentMode.value = 'b2b';
    if (customPharmacy) {
      pharmacyBranding.value = { ...DEFAULT_B2C_BRANDING, ...customPharmacy };
    }
    applyBrandingTokens(APOTHIKA_B2B_BRANDING);
  }

  return {
    pharmacyBranding,
    currentMode,
    loadPublicBranding,
    applyPatientTheme,
    applyPharmacyB2BTheme,
  };
}