<template>
    <div
        v-if="logViewerStore.showSeverityStats && levelCounts.length > 0"
        class="mb-6"
    >
        <div class="ml-1 block text-sm text-gray-500 dark:text-gray-400 mb-3">Aggregate Statistics</div>
        <div class="grid grid-cols-1 gap-2">
            <div
                v-for="level in levelCounts"
                :key="level.level"
                :class="getLevelCardClass(level.level_class, level.level)"
                class="severity-stat-card"
            >
                <div class="severity-icon">
                    <component
                        :is="getLevelIcon(level.level)"
                        :class="['w-6 h-6', getLevelIconClass(level.level)]"
                    />
                </div>
                <div class="severity-content">
                    <div class="severity-name">{{ level.level_name }}</div>
                    <div class="severity-stats">
                        <span class="severity-count">{{ Number(level.count).toLocaleString() }} entries</span>
                        <span class="severity-percentage">{{ level.percentage }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div
        v-else-if="logViewerStore.showSeverityStats && loading"
        class="mb-6 text-center text-sm text-gray-500 dark:text-gray-400 py-4"
    >
        <div class="flex items-center justify-center">
            <SpinnerIcon class="w-5 h-5 mr-2" />
            Loading statistics...
        </div>
    </div>
</template>

<script setup>
import {
    Bars3BottomLeftIcon,
    BellAlertIcon,
    BugAntIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    LightBulbIcon,
    MegaphoneIcon,
    ShieldExclamationIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline'
import axios from 'axios'
import { onMounted, ref, watch } from 'vue'
import { useFileStore } from '../stores/files.js'
import { useHostStore } from '../stores/hosts.js'
import { useLogViewerStore } from '../stores/logViewer.js'
import SpinnerIcon from './SpinnerIcon.vue'

const fileStore = useFileStore()
const hostStore = useHostStore()
const logViewerStore = useLogViewerStore()
const levelCounts = ref([])
const loading = ref(false)

const levelIcons = {
    all: Bars3BottomLeftIcon,
    emergency: MegaphoneIcon,
    alert: BellAlertIcon,
    critical: ShieldExclamationIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    notice: LightBulbIcon,
    info: InformationCircleIcon,
    debug: BugAntIcon,
}

const getLevelIcon = (level) => {
    const normalizedLevel = level?.toLowerCase()
    return levelIcons[normalizedLevel] || InformationCircleIcon
}

const getLevelIconClass = (levelName) => {
    const normalizedLevel = levelName?.toLowerCase()

    if (normalizedLevel === 'debug') {
        return 'text-orange-500'
    }
    if (normalizedLevel === 'info') {
        return 'text-blue-500'
    }
    if (normalizedLevel === 'notice') {
        return 'text-emerald-500'
    }
    if (normalizedLevel === 'warning') {
        return 'text-yellow-500'
    }
    if (
        normalizedLevel === 'error' ||
        normalizedLevel === 'critical' ||
        normalizedLevel === 'alert' ||
        normalizedLevel === 'emergency'
    ) {
        return 'text-red-500'
    }

    return 'text-gray-500'
}

const getLevelCardClass = (levelClass, levelName) => {
    const baseClasses = 'rounded border-l-4 p-3 flex items-center transition-colors duration-150'

    // Normalize level name for comparison
    const normalizedLevel = levelName?.toLowerCase()

    // Check specific level names first for custom colors
    if (normalizedLevel === 'debug') {
        // Debug - Orange
        return `${baseClasses} border-l-orange-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100`
    }

    if (normalizedLevel === 'info') {
        // Info - Blue
        return `${baseClasses} border-l-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100`
    }

    // Then use level class for general categorization
    switch (levelClass) {
        case 'none':
            // All - Neutral gray
            return `${baseClasses} border-l-gray-400 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100`

        case 'info':
            // Fallback for info class - Blue
            return `${baseClasses} border-l-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100`

        case 'notice':
        case 'success':
            // Notice - Green
            return `${baseClasses} border-l-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100`

        case 'warning':
            // Warning - Yellow
            return `${baseClasses} border-l-yellow-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100`

        case 'danger':
            // Danger (includes error, critical, alert, emergency) - Red
            return `${baseClasses} border-l-red-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100`

        default:
            return `${baseClasses} border-l-gray-400 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100`
    }
}

const loadLevelStats = async () => {
    // Don't load if the feature is disabled
    if (!logViewerStore.showSeverityStats) {
        return
    }

    loading.value = true

    try {
        const params = {
            host: fileStore.hostQueryParam,
            exclude_file_types: fileStore.fileTypesExcluded,
        }

        const response = await axios.get(`${window.LogViewer.basePath}/api/level-stats`, { params })
        levelCounts.value = response.data.levelCounts || []
    } catch (error) {
        console.error('Error loading level statistics:', error)
        levelCounts.value = []
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    loadLevelStats()
})

// Reload stats when host, file types, or the setting changes
watch(
    () => [hostStore.selectedHost, fileStore.selectedFileTypes, logViewerStore.showSeverityStats],
    () => {
        loadLevelStats()
    },
    { deep: true },
)
</script>

<style scoped>
.severity-stat-card {
    min-height: 68px;
}

.severity-icon {
    @apply flex-shrink-0 mr-3;
}

.severity-content {
    @apply flex-1 min-w-0;
}

.severity-name {
    @apply font-semibold text-sm mb-1 text-gray-900 dark:text-gray-100;
}

.severity-stats {
    @apply flex justify-between items-center text-xs font-medium;
}

.severity-count {
    @apply truncate text-gray-600 dark:text-gray-400;
}

.severity-percentage {
    @apply flex-shrink-0 ml-2 font-semibold text-gray-700 dark:text-gray-300;
}
</style>
