<template>
  <div class="p-4 lg:p-8">
    <!-- Exception Header -->
    <div v-if="stackTrace.header" class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-600">
      <div class="text-red-600 dark:text-red-400 font-semibold text-lg mb-2">
        {{ stackTrace.header.type }}
      </div>
      <div class="text-gray-800 dark:text-gray-200 text-base mb-2">
        {{ stackTrace.header.message }}
      </div>
      <div class="text-sm text-gray-600 dark:text-gray-400 font-mono">
        in {{ stackTrace.header.file }}:{{ stackTrace.header.line }}
      </div>
    </div>

    <!-- Stack Trace Frames -->
    <div class="space-y-2">
      <div v-for="(frame, frameIndex) in stackTrace.frames" :key="frameIndex"
           class="mb-2 border-b border-gray-100 dark:border-gray-700 pb-2 last:border-b-0">
        <div class="flex items-start gap-2">
          <div class="text-xs text-gray-500 dark:text-gray-400 font-mono w-8 flex-shrink-0 pt-1">
            #{{ frame.number }}
          </div>
          <div class="flex-1 min-w-0">
            <div v-if="frame.file" class="text-xs mb-1">
              <span class="font-mono text-blue-600 dark:text-blue-400 break-all">{{ frame.file }}</span>
              <span class="text-gray-500 dark:text-gray-400 mx-0.5">:</span>
              <span class="font-mono text-orange-600 dark:text-orange-400">{{ frame.line }}</span>
            </div>
            <div class="text-xs text-gray-800 dark:text-gray-200 font-mono break-all">
              {{ frame.call }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error Fallback -->
    <div v-if="!stackTrace.header && stackTrace.frames.length === 0" class="text-gray-500 dark:text-gray-400 text-sm italic">
      Unable to parse stack trace. View the Raw tab for full details.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  log: {
    type: Object,
    required: true
  }
});

/**
 * Parses the Laravel exception stack trace from the log context.
 * This computed property ensures the expensive parsing operation only happens once per log.
 */
const stackTrace = computed(() => {
  try {
    const exception = Array.isArray(props.log.context)
      ? props.log.context.find(item => item.exception)?.exception
      : props.log.context?.exception;

    if (!exception || typeof exception !== 'string') {
      return { header: null, frames: [] };
    }

    // Parse exception header
    // Format: [object] (ExceptionType(code: 0): Message at /path/file.php:123)
    const headerMatch = exception.match(/^\[object\]\s*\(([^(]+)\(code:\s*\d+\):\s*(.+?)\s+at\s+(.+?):(\d+)\)/);
    const header = headerMatch ? {
      type: headerMatch[1].trim(),
      message: headerMatch[2].trim(),
      file: headerMatch[3].trim(),
      line: parseInt(headerMatch[4])
    } : null;

    // Parse stack trace frames
    // Format: #0 /path/file.php(123): Class::method()
    const stacktraceMatch = exception.match(/\[stacktrace\]([\s\S]*?)(?:\n\n|\n$|$)/);
    const frames = [];

    if (stacktraceMatch) {
      const frameRegex = /#(\d+)\s+(.+?)(?:\n|$)/g;
      let match;

      while ((match = frameRegex.exec(stacktraceMatch[1])) !== null) {
        const frameLine = match[2].trim();
        const fileMatch = frameLine.match(/^(.+?)\((\d+)\):\s*(.+)$/);

        frames.push(fileMatch ? {
          number: parseInt(match[1]),
          file: fileMatch[1],
          line: parseInt(fileMatch[2]),
          call: fileMatch[3]
        } : {
          number: parseInt(match[1]),
          file: '',
          line: 0,
          call: frameLine
        });
      }
    }

    return { header, frames };
  } catch (error) {
    // Gracefully handle parsing errors
    console.error('Error parsing stack trace:', error);
    return { header: null, frames: [] };
  }
});
</script>
