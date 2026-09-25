<script setup>
import { computed } from 'vue';

const props = defineProps({
    /** Current page, starting at 1. */
    page: { type: Number, required: true },
    count: { type: Number, required: true },
    /** Accessible name for the navigation, e.g. "Quiz questions". */
    label: { type: String, required: true },
    /** Optional state per page ('done', 'correct' or 'wrong'), shown on the page buttons. */
    states: { type: Array, default: () => [] },
    /** Accessible name for one page, e.g. (n) => `Question ${n}`. */
    pageLabel: { type: Function, default: (n) => `Page ${n}` },
    /** Hide Previous and Next, e.g. when the page supplies its own. */
    numbersOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['update:page']);

/** Page numbers to show, with null for a gap: 1 … 4 5 6 … 20 */
const items = computed(() => {
    const { page, count } = props;
    if (count <= 9) {
        return Array.from({ length: count }, (_, i) => i + 1);
    }
    const wanted = new Set([1, count, page - 1, page, page + 1].filter((n) => n >= 1 && n <= count));
    const sorted = [...wanted].sort((a, b) => a - b);
    return sorted.flatMap((n, i) => (i > 0 && n - sorted[i - 1] > 1 ? [null, n] : [n]));
});

function go(page) {
    if (page >= 1 && page <= props.count && page !== props.page) {
        emit('update:page', page);
    }
}

const stateLabels = { done: 'done', correct: 'answered correctly', wrong: 'answered incorrectly' };
</script>

<template>
    <nav v-if="count > 1" class="pager" :aria-label="label">
        <button v-if="!numbersOnly" type="button" class="step prev" :disabled="page === 1" @click="go(page - 1)">
            Previous
        </button>
        <ol>
            <li v-for="(n, i) in items" :key="n ?? `gap-${i}`">
                <span v-if="n === null" class="gap" aria-hidden="true">…</span>
                <button
                    v-else
                    type="button"
                    class="number"
                    :class="[states[n - 1], { current: n === page }]"
                    :aria-current="n === page ? 'page' : undefined"
                    :aria-label="`${pageLabel(n)}${states[n - 1] ? `, ${stateLabels[states[n - 1]]}` : ''}`"
                    @click="go(n)"
                >
                    {{ n }}
                </button>
            </li>
        </ol>
        <button v-if="!numbersOnly" type="button" class="step next" :disabled="page === count" @click="go(page + 1)">
            Next
        </button>
    </nav>
</template>

<style scoped>
.pager {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}

ol {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
}

.number,
.step,
.gap {
    display: inline-grid;
    place-items: center;
    min-width: 2.1rem;
    height: 2.1rem;
    border-radius: var(--radius);
    font-size: 0.9rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.number {
    border: 1px solid var(--line);
    background: var(--surface);
    color: var(--ink);
    cursor: pointer;
}

.number:hover {
    border-color: var(--ink);
}

.number.done,
.number.correct {
    background: var(--ok-soft);
    border-color: var(--ok);
    color: #1f6a42;
}

.number.wrong {
    background: var(--bad-soft);
    border-color: var(--bad);
    color: #7d1f29;
}

.number.current {
    background: var(--ink);
    border-color: var(--ink);
    color: #fff;
    box-shadow: 0 0 0 2px var(--paper), 0 0 0 4px var(--highlight);
}

.step {
    padding: 0 0.8rem;
    border: 1px solid transparent;
    color: var(--ink-soft);
    cursor: pointer;
}

.step:hover:not(:disabled) {
    background: rgb(24 35 63 / 0.06);
    color: var(--ink);
}

.step:disabled {
    opacity: 0.4;
    cursor: default;
}

.gap {
    min-width: 1.2rem;
    color: var(--muted);
}
</style>
