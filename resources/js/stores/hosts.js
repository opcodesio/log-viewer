import { defineStore } from 'pinia';

export const useHostStore = defineStore({
  id: 'hosts',

  state: () => ({
    selectedHostIdentifier: null,
  }),

  getters: {
    supportsHosts() {
      return LogViewer.supports_hosts;
    },
    hosts() {
      return LogViewer.hosts || [];
    },
    hasRemoteHosts() {
      return this.hosts.some(host => host.is_remote);
    },
    selectedHost() {
      return this.hosts.find(host => host.identifier === this.selectedHostIdentifier);
    }
  },

  actions: {
    selectHost(host) {
      if (! this.supportsHosts) {
        host = null;
      }

      if (typeof host === 'string') {
        host = this.hosts.find(h => h.identifier === host);
      }

      this.selectedHostIdentifier = host?.identifier || null;
    }
  }
})
