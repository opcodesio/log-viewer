import { defineStore } from 'pinia';
import axios from 'axios';
import { useLocalStorage } from '@vueuse/core';

export const useFileStore = defineStore({
  id: 'fileStore',

  state: () => ({
    // data
    folders: [],
    direction: useLocalStorage('fileViewerDirection', 'desc'),
    selectedFileIdentifier: null,

    // control variables
    loading: false,
    scanInProgress: false,
    checkBoxesVisibility: false,
    filesChecked: [],
    openFolderIdentifiers: [],
    foldersInView: [],
    folderTops: {},
    containerTop: 0,
  }),

  getters: {
    files: (state) => state.folders.flatMap((folder) => folder.files),

    selectedFile: (state) => state.files.find((file) => file.identifier === state.selectedFileIdentifier),

    foldersOpen(state) {
      return state.openFolderIdentifiers.map((identifier) => state.folders.find((folder) => folder.identifier === identifier));
    },

    isOpen() {
      return (folder) => this.foldersOpen.includes(folder);
    },

    isChecked: (state) => (file) => state.filesChecked.includes(file),

    shouldBeSticky(state) {
      return (folder) => this.isOpen(folder) && state.foldersInView.includes(folder);
    },

    isInViewport() {
      return (index) => this.pixelsAboveFold(index) > -36
    },

    stickTopPosition() {
      return (folder) => {
        let aboveFold = this.pixelsAboveFold(folder);

        if (aboveFold < 0) {
          return Math.max(0, -8 + aboveFold) + 'px';
        }

        return '-8px';
      }
    },

    pixelsAboveFold: (state) => (folder) => {
      let folderContainer = document.getElementById('folder-' + folder);
      if (!folderContainer) return false;
      let row = folderContainer.getClientRects()[0];
      return (row.top + row.height) - state.containerTop;
    },

    hasFilesChecked: (state) => state.filesChecked.length > 0,
  },

  actions: {
    setDirection(direction) {
      this.direction = direction;
    },

    selectFile(logFileIdentifier) {
      if (this.selectedFileIdentifier === logFileIdentifier) return;

      this.selectedFileIdentifier = logFileIdentifier;
      this.openFolderForActiveFile();
    },

    openFolderForActiveFile() {
      if (this.selectedFile) {
        const folder = this.folders.find(folder => folder.files.some(file => file.identifier === this.selectedFile.identifier));

        if (!this.isOpen(folder)) {
          this.toggle(folder);
        }
      }
    },

    initScanCheck(routeScanCheck, routeScan) {
      if (this.scanInProgress) return;
      fetch(routeScanCheck)
        .then((response) => response.json())
        .then((data) => {
          if (data.requires_scan) {
            this.scanInProgress = true;
            fetch(routeScan)
              .then((response) => response.json())
              .then((data) => {
                this.scanInProgress = false;
                window.dispatchEvent(new CustomEvent('reload-files'));
              })
              .catch((error) => {
                console.error(error);
                this.scanInProgress = false;
              })
          }
        })
    },

    loadFolders() {
      this.loading = true;

      // load the folders from the server
      return axios.get(`${LogViewer.path}/api/folders`, { params: { direction: this.direction }})
        .then(({ data }) => {
          this.folders = data;
          this.loading = false;
          this.openFolderForActiveFile();

          if (this.openFolderIdentifiers.length === 0 && this.folders.length > 0) {
            this.openFolderIdentifiers.push(this.folders[0].identifier);
          }
        })
        .catch((error) => {
          this.loading = false;
          console.error(error);
        })
    },

    toggle(folder) {
      if (this.isOpen(folder)) {
        this.openFolderIdentifiers = this.openFolderIdentifiers.filter(f => f !== folder.identifier);
      } else {
        this.openFolderIdentifiers.push(folder.identifier);
      }
      this.onScroll();
    },

    onScroll() {
      let vm = this;
      this.foldersOpen.forEach(function (folder) {
        if (vm.isInViewport(folder)) {
          if (!vm.foldersInView.includes(folder)) {
            vm.foldersInView.push(folder);
          }
          vm.folderTops[folder] = vm.stickTopPosition(folder);
        } else {
          vm.foldersInView = vm.foldersInView.filter(f => f !== folder);
          delete vm.folderTops[folder];
        }
      })
    },

    reset() {
      this.openFolderIdentifiers = [];
      this.foldersInView = [];
      this.folderTops = {};
      const container = document.getElementById('file-list-container');
      this.containerTop = container.getBoundingClientRect().top;
      container.scrollTo(0, 0);
    },

    checkBoxToggle(file) {
      if (this.isChecked(file)) {
        this.filesChecked = this.filesChecked.filter(f => f !== file);
      } else {
        this.filesChecked.push(file);
      }
    },

    toggleCheckboxVisibility() {
      this.checkBoxesVisibility = !this.checkBoxesVisibility;
    },

    resetChecks() {
      this.filesChecked = [];
      this.checkBoxesVisibility = false;
    },
  },
})
