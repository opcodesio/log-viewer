import {
  KeyShortcuts,
  focusFirstLogEntry,
  focusActiveOrFirstFile
} from './shared.js';
import { useLogViewerStore } from '../stores/logViewer.js';

const globalKeyboardEventHandler = (event) => {
  // if event.target is an <input> element, we don't want to handle the keyboard shortcuts
  if (event.target.tagName === 'INPUT') return;
  if (event.metaKey || event.ctrlKey) return;

  if (event.key === KeyShortcuts.ShortcutHelp) {
    event.preventDefault();
    const logViewerStore = useLogViewerStore();
    logViewerStore.helpSlideOverOpen = !logViewerStore.helpSlideOverOpen;
  } else if (event.key === KeyShortcuts.Files) {
    event.preventDefault();
    focusActiveOrFirstFile();
  } else if (event.key === KeyShortcuts.Logs) {
    event.preventDefault();
    focusFirstLogEntry();
  } else if (event.key === KeyShortcuts.Hosts) {
    event.preventDefault();
    const hostsButton = document.getElementById('hosts-toggle-button');
    hostsButton?.click();
  } else if (event.key === KeyShortcuts.Severity) {
    event.preventDefault();
    const severityButton = document.getElementById('severity-dropdown-toggle');
    severityButton?.click();
  } else if (event.key === KeyShortcuts.Settings) {
    event.preventDefault();
    const settingsButton = document.querySelector('#desktop-site-settings .menu-button');
    settingsButton?.click();
  } else if (event.key === KeyShortcuts.Search) {
    event.preventDefault();
    const searchInput = document.getElementById('query');
    searchInput?.focus();
  } else if (event.key === KeyShortcuts.Refresh) {
    event.preventDefault();
    const refreshButton = document.getElementById('reload-logs-button');
    refreshButton?.click();
  }
};

export const registerGlobalShortcuts = () => {
  document.addEventListener('keydown', globalKeyboardEventHandler);
}

export const unregisterGlobalShortcuts = () => {
  document.removeEventListener('keydown', globalKeyboardEventHandler);
}
