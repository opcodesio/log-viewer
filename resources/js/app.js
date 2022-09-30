import Alpine from 'alpinejs'
import Clipboard from '@ryangjchandler/alpine-clipboard'
import Persist from '@alpinejs/persist'

const Theme = {
    System: 'System',
    Light: 'Light',
    Dark: 'Dark',
}

Alpine.plugin(Clipboard)
Alpine.plugin(Persist)

window.Alpine = Alpine

Alpine.data('dropdown', () => ({
    open: false,
    direction: 'down',
    toggle() {
        if (this.open) { return this.close() }
        this.$refs.button.focus()
        this.open = true

        const fileListContainer = this.$refs.fileList;

        if (fileListContainer) {
            const p = fileListContainer.getBoundingClientRect()
            this.direction = this.$refs.button.getBoundingClientRect().bottom - p.top + 140 > p.height ? 'up' : 'down';
        }
    },
    close(focusAfter) {
        if (! this.open) { return }
        this.open = false
        focusAfter?.focus()
    },
    transitions: {
        'x-transition:enter': "transition ease-out duration-100",
        'x-transition:enter-start': "opacity-0 scale-90",
        'x-transition:enter-end': "opacity-100 scale-100",
        'x-transition:leave': "transition ease-in duration-100",
        'x-transition:leave-start': "opacity-100 scale-100",
        'x-transition:leave-end': "opacity-0 scale-90",
    }
}));

Alpine.store('fileViewer', {
    scanInProgress: false,
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

    foldersOpen: [],
    foldersInView: [],
    folderTops: {},
    containerTop: 0,
    isOpen(folder) {
        return this.foldersOpen.includes(folder);
    },
    toggle(folder) {
        if (this.isOpen(folder)) {
            this.foldersOpen = this.foldersOpen.filter(f => f !== folder);
        } else {
            this.foldersOpen.push(folder);
        }
        this.onScroll();
    },
    shouldBeSticky(folder) {
        return this.isOpen(folder) && this.foldersInView.includes(folder);
    },
    stickTopPosition(folder) {
        let aboveFold = this.pixelsAboveFold(folder);

        if (aboveFold < 0) {
            return Math.max(0, -8 + aboveFold) + 'px';
        }

        return '-8px';
    },
    pixelsAboveFold(folder) {
        let folderContainer = document.getElementById('folder-'+folder);
        if (!folderContainer) return false;
        let row = folderContainer.getClientRects()[0];
        return (row.top + row.height) - this.containerTop;
    },
    isInViewport(index) {
        return this.pixelsAboveFold(index) > -36;
    },
    onScroll() {
        let vm = this;
        this.foldersOpen.forEach(function (folder) {
            if (vm.isInViewport(folder)) {
                if (!vm.foldersInView.includes(folder)) { vm.foldersInView.push(folder); }
                vm.folderTops[folder] = vm.stickTopPosition(folder);
            } else {
                vm.foldersInView = vm.foldersInView.filter(f => f !== folder);
                delete vm.folderTops[folder];
            }
        })
    },
    reset() {
        this.foldersOpen = [];
        this.foldersInView = [];
        this.folderTops = {};
        const container = document.getElementById('file-list-container');
        this.containerTop = container.getBoundingClientRect().top;
        container.scrollTo(0, 0);
    }
});

Alpine.store('logViewer', {
    theme: Alpine.$persist(Theme.System).as('logViewer_theme'),
    stacksOpen: [],
    stacksInView: [],
    stackTops: {},
    containerTop: 0,
    toggleTheme() {
        switch (this.theme) {
            case Theme.System: return this.theme = Theme.Light;
            case Theme.Light: return this.theme = Theme.Dark;
            default: return this.theme = Theme.System;
        }
    },
    isOpen(index) {
        return this.stacksOpen.includes(index);
    },
    toggle(index) {
        if (this.isOpen(index)) {
            this.stacksOpen = this.stacksOpen.filter(idx => idx !== index)
        } else {
            this.stacksOpen.push(index)
        }
        this.onScroll();
    },
    shouldBeSticky(index) {
        return this.isOpen(index) && this.stacksInView.includes(index);
    },
    stickTopPosition(index) {
        let aboveFold = this.pixelsAboveFold(index);

        if (aboveFold < 0) {
            return Math.max(0, 36 + aboveFold) + 'px';
        }

        return '36px';
    },
    pixelsAboveFold(index) {
        let tbody = document.getElementById('tbody-'+index);
        if (!tbody) return false;
        let row = tbody.getClientRects()[0];
        return (row.top + row.height - 73) - this.containerTop;
    },
    isInViewport(index) {
        return this.pixelsAboveFold(index) > -36;
    },
    onScroll() {
        let vm = this;
        this.stacksOpen.forEach(function (index) {
            if (vm.isInViewport(index)) {
                if (!vm.stacksInView.includes(index)) { vm.stacksInView.push(index); }
                vm.stackTops[index] = vm.stickTopPosition(index);
            } else {
                vm.stacksInView = vm.stacksInView.filter(idx => idx !== index);
                delete vm.stackTops[index];
            }
        })
    },
    reset() {
        this.stacksOpen = [];
        this.stacksInView = [];
        this.stackTops = {};
        const container = document.getElementById('log-item-container');
        this.containerTop = container.getBoundingClientRect().top;
        container.scrollTo(0, 0);
    }
})

Alpine.start()

const syncTheme = () => {
    const theme = Alpine.store('logViewer').theme;

    if (theme === Theme.Dark || (theme === Theme.System && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark')
    } else {
        document.documentElement.classList.remove('dark')
    }
};

Alpine.effect(syncTheme)

// This makes sure we react to device's dark mode changes
setInterval(syncTheme, 1000);
