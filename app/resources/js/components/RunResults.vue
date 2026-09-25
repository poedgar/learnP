<script setup>
import { computed } from 'vue';
import ResultTable from './ResultTable.vue';

const props = defineProps({
    /** Response from the run endpoint: { results, error }. */
    run: { type: Object, required: true },
});

const multiple = computed(() => props.run.results.length + (props.run.error ? 1 : 0) > 1);

function plural(count, word) {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}

function rowsCaption(result) {
    const count = result.truncated ? `First ${result.rows.length} rows` : plural(result.rows.length, 'row');
    return `${count} in ${result.duration_ms} ms`;
}

function commandCaption(result) {
    const verb = result.sql.trim().split(/\s+/)[0].toUpperCase();
    return result.affected_rows === null
        ? `${verb} ran successfully.`
        : `${verb} changed ${plural(result.affected_rows, 'row')}.`;
}

function preview(sql) {
    const line = sql.replace(/\s+/g, ' ').trim();
    return line.length > 70 ? `${line.slice(0, 70)}…` : line;
}
</script>

<template>
    <div class="run-results" aria-live="polite">
        <section v-for="(result, index) in run.results" :key="index" class="result">
            <p v-if="multiple" class="statement"><span class="number">{{ index + 1 }}</span>{{ preview(result.sql) }}</p>
            <template v-if="result.type === 'rows'">
                <ResultTable :columns="result.columns" :rows="result.rows" :caption="rowsCaption(result)" />
                <p class="meta">
                    {{ rowsCaption(result) }}
                    <template v-if="result.truncated">. Add a LIMIT to see specific rows.</template>
                </p>
            </template>
            <p v-else class="command">{{ commandCaption(result) }}</p>
        </section>

        <div v-if="run.error" class="error" role="alert">
            <strong>
                {{ run.error.statement !== null && multiple ? `Statement ${run.error.statement + 1} failed` : 'Your SQL could not run' }}
            </strong>
            <p>{{ run.error.message }}</p>
        </div>

        <p v-if="!run.error && run.results.length === 0" class="meta">Nothing to show.</p>

        <p v-if="run.results.some((r) => r.type === 'command')" class="reset-note">
            Changes only last for this run. Every run starts from a fresh copy of the data.
        </p>
    </div>
</template>

<style scoped>
.run-results {
    display: grid;
    gap: 1.1rem;
}

.statement {
    display: flex;
    gap: 0.6rem;
    align-items: baseline;
    font-family: var(--font-code);
    font-size: 0.8rem;
    color: var(--muted);
    margin-bottom: 0.45rem;
}

.number {
    font-weight: 600;
    color: var(--ink);
}

.meta {
    margin-top: 0.4rem;
    font-size: 0.85rem;
    color: var(--muted);
}

.command {
    padding: 0.65rem 0.9rem;
    border-radius: var(--radius);
    background: var(--ok-soft);
    color: #1f6a42;
    font-weight: 700;
    font-size: 0.93rem;
}

.error {
    padding: 0.8rem 1rem;
    border-radius: var(--radius);
    background: var(--bad-soft);
    border-left: 4px solid var(--bad);
    color: #7d1f29;
}

.error p {
    font-family: var(--font-code);
    font-size: 0.88rem;
    margin-top: 0.2rem;
}

.reset-note {
    font-size: 0.85rem;
    color: var(--muted);
}
</style>
