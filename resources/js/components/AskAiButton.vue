<template>
  <div ref="containerRef" class="relative inline-block">
    <button
      @click.stop="toggleDropdown"
      class="log-link group"
      title="Ask AI for help with this error"
    >
      <SparklesIcon class="w-4 h-4 opacity-75 group-hover:opacity-100" />
    </button>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showDropdown"
        class="absolute right-0 mt-1 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700"
        style="min-width: 170px; z-index: 999999"
      >
        <div class="py-1">
          <button
            @click="copyForAi"
            class="flex items-center w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            <ClipboardDocumentIcon class="w-3.5 h-3.5 mr-2" />
            {{ copied ? 'Copied!' : 'Copy for AI' }}
          </button>

          <div class="border-t border-gray-100 dark:border-gray-700"></div>

          <button
            @click="openInChatGpt"
            class="flex items-center w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 mr-2">
              <path d="M22.282 9.821a5.985 5.985 0 0 0-.516-4.91 6.046 6.046 0 0 0-6.51-2.9A6.065 6.065 0 0 0 4.981 4.18a5.985 5.985 0 0 0-3.998 2.9 6.046 6.046 0 0 0 .743 7.097 5.98 5.98 0 0 0 .51 4.911 6.051 6.051 0 0 0 6.515 2.9A5.985 5.985 0 0 0 13.26 24a6.056 6.056 0 0 0 5.772-4.206 5.99 5.99 0 0 0 3.997-2.9 6.056 6.056 0 0 0-.747-7.073zM13.26 22.43a4.476 4.476 0 0 1-2.876-1.04l.141-.081 4.779-2.758a.795.795 0 0 0 .392-.681v-6.737l2.02 1.168a.071.071 0 0 1 .038.052v5.583a4.504 4.504 0 0 1-4.494 4.494zM3.6 18.304a4.47 4.47 0 0 1-.535-3.014l.142.085 4.783 2.759a.771.771 0 0 0 .78 0l5.843-3.369v2.332a.08.08 0 0 1-.033.062L9.74 19.95a4.5 4.5 0 0 1-6.14-1.646zM2.34 7.896a4.485 4.485 0 0 1 2.366-1.973V11.6a.766.766 0 0 0 .388.676l5.815 3.355-2.02 1.168a.076.076 0 0 1-.071 0l-4.83-2.786A4.504 4.504 0 0 1 2.34 7.872zm16.597 3.855l-5.833-3.387L15.119 7.2a.076.076 0 0 1 .071 0l4.83 2.791a4.494 4.494 0 0 1-.676 8.105v-5.678a.79.79 0 0 0-.407-.667zm2.01-3.023l-.141-.085-4.774-2.782a.776.776 0 0 0-.785 0L9.409 9.23V6.897a.066.066 0 0 1 .028-.061l4.83-2.787a4.5 4.5 0 0 1 6.68 4.66zm-12.64 4.135l-2.02-1.164a.08.08 0 0 1-.038-.057V6.075a4.5 4.5 0 0 1 7.375-3.453l-.142.08L8.704 5.46a.795.795 0 0 0-.393.681zm1.097-2.365l2.602-1.5 2.607 1.5v2.999l-2.597 1.5-2.607-1.5z"/>
            </svg>
            Open in ChatGPT
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { SparklesIcon, ClipboardDocumentIcon } from '@heroicons/vue/24/outline';
import { copyToClipboard } from '../helpers.js';

const props = defineProps({
  log: {
    type: Object,
    required: true,
  },
});

const containerRef = ref(null);
const showDropdown = ref(false);
const copied = ref(false);

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (event) => {
  if (containerRef.value && !containerRef.value.contains(event.target)) {
    showDropdown.value = false;
  }
};

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value;
};

