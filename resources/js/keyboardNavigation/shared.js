export const fileItemClass = 'file-item-info';
export const fileSettingsButtonClass = 'file-dropdown-toggle';
export const logToggleButtonClass = 'log-level-icon';
export const logLinkClass = 'log-link.large-screen';

export const KeyShortcuts = {
  Files: 'f',
  Logs: 'l',
  Next: 'j',
  Previous: 'k',
  NextLog: 'n',
  PreviousLog: 'p',
  Hosts: 'h',
  Severity: 's',
  Settings: 'g',
  Search: '/',
  Refresh: 'r',
  ShortcutHelp: '?',
}

export const focusFirstLogEntry = () => {
  const logToggleButtons = Array.from(document.querySelectorAll(`.${logToggleButtonClass}`));
  if (logToggleButtons.length > 0) {
    logToggleButtons[0].focus();
  }
}

export const focusLastLogEntry = () => {
  const logToggleButtons = Array.from(document.querySelectorAll(`.${logToggleButtonClass}`));
  if (logToggleButtons.length > 0) {
    logToggleButtons[logToggleButtons.length - 1].focus();
  }
}

export const ensureIsExpanded = (element) => {
  const isExpanded = element.getAttribute('aria-expanded') === 'true';
  if (!isExpanded) {
    element.click();
  }
}

export const ensureIsCollapsed = (element) => {
  const isExpanded = element.getAttribute('aria-expanded') === 'true';
  if (isExpanded) {
    element.click();
  }
}

export const openNextLogEntry = () => {
  const el = document.activeElement;
  const nextElement = getNextElementWithClass(el, logToggleButtonClass);
  if (!nextElement) {
    const onNextPageLoad = () => {
      setTimeout(() => {
        focusFirstLogEntry();
        ensureIsExpanded(document.activeElement);
      }, 50)
      document.removeEventListener('logsPageLoaded', onNextPageLoad);
    };
    document.addEventListener('logsPageLoaded', onNextPageLoad);
    document.dispatchEvent(new Event('goToNextPage'));
    return;
  }
  ensureIsCollapsed(el);
  nextElement.focus();
  ensureIsExpanded(nextElement);
}

export const openPreviousLogEntry = () => {
  const el = document.activeElement;
  const previousElement = getPreviousElementWithClass(el, logToggleButtonClass);
  if (!previousElement) {
    const onPreviousPageLoad = () => {
      setTimeout(() => {
        focusLastLogEntry();
        ensureIsExpanded(document.activeElement);
      }, 50)
      document.removeEventListener('logsPageLoaded', onPreviousPageLoad);
    };
    document.addEventListener('logsPageLoaded', onPreviousPageLoad);
    document.dispatchEvent(new Event('goToPreviousPage'));
    return;
  }
  ensureIsCollapsed(el);
  previousElement.focus();
  ensureIsExpanded(previousElement);
}

export const focusActiveOrFirstFile = () => {
  const activeFile = document.querySelector('.file-item-container.active .file-item-info');
  if (activeFile) {
    activeFile.focus();
  } else {
    const firstFile = document.querySelector('.file-item-container .file-item-info');
    firstFile?.focus();
  }
};

export const focusActiveOrFirstFileSettings = () => {
  const activeFile = document.querySelector('.file-item-container.active .file-item-info');
  if (activeFile) {
    activeFile.nextElementSibling.focus();
  } else {
    const firstFile = document.querySelector('.file-item-container .file-item-info');
    firstFile?.nextElementSibling?.focus();
  }
};

export const focusNextFile = () => {
  const nextElement = getNextElementWithClass(document.activeElement, fileItemClass);
  if (nextElement) {
    nextElement.focus();
  }
}

export const focusPreviousFile = () => {
  const previousElement = getPreviousElementWithClass(document.activeElement, fileItemClass);
  if (previousElement) {
    previousElement.focus();
  }
}

export const getPreviousElementWithClass = (element, className) => {
  const elements = Array.from(document.querySelectorAll(`.${className}`));
  const currentIndex = elements.findIndex(el => el === element);

  // Let's find the first previous element that's not hidden
  let previousIndex = currentIndex - 1;
  while (previousIndex >= 0 && elements[previousIndex].offsetParent === null) {
    previousIndex--;
  }

  return elements[previousIndex] ? elements[previousIndex] : null;
};

export const getNextElementWithClass = (element, className) => {
  const elements = Array.from(document.querySelectorAll(`.${className}`));
  const currentIndex = elements.findIndex(el => el === element);

  // Let's find the first next element that's not hidden
  let nextIndex = currentIndex + 1;
  while (nextIndex < elements.length && elements[nextIndex].offsetParent === null) {
    nextIndex++;
  }

  return elements[nextIndex] ? elements[nextIndex] : null;
};

export const getIndexOfElementWithClass = (element, className) => {
  const elements = Array.from(document.querySelectorAll(`.${className}`));
  return elements.findIndex(el => el === element);
}
