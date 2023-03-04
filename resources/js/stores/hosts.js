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
    },
    localHost() {
      return this.hosts.find(host => !host.is_remote);
    },
    hostQueryParam() {
      return this.selectedHost && this.selectedHost.is_remote ? this.selectedHost.identifier : undefined;
    },
  },

  actions: {
    selectHost(host) {
      if (! this.supportsHosts) {
        host = null;
      }

      if (typeof host === 'string') {
        host = this.hosts.find(h => h.identifier === host);
      }

      if (!host) {
        host = this.hosts.find(h => !h.is_remote);
      }

      this.selectedHostIdentifier = host?.identifier || null;
    }
  }
})
