import {
  getPreviousElementWithClass,
  getNextElementWithClass,
  logToggleButtonClass,
  fileItemClass,
} from './shared.js';

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
    event.preventDefault();
    const logToggleButtons = Array.from(document.querySelectorAll(`.${logToggleButtonClass}`));
    if (logToggleButtons.length > 0) {
      logToggleButtons[0].focus();
    }
  }
}
