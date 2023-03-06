import {
  focusActiveOrFirstFileSettings,
  getPreviousElementWithClass,
  getIndexOfElementWithClass,
  getNextElementWithClass,
  logToggleButtonClass,
  logLinkClass,
} from './shared.js';

export const handleLogToggleKeyboardNavigation = (event) => {
  if (event.key === 'ArrowLeft') {
    event.preventDefault();
    focusActiveOrFirstFileSettings();
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
