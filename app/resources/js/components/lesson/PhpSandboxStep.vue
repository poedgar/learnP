<script setup>
import { ref, watch } from 'vue';
import { lessonProgress } from '../../progress';
import { runScript, withOpeningTag } from '../../lib/php/sandbox';
import { usePhpRuntime } from '../../lib/php/usePhpRuntime';
import EditorPanel from '../EditorPanel.vue';
import PhpOutput from '../PhpOutput.vue';

const props = defineProps({
    lesson: { type: Object, required: true },
    /** Code sent over from a "Try it" button: { code, key }. It replaces the editor and runs. */
    request: { type: Object, default: null },
});

const progress = lessonProgress(props.lesson.id);
const code = ref(progress.sandboxCode ?? props.lesson.starter_code ?? '');
const result = ref(null);
const running = ref(false);
const failure = ref('');
const runtime = usePhpRuntime();

watch(code, (value) => (progress.sandboxCode = value));

async function execute() {
    if (running.value) return;
    running.value = true;
    failure.value = '';
    try {
        result.value = await runScript(code.value);
    } catch (error) {
        result.value = null;
        failure.value = error.message;
    } finally {
        running.value = false;
    }
}

function reset() {
    code.value = props.lesson.starter_code ?? '';
    result.value = null;
    failure.value = '';
}

watch(
    () => props.request?.key,
    () => {
        if (props.request) {
            code.value = withOpeningTag(props.request.code);
            execute();
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="sandbox">
        <p class="intro">
            Write any PHP you like and press Run. It runs in your browser, so nothing you do here can affect
            anyone else. Use <code>echo</code> to print values.
        </p>
        <p class="runtime" :class="runtime.status" role="status">{{ runtime.message }}</p>
        <EditorPanel
            v-model="code"
            language="php"
            :running="running"
            label="Sandbox PHP editor"
            reset-label="Restore example"
            @run="execute"
            @reset="reset"
        />
        <p v-if="failure" class="failure" role="alert">{{ failure }}</p>
        <PhpOutput v-if="result" v-bind="result" />
        <p v-else-if="!failure" class="empty">Press Run to see what your code prints.</p>
    </div>
</template>

<style scoped>
.sandbox {
    display: grid;
    gap: 1rem;
    max-width: 52rem;
}

.intro {
    color: var(--muted);
    max-width: 65ch;
}

.intro code {
    font-size: 0.88em;
    background: var(--highlight-soft);
    color: var(--ink);
    padding: 0.1em 0.35em;
    border-radius: var(--radius-sm);
}

.runtime {
    font-size: 0.88rem;
    color: var(--muted);
    margin-bottom: -0.4rem;
}

.runtime.error {
    color: var(--bad);
}

.empty {
    color: var(--muted);
    padding: 2rem 1rem;
    text-align: center;
    border: 1px dashed var(--line);
    border-radius: var(--radius);
}

.failure {
    color: var(--bad);
    font-weight: 700;
}
</style>
