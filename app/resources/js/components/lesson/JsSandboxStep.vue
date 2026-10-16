<script setup>
import { computed, ref, watch } from 'vue';
import { lessonProgress } from '../../progress';
import { runCode, useTypescriptRuntime } from '../../lib/js/sandbox';
import { usePagePreview } from '../../lib/js/usePagePreview';
import EditorPanel from '../EditorPanel.vue';
import JsOutput from '../JsOutput.vue';
import PagePreview from '../PagePreview.vue';

const props = defineProps({
    lesson: { type: Object, required: true },
    /** javascript, typescript or html */
    language: { type: String, required: true },
    /** Code sent over from a "Try it" button: { code, key }. It replaces the editor and runs. */
    request: { type: Object, default: null },
});

const progress = lessonProgress(props.lesson.id);
const code = ref(progress.sandboxCode ?? props.lesson.starter_code ?? '');
const result = ref(null);
const running = ref(false);
const failure = ref('');
const page = usePagePreview();
const typescript = props.language === 'typescript' ? useTypescriptRuntime() : null;
const name = computed(() => ({ typescript: 'TypeScript', html: 'HTML and CSS' })[props.language] ?? 'JavaScript');
const isHtml = props.language === 'html';

watch(code, (value) => (progress.sandboxCode = value));

// HTML has nothing to wait for, so the page follows the code as you type.
let typing = null;
if (isHtml) {
    watch(code, () => {
        clearTimeout(typing);
        typing = setTimeout(function again() {
            typing = running.value ? setTimeout(again, 200) : (execute(), null);
        }, 500);
    });
}

async function execute() {
    if (running.value) return;
    running.value = true;
    failure.value = '';
    page.clear();
    try {
        result.value = await runCode(code.value, { language: props.language, host: page.hostElement() });
        page.show(result.value);
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
    page.clear();
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
        <p v-if="isHtml" class="intro">
            Write HTML, with CSS in a <code>&lt;style&gt;</code> element, and see the page below. It updates as you
            type, in a sandbox that can't touch this page.
        </p>
        <p v-else class="intro">
            Write any {{ name }} you like and press Run. It runs in your browser, in a sandbox that can't touch this
            page or anything else. Use <code>console.log()</code> to print values.
            <template v-if="typescript">TypeScript checks your types first; code with type errors doesn't run.</template>
        </p>
        <p v-if="typescript" class="runtime" :class="typescript.status" role="status">{{ typescript.message }}</p>
        <EditorPanel
            v-model="code"
            :language="language"
            :running="running"
            :label="`Sandbox ${name} editor`"
            reset-label="Restore example"
            @run="execute"
            @reset="reset"
        />
        <p v-if="failure" class="failure" role="alert">{{ failure }}</p>
        <JsOutput v-if="result" v-bind="result" :quiet="isHtml" />
        <p v-else-if="!failure" class="empty">{{ isHtml ? 'Press Run to see your page.' : 'Press Run to see what your code prints.' }}</p>
        <PagePreview :ref="page.preview" :visible="Boolean(page.frame.value)" />
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
