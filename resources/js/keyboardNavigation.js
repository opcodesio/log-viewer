const fileItemClass = 'file-item-info';
const fileSettingsButtonClass = 'file-dropdown-toggle';
const logToggleButtonClass = 'log-level-icon';
const logLinkClass = 'log-link.large-screen';

export const handleKeyboardFileNavigation = (event) => {
  if (event.key === 'ArrowUp') {
    const previousElement = getPreviousElementWithClass(document.activeElement, fileItemClass);
    if (previousElement) {
      event.preventDefault();
      previousElement.focus();
    }
  } else if (event.key === 'ArrowDown') {
    const nextElement = getNextElementWithClass(document.activeElement, fileItemClass);
    if (nextElement) {
      event.preventDefault();
      nextElement.focus();
    }
  } else if (event.key === 'ArrowRight') {
    event.preventDefault();
    document.activeElement.nextElementSibling.focus();
  }
};

export const handleKeyboardFileSettingsNavigation = (event) => {
  if (event.key === 'ArrowLeft') {
    event.preventDefault();
    document.activeElement.previousElementSibling.focus();
  } else if (event.key === 'ArrowRight') {
    const logToggleButtons = Array.from(document.querySelectorAll(`.${logToggleButtonClass}`));
    if (logToggleButtons.length > 0) {
      event.preventDefault();
      logToggleButtons[0].focus();
    }
  }
}

export const handleLogToggleKeyboardNavigation = (event) => {
  if (event.key === 'ArrowLeft') {
    event.preventDefault();
    const activeFile = document.querySelector('.file-item-container.active .file-item-info');
    if (activeFile) {
      activeFile.nextElementSibling.focus();
    } else {
      const firstFile = document.querySelector('.file-item-container .file-item-info');
      if (firstFile) {
        firstFile.nextElementSibling.focus();
      }
    }
  } else if (event.key === 'ArrowRight') {
    const logIndex = getIndexOfElementWithClass(document.activeElement, logToggleButtonClass);
    const logLinks = Array.from(document.querySelectorAll(`.${logLinkClass}`));
    if (logLinks.length > logIndex) {
      event.preventDefault();
      logLinks[logIndex].focus();
    }
  } else if (event.key === 'ArrowUp') {
    const previousElement = getPreviousElementWithClass(document.activeElement, logToggleButtonClass);
    if (previousElement) {
      event.preventDefault();
      previousElement.focus();
    }
  } else if (event.key === 'ArrowDown') {
    const nextElement = getNextElementWithClass(document.activeElement, logToggleButtonClass);
    if (nextElement) {
      event.preventDefault();
      nextElement.focus();
    }
  }
};

export const handleLogLinkKeyboardNavigation = (event) => {
  if (event.key === 'ArrowLeft') {
    const logIndex = getIndexOfElementWithClass(document.activeElement, logLinkClass);
    const logToggleButtons = Array.from(document.querySelectorAll(`.${logToggleButtonClass}`));
    if (logToggleButtons.length > logIndex) {
      event.preventDefault();
      logToggleButtons[logIndex].focus();
    }
  } else if (event.key === 'ArrowUp') {
    const previousElement = getPreviousElementWithClass(document.activeElement, logLinkClass);
    if (previousElement) {
      event.preventDefault();
      previousElement.focus();
    }
  } else if (event.key === 'ArrowDown') {
    const nextElement = getNextElementWithClass(document.activeElement, logLinkClass);
    if (nextElement) {
      event.preventDefault();
      nextElement.focus();
    }
  } else if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault();
    const el = document.activeElement;
    el.click();
    el.focus();
  }
}

const getPreviousElementWithClass = (element, className) => {
  const elements = Array.from(document.querySelectorAll(`.${className}`));
  const currentIndex = elements.findIndex(el => el === element);

  // Let's find the first previous element that's not hidden
  let previousIndex = currentIndex - 1;
  while (previousIndex >= 0 && elements[previousIndex].offsetParent === null) {
    previousIndex--;
  }

  return elements[previousIndex] ? elements[previousIndex] : null;
};

const getNextElementWithClass = (element, className) => {
  const elements = Array.from(document.querySelectorAll(`.${className}`));
  const currentIndex = elements.findIndex(el => el === element);

  // Let's find the first next element that's not hidden
  let nextIndex = currentIndex + 1;
  while (nextIndex < elements.length && elements[nextIndex].offsetParent === null) {
    nextIndex++;
  }

  return elements[nextIndex] ? elements[nextIndex] : null;
};

const getIndexOfElementWithClass = (element, className) => {
  const elements = Array.from(document.querySelectorAll(`.${className}`));
  return elements.findIndex(el => el === element);
}
