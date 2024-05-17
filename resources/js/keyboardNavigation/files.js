import {focusNextFile, focusPreviousFile, logToggleButtonClass} from './shared.js';

export const handleKeyboardFileNavigation = (event) => {
  if (event.key === 'ArrowUp') {
    event.preventDefault();
    focusPreviousFile();
  } else if (event.key === 'ArrowDown') {
    event.preventDefault();
    focusNextFile();
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
