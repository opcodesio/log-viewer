import { defineStore } from 'pinia';
import axios from 'axios';

export const useAiProvidersStore = defineStore('aiProviders', {
    state: () => ({
        providers: [],
        loading: false,
        loaded: false,
        error: null,
    }),

    getters: {
        hasProviders: (state) => state.providers.length > 0,
        enabledProviders: (state) => state.providers.filter(p => p.enabled),
    },

    actions: {
        async fetchProviders(force = false) {
            // Se o AI Export está desabilitado, não faz nada
            if (!window.LogViewer?.ai_export_enabled) {
                this.providers = [];
                this.loaded = true;
                return [];
            }

            if (this.loaded && !force) {
                return this.providers;
            }

            if (this.loading) {
                return new Promise((resolve) => {
                    const checkInterval = setInterval(() => {
                        if (!this.loading) {
                            clearInterval(checkInterval);
                            resolve(this.providers);
                        }
                    }, 50);
                });
            }

            this.loading = true;
            this.error = null;

            try {
                const basePath = window.LogViewer?.basePath || '';
                const response = await axios.get(`${basePath}/api/ai/providers`);
                this.providers = response.data.providers || [];
                this.loaded = true;
                return this.providers;
            } catch (error) {
                this.error = error.message || 'Failed to load AI providers';
                console.error('Failed to load AI providers:', error);
                this.providers = [];
                return [];
            } finally {
                this.loading = false;
            }
        },

        clearCache() {
            this.providers = [];
            this.loaded = false;
            this.error = null;
        }
    }
});
