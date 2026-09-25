<script setup>
import { computed } from 'vue';
import ResultTable from '../ResultTable.vue';
import RunResults from '../RunResults.vue';

const props = defineProps({
    /** Response from the SQL challenge check endpoint. */
    check: { type: Object, required: true },
});

const learnerRows = computed(() => props.check.run.results.filter((r) => r.type === 'rows').at(-1) ?? null);
</script>

<template>
    <div v-if="!check.passed" class="compare">
        <div>
            <h4>Your result</h4>
            <ResultTable v-if="learnerRows" :columns="learnerRows.columns" :rows="learnerRows.rows" caption="Your result" />
            <RunResults v-else :run="check.run" />
        </div>
        <div>
            <h4>Expected result</h4>
            <ResultTable :columns="check.expected.columns" :rows="check.expected.rows" caption="Expected result" />
        </div>
    </div>
    <ResultTable v-else-if="learnerRows" :columns="learnerRows.columns" :rows="learnerRows.rows" caption="Your result" />
</template>

<style scoped>
.compare {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
    gap: 1rem;
}

.compare h4 {
    font-weight: 700;
    font-size: 0.95rem;
    margin-bottom: 0.4rem;
}
</style>
