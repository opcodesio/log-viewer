import { ref } from 'vue';

export const highlightSearchResult = (text, query = null) => {
  text = text || '';

  if (query) {
    try {
      text = text.replace(new RegExp(query, 'gi'), '<mark>$&</mark>');
    } catch (e) {
      // in case the regex is invalid, we want to just continue without marking any text.
    }
  }

  // Let's return the <mark> tags which we use for highlighting the search results
  // while escaping the rest of the HTML entities
  return escapeHtml(text)
    .replace(/&lt;mark&gt;/g, '<mark>')
    .replace(/&lt;\/mark&gt;/g, '</mark>')
    .replace(/&lt;br\/&gt;/g, '<br/>');
};

export const escapeHtml = (text) => {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };

  return text.replace(/[&<>"']/g, m => map[m]);
}

export const copyToClipboard = (str) => {
  const el = document.createElement('textarea');
  el.value = str;
  el.setAttribute('readonly', '');
  el.style.position = 'absolute';
  el.style.left = '-9999px';
  document.body.appendChild(el);
  const selected =
    document.getSelection().rangeCount > 0
      ? document.getSelection().getRangeAt(0)
      : false;
  el.select();
  document.execCommand('copy');
  document.body.removeChild(el);
  if (selected) {
    document.getSelection().removeAllRanges();
    document.getSelection().addRange(selected);
  }
};

export const replaceQuery = (router, key, value) => {
  const route = router.currentRoute.value;
  const query = {
    host: route.query.host || undefined,
    file: route.query.file || undefined,
    query: route.query.query || undefined,
    page: route.query.page || undefined,
  };

  // maybe this logic shouldn't be here, but that's what works for now.
  // calling `replaceQuery` twice in a single "tick" can cause previous change to be reverted.
  if (key === 'host') {
    query.file = undefined;
    query.page = undefined;
  } else if (key === 'file' && query.page !== undefined) {
    query.page = undefined;
  }

  query[key] = value ? String(value) : undefined;

  router.push({ name: 'home', query });
};

export const useDropdownDirection = () => {
  const dropdownDirections = ref({});

  const getDropdownDirection = (buttonElement) => {
    const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
    const boundingRect = buttonElement.getBoundingClientRect();

    return (boundingRect.bottom + 190) < viewportHeight ? 'down' : 'up';
  }

  const calculateDropdownDirection = (toggleButton) => {
    dropdownDirections.value[toggleButton.dataset.toggleId] = getDropdownDirection(toggleButton);
  }

  return { dropdownDirections, calculateDropdownDirection };
}

export const isMobile = () => {
  return window.matchMedia('(max-width: 768px)').matches;
}
