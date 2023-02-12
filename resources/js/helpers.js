import { useRoute, useRouter } from 'vue-router';

export const highlightSearchResult = (text, query = null) => {
  if (query) {
    if (!query.endsWith('/i')) {
      query = '/' + query + '/i';
    }

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
    .replace(/&lt;\/mark&gt;/g, '</mark>');
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
    file: route.query.file || undefined,
    query: route.query.query || undefined,
    page: route.query.page || undefined,
  };

  if (value) {
    query[key] = value;
  } else {
    delete query[key];
  }

  router.push({ name: 'home', query });
};
