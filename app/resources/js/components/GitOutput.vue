<script setup>
/** A terminal for Git lessons: each command, then what it printed. Errors are marked. */
defineProps({
    /** From runGit() in lib/git/engine.js: [{ level: 'command' | 'log' | 'error', text }]. */
    entries: { type: Array, required: true },
});
</script>

<template>
    <div class="git-output" aria-live="polite">
        <div class="screen" tabindex="0" role="region" aria-label="Terminal output">
            <pre v-if="entries.length"><template v-for="(entry, i) in entries" :key="i"><span :class="`line-${entry.level}`">{{ entry.text }}</span>{{ i < entries.length - 1 ? '\n' : '' }}</template></pre>
            <p v-else class="silent">Nothing ran. Write some commands, one per line, and press Run.</p>
        </div>
    </div>
</template>

<style scoped>
.screen {
    padding: 1rem 1.25rem;
    border-radius: var(--radius);
    background: #18233f;
    color: #eef1f8;
    overflow-x: auto;
}

pre {
    margin: 0;
    font-size: 0.92rem;
    line-height: 1.55;
}

.line-command {
    color: #f5b83d;
    font-weight: 700;
}

.line-error {
    color: #ff9aa3;
}

.silent {
    color: #b9c2d8;
}
</style>
