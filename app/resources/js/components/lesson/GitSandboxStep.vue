<script setup>
import { ref, watch } from 'vue';
import { lessonProgress } from '../../progress';
import { runGit } from '../../lib/git/engine';
import EditorPanel from '../EditorPanel.vue';
import GitOutput from '../GitOutput.vue';

const props = defineProps({
    lesson: { type: Object, required: true },
    /** Code sent over from a "Try it" button: { code, key }. It replaces the editor and runs. */
    request: { type: Object, default: null },
});

const progress = lessonProgress(props.lesson.id);
const code = ref(progress.sandboxCode ?? props.lesson.starter_code ?? '');
const result = ref(null);

watch(code, (value) => (progress.sandboxCode = value));

function execute() {
    result.value = runGit(code.value);
}

function reset() {
    code.value = props.lesson.starter_code ?? '';
    result.value = null;
}

watch(
    () => props.request?.key,
    () => {
        if (props.request) {
            code.value = props.request.code;
            execute();
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="sandbox">
        <p class="intro">
            Type commands, one per line, and press Run. They run in a small Git inside your browser, on a fresh
            machine each time, so you can't break anything.
        </p>
        <EditorPanel
            v-model="code"
            language="git"
            label="Sandbox terminal script"
            reset-label="Restore example"
            @run="execute"
            @reset="reset"
        />
        <GitOutput v-if="result" :entries="result.entries" />
        <p v-else class="empty">Press Run to see what the commands print.</p>
    </div>
</template>

<style scoped>
.sandbox {
    display: grid;
    gap: 1rem;
    padding: 1.5rem 0 3rem;
}

.intro,
.empty {
    color: var(--muted);
}
</style>
