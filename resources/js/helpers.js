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
