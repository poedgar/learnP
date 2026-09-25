<script setup>
import { computed } from 'vue';

const props = defineProps({
    output: { type: String, required: true },
    fatal: { type: Boolean, default: false },
    durationMs: { type: Number, default: null },
});

/** Splits the output so PHP's own error and warning messages can be styled. */
const lines = computed(() =>
    props.output.split('\n').map((text) => ({
        text,
        kind: /^(PHP )?(Fatal error|Parse error)/.test(text)
            ? 'line-fatal'
            : /^(PHP )?(Warning|Notice|Deprecated)/.test(text)
              ? 'line-warning'
              : null,
    })),
);
</script>

<template>
    <div class="php-output" aria-live="polite">
        <div class="screen" :class="{ fatal }" tabindex="0" role="region" aria-label="Program output">
            <pre v-if="output.trim()"><span
                v-for="(line, i) in lines"
                :key="i"
                :class="line.kind"
            >{{ line.text }}{{ i < lines.length - 1 ? '\n' : '' }}</span></pre>
            <p v-else class="silent">Your code ran but didn't print anything. Use <code>echo</code> to see values.</p>
        </div>
        <p class="meta">
            <template v-if="fatal">PHP stopped with an error.</template>
            <template v-else>Finished</template>
            <template v-if="durationMs !== null"> in {{ durationMs }} ms.</template>
        </p>
    </div>
</template>

<style scoped>
.screen {
    background: var(--surface);
    border: 1px solid var(--line);
    border-left: 4px solid var(--ok);
    border-radius: var(--radius);
    padding: 0.9rem 1.1rem;
    max-height: 26rem;
    overflow: auto;
}

.screen.fatal {
    border-left-color: var(--bad);
}

pre {
    font-family: var(--font-code);
    font-size: 0.9rem;
    line-height: 1.6;
    white-space: pre-wrap;
    word-break: break-word;
}

.line-fatal {
    color: #9d2531;
    font-weight: 600;
}

.line-warning {
    color: #8a5a00;
}

.silent {
    color: var(--muted);
}

.silent code {
    font-size: 0.88em;
    background: var(--highlight-soft);
    padding: 0.1em 0.35em;
    border-radius: var(--radius-sm);
}

.meta {
    margin-top: 0.4rem;
    font-size: 0.85rem;
    color: var(--muted);
}
</style>
