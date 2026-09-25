<script setup>
defineProps({
    tables: { type: Array, required: true },
});

const emit = defineEmits(['preview']);
</script>

<template>
    <aside class="schema" aria-labelledby="schema-heading">
        <h3 id="schema-heading">Tables</h3>
        <section v-for="table in tables" :key="table.name" class="table">
            <div class="table-head">
                <code class="table-name">{{ table.name }}</code>
                <span class="count">{{ table.row_count }} rows</span>
            </div>
            <ul>
                <li v-for="column in table.columns" :key="column.name">
                    <code>{{ column.name }}</code>
                    <span class="type">
                        {{ column.type.toLowerCase() }}<template v-if="column.primary_key">, key</template>
                    </span>
                </li>
            </ul>
            <button type="button" class="btn btn-quiet preview" @click="emit('preview', table.name)">
                Preview {{ table.name }}
            </button>
        </section>
    </aside>
</template>

<style scoped>
.schema {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 1rem;
    align-self: start;
}

h3 {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.table + .table {
    margin-top: 1.25rem;
}

.table-head {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding-bottom: 0.35rem;
    border-bottom: 2px solid var(--ink);
}

.table-name {
    font-weight: 600;
}

.count {
    font-size: 0.8rem;
    color: var(--muted);
}

ul {
    margin-top: 0.35rem;
}

li {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.2rem 0;
    font-size: 0.88rem;
}

li code {
    font-size: 0.82rem;
}

.type {
    color: var(--muted);
    font-size: 0.8rem;
}

.preview {
    margin-top: 0.5rem;
    padding-left: 0.4rem;
    padding-right: 0.4rem;
    font-size: 0.85rem;
}
</style>
