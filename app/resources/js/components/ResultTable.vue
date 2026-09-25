<script setup>
import { computed, ref, watch } from 'vue';
import Pager from './Pager.vue';

const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, required: true },
    caption: { type: String, default: '' },
    pageSize: { type: Number, default: 10 },
});

const page = ref(1);
const pageCount = computed(() => Math.max(1, Math.ceil(props.rows.length / props.pageSize)));
const first = computed(() => (page.value - 1) * props.pageSize);
const visibleRows = computed(() => props.rows.slice(first.value, first.value + props.pageSize));

// A new result starts on its first page.
watch(() => props.rows, () => (page.value = 1));

function isNumber(value) {
    return typeof value === 'number';
}
</script>

<template>
    <div class="result-table">
        <div class="scroller" tabindex="0" role="region" :aria-label="caption || 'Query result'">
            <table>
                <caption v-if="caption" class="visually-hidden">{{ caption }}</caption>
                <thead>
                    <tr>
                        <th v-for="(column, i) in columns" :key="i" scope="col">{{ column }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, r) in visibleRows" :key="first + r">
                        <td v-for="(value, c) in row" :key="c" :class="{ numeric: isNumber(value), null: value === null }">
                            {{ value === null ? 'NULL' : value }}
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td :colspan="Math.max(columns.length, 1)" class="empty">No rows matched.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="pageCount > 1" class="table-pager">
            <p class="range">Rows {{ first + 1 }}–{{ first + visibleRows.length }} of {{ rows.length }}</p>
            <Pager v-model:page="page" :count="pageCount" :label="`Pages of ${caption || 'the result'}`" />
        </div>
    </div>
</template>

<style scoped>
.result-table {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    overflow: hidden;
}

.scroller {
    overflow-x: auto;
}

.table-pager {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem 1rem;
    padding: 0.5rem 0.75rem;
    border-top: 1px solid var(--line);
}

.range {
    font-size: 0.85rem;
    color: var(--muted);
    font-variant-numeric: tabular-nums;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.92rem;
}

th {
    background: var(--surface);
    font-family: var(--font-code);
    font-size: 0.78rem;
    font-weight: 600;
    text-align: left;
    padding: 0.55rem 0.85rem;
    border-bottom: 2px solid var(--ink);
    white-space: nowrap;
}

td {
    padding: 0.42rem 0.85rem;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
}

tbody tr:last-child td {
    border-bottom: none;
}

tbody tr:hover td {
    background: var(--highlight-soft);
}

.numeric {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.null {
    color: var(--muted);
    font-style: italic;
}

.empty {
    color: var(--muted);
    text-align: center;
    padding: 1.25rem;
}
</style>