const sanitize = (text) => {
  if (!text) return text;
  const patterns = [
    [/Bearer\s+[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+/gi, 'Bearer [REDACTED]'],
    [/api[_\-]?key[\s]*[=:]\s*["']?[\w\-]+["']?/gi, 'api_key=[REDACTED]'],
    [/password[\s]*[=:]\s*["']?[^"'\s]+["']?/gi, 'password=[REDACTED]'],
    [/token[\s]*[=:]\s*["']?[\w\-]+["']?/gi, 'token=[REDACTED]'],
    [/secret[\s]*[=:]\s*["']?[\w\-]+["']?/gi, 'secret=[REDACTED]'],
    [/\b\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}\b/g, '[REDACTED_CARD]'],
    [/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/g, '[REDACTED_DOC]'],
  ];
  let result = text;
  for (const [regex, replacement] of patterns) {
    result = result.replace(regex, replacement);
  }
  return result;
};

const sanitizeContext = (context) => {
  if (!context || typeof context !== 'object') return context;
  const sensitiveKeys = ['password', 'pwd', 'pass', 'secret', 'token', 'api_key', 'apikey', 'access_token', 'refresh_token', 'private_key', 'credit_card', 'card_number', 'cvv', 'cpf', 'ssn'];
  const sanitized = JSON.parse(JSON.stringify(context));
  const walk = (obj) => {
    for (const key in obj) {
      if (typeof obj[key] === 'object' && obj[key] !== null) {
        walk(obj[key]);
      } else if (sensitiveKeys.some(sk => key.toLowerCase().includes(sk))) {
        obj[key] = '[REDACTED]';
      }
    }
  };
  walk(sanitized);
  return sanitized;
};

const extractExceptionClass = (text) => {
  if (!text) return null;
  const match = text.match(/^(\w+\\)*\w+Exception/m);
  return match ? match[0] : null;
};

const formatLogAsMarkdown = (log, { truncateStackTrace = false, maxChars = 0 } = {}) => {
  const level = (log.level_name || 'ERROR').toUpperCase();
  const message = log.message || 'No message available';
  const datetime = log.datetime || '';
  const appName = window.LogViewer?.app_name || 'Laravel Application';
  const exceptionClass = extractExceptionClass(log.full_text);
  const fullText = sanitize(log.full_text || '');

  let md = `## Error Analysis Request\n\n`;
  md += `**Application**: ${appName}\n`;
  md += `**Level**: ${level}\n`;
  md += `**Timestamp**: ${datetime}\n`;
  if (exceptionClass) md += `**Exception**: ${exceptionClass}\n`;
  md += `\n### Message\n\n${message}\n`;

  if (fullText) {
    let stackTrace = fullText;
    if (truncateStackTrace && maxChars > 0) {
      const headerLength = md.length + 50;
      const available = maxChars - headerLength - 200;
      if (available > 0 && stackTrace.length > available) {
        stackTrace = stackTrace.substring(0, available) + '\n... [truncated]';
      }
    }
    md += `\n### Stack Trace\n\n\`\`\`\n${stackTrace}\n\`\`\`\n`;
  }

  const context = log.context && Object.keys(log.context).length > 0 ? log.context : null;
  if (context) {
    const sanitizedContext = sanitizeContext(context);
    md += `\n### Context\n\n\`\`\`json\n${JSON.stringify(sanitizedContext, null, 2)}\n\`\`\`\n`;
  }

  md += `\n---\n\nPlease analyze this error and provide:\n`;
  md += `1. **Root Cause**: What is causing this error?\n`;
  md += `2. **Fix**: Step-by-step solution\n`;
  md += `3. **Code Examples**: Corrected code if applicable\n`;
  md += `4. **Prevention**: How to prevent this in the future\n`;

  return md;
};

const copyForAi = async () => {
  const markdown = formatLogAsMarkdown(props.log);
  try {
    await navigator.clipboard.writeText(markdown);
  } catch {
    copyToClipboard(markdown);
  }
  copied.value = true;
  setTimeout(() => {
    copied.value = false;
    showDropdown.value = false;
  }, 1000);
};

const openInChatGpt = () => {
  const markdown = formatLogAsMarkdown(props.log, { truncateStackTrace: true, maxChars: 1800 });
  const url = `https://chatgpt.com/?q=${encodeURIComponent(markdown)}`;
  window.open(url, '_blank');
  showDropdown.value = false;
};
</script>
