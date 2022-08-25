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
