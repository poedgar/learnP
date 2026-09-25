<script setup>
import { ref, watch } from 'vue';
import { api } from '../../api';
import { lessonProgress } from '../../progress';
import EditorPanel from '../EditorPanel.vue';
import RunResults from '../RunResults.vue';
import SchemaPanel from '../SchemaPanel.vue';

const props = defineProps({
    lesson: { type: Object, required: true },
    schema: { type: Array, required: true },
    /** Code sent over from a "Try it" button: { code, key }. It replaces the editor and runs. */
    request: { type: Object, default: null },
});

const progress = lessonProgress(props.lesson.id);
const sql = ref(progress.sandboxCode ?? props.lesson.starter_code ?? '');
const run = ref(null);
const running = ref(false);
const failure = ref('');

watch(sql, (value) => (progress.sandboxCode = value));

async function execute() {
    if (running.value) return;
    running.value = true;
    failure.value = '';
    try {
        run.value = await api.run(props.lesson.id, sql.value);
    } catch (error) {
        failure.value = error.message;
    } finally {
        running.value = false;
    }
}

function reset() {
    sql.value = props.lesson.starter_code ?? '';
    run.value = null;
    failure.value = '';
}

function preview(table) {
    sql.value = `SELECT *\nFROM ${table}\nLIMIT 10;`;
    execute();
}

watch(
    () => props.request?.key,
    () => {
        if (props.request) {
            sql.value = props.request.code;
            execute();
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="sandbox">
        <SchemaPanel :tables="schema" @preview="preview" />

        <div class="workspace">
            <p class="intro">
                Write any SQL you like: SELECT, INSERT, UPDATE, even DROP TABLE. Every run starts from a fresh
                copy of the data, so you can't break anything.
            </p>
            <EditorPanel
                v-model="sql"
                :schema="schema"
                :running="running"
                label="Sandbox SQL editor"
                reset-label="Restore example"
                @run="execute"
                @reset="reset"
            />
            <p v-if="failure" class="failure" role="alert">{{ failure }}</p>
            <RunResults v-if="run" :run="run" />
            <p v-else-if="!failure" class="empty">Press Run to see the result of your query here.</p>
        </div>
    </div>
</template>

<style scoped>
.sandbox {
    display: grid;
    grid-template-columns: minmax(0, 15rem) minmax(0, 1fr);
    gap: 1.5rem;
}

.workspace {
    display: grid;
    gap: 1rem;
    align-content: start;
    min-width: 0;
}

.intro {
    color: var(--muted);
    max-width: 65ch;
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

@media (max-width: 760px) {
    .sandbox {
        grid-template-columns: minmax(0, 1fr);
    }

    .sandbox > :first-child {
        order: 2;
    }
}
</style>
