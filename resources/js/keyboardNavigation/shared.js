export const fileItemClass = 'file-item-info';
export const fileSettingsButtonClass = 'file-dropdown-toggle';
export const logToggleButtonClass = 'log-level-icon';
export const logLinkClass = 'log-link.large-screen';

export const KeyShortcuts = {
  Files: 'f',
  Logs: 'l',
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
