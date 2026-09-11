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

/**
 * Formats a log entry the way it is displayed in the UI, but prefixed with the
 * datetime, environment and severity of the log - just like in the log file.
 * Meant for copying a complete log entry out of the viewer.
 */
export const logToText = (log) => {
  let text = log.full_text || log.message || '';

  // Laravel logs have their `[datetime] env.LEVEL:` prefix stripped while parsing,
  // in which case the text starts with the message. Other log types keep their
  // original first line, and must not get a second timestamp in front of it.
  if (log.message && text.startsWith(log.message)) {
    text = logHeader(log) + text;
  }

  if (log.context && Object.keys(log.context).length > 0) {
    text += "\n\nContext:\n" + formatContext(log.context);
  }

  return text;
};

const logHeader = (log) => {
  // The datetime as written in the log file, rather than the one displayed in the
  // UI, which has been reformatted and converted to the configured timezone.
  const datetime = log.original_datetime || log.datetime;
  const environment = log.extra?.environment;
  const level = [environment, log.level].filter(Boolean).join('.');

  return ['[' + datetime + ']', level ? level + ':' : null].filter(Boolean).join(' ') + ' ';
};

const formatContext = (context) => {
  // Stack traces inside the context are a lot easier to read with actual line
  // breaks instead of the escaped ones JSON.stringify() leaves behind.
  return JSON.stringify(context, null, 2).replace(/\\n/g, "\n");
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
