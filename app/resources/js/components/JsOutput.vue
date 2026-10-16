<script setup>
import { computed } from 'vue';

const props = defineProps({
    /** What console.log and friends printed: [{ level: 'log' | 'warn' | 'error', text }]. */
    entries: { type: Array, default: () => [] },
    /** An error that stopped the code, such as a SyntaxError. */
    error: { type: String, default: null },
    /** TypeScript's type errors: [{ line, message }]. Code with type errors doesn't run. */
    typeErrors: { type: Array, default: () => [] },
    /** A timer, such as setInterval, was still going when the run stopped waiting. */
    stillRunning: { type: Boolean, default: false },
    durationMs: { type: Number, default: null },
    /** The code built a page, shown next to this output. */
    hasPage: { type: Boolean, default: false },
    /** Show nothing when nothing was printed and nothing went wrong (HTML, where the page is the result). */
    quiet: { type: Boolean, default: false },
});

const failed = computed(() => Boolean(props.error) || props.typeErrors.length > 0);
const silent = computed(() => props.entries.length === 0 && !failed.value);
</script>

<template>
    <div v-if="!(quiet && silent)" class="js-output" aria-live="polite">
        <div class="screen" :class="{ failed }" tabindex="0" role="region" aria-label="Program output">
            <ul v-if="typeErrors.length" class="type-errors">
                <li v-for="(problem, i) in typeErrors" :key="i">
                    <span class="where">Line {{ problem.line }}</span> {{ problem.message }}
                </li>
            </ul>
            <pre v-if="entries.length || error"><span
                v-for="(entry, i) in entries"
                :key="i"
                :class="`line-${entry.level}`"
            >{{ entry.text }}{{ '\n' }}</span><span v-if="error" class="line-error">{{ error }}</span></pre>
            <p v-else-if="silent" class="silent">
                Your code ran but didn't print anything.
                <template v-if="hasPage">See the page it built below, or use <code>console.log()</code> to print values.</template>
                <template v-else>Use <code>console.log()</code> to see values.</template>
            </p>
        </div>
        <p class="meta">
            <template v-if="typeErrors.length">
                TypeScript found {{ typeErrors.length === 1 ? 'a type error' : `${typeErrors.length} type errors` }}, so
                the code didn't run.
            </template>
            <template v-else-if="error">Your code stopped with an error.</template>
            <template v-else>Finished</template>
            <template v-if="durationMs !== null && !typeErrors.length"> in {{ durationMs }} ms.</template>
            <template v-if="stillRunning"> A timer was still running after 5 seconds, so the sandbox stopped waiting for it.</template>
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
    display: grid;
    gap: 0.6rem;
}

.screen.failed {
    border-left-color: var(--bad);
}

pre {
    font-family: var(--font-code);
    font-size: 0.9rem;
    line-height: 1.6;
    white-space: pre-wrap;
    word-break: break-word;
}

.line-error {
    color: #9d2531;
    font-weight: 600;
}

.line-warn {
    color: #8a5a00;
}

.type-errors {
    display: grid;
    gap: 0.35rem;
    font-family: var(--font-code);
    font-size: 0.88rem;
    color: #7d1f29;
}

.where {
    font-weight: 700;
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
