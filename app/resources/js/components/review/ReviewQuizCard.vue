<script setup>
import { ref } from 'vue';
import { api } from '../../api';
import { highlightCode } from '../../lib/highlight';
import MarkdownContent from '../MarkdownContent.vue';
import GradeButtons from './GradeButtons.vue';

const props = defineProps({
    question: { type: Object, required: true },
    language: { type: String, required: true },
    card: { type: Object, default: undefined },
});

const emit = defineEmits(['grade']);

const letters = ['A', 'B', 'C', 'D', 'E', 'F'];

// A new order each time, so the answer can't be remembered by its position.
const order = props.question.options.map((_, i) => i);
for (let i = order.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [order[i], order[j]] = [order[j], order[i]];
}

const chosen = ref(null);
const answer = ref(null);
const error = ref('');

/** Checked on the server, but not saved to lesson progress. */
async function choose(option) {
    if (chosen.value !== null) return;
    chosen.value = option;
    error.value = '';
    try {
        answer.value = await api.answer(props.question.id, option);
    } catch (e) {
        chosen.value = null;
        error.value = e.message;
    }
}

function optionState(option) {
    if (!answer.value) return '';
    if (option === answer.value.correct_option) return 'is-correct';
    if (option === chosen.value) return 'is-wrong';
    return 'is-dimmed';
}
</script>

<template>
    <section class="card">
        <h2 class="question"><MarkdownContent :source="question.question" inline /></h2>
        <pre v-if="question.code" class="code-block"><code v-html="highlightCode(question.code, language)"></code></pre>

        <div class="options" role="group" aria-label="Answers">
            <button
                v-for="(option, position) in order"
                :key="option"
                type="button"
                class="option"
                :class="optionState(option)"
                :disabled="chosen !== null"
                :aria-pressed="chosen === option"
                @click="choose(option)"
            >
                <span class="letter">{{ letters[position] }}</span>
                <MarkdownContent :source="question.options[option]" inline />
            </button>
        </div>

        <p v-if="error" class="error" role="alert">{{ error }}</p>

        <template v-if="answer">
            <div class="feedback" :class="answer.correct ? 'good' : 'bad'" role="status">
                <strong>{{ answer.correct ? 'Correct.' : 'Not quite.' }}</strong>
                <MarkdownContent :source="answer.explanation" inline />
            </div>
            <GradeButtons v-if="answer.correct" :card="card" :grades="['hard', 'good', 'easy']" @grade="emit('grade', $event)" />
            <div v-else>
                <button type="button" class="btn btn-primary" @click="emit('grade', 'again')">Continue (see it again soon)</button>
            </div>
        </template>
    </section>
</template>

<style scoped>
.card {
    display: grid;
    gap: 1rem;
    max-width: 46rem;
}

.question {
    font-family: var(--font-body);
    font-size: 1.2rem;
    font-weight: 700;
    line-height: 1.45;
    letter-spacing: 0;
}

.options {
    display: grid;
    gap: 0.5rem;
}

.option {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    width: 100%;
    text-align: left;
    padding: 0.65rem 0.85rem;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 0.98rem;
}

.option:not(:disabled):hover {
    border-color: var(--ink);
    background: var(--highlight-soft);
}

.option:disabled {
    cursor: default;
}

.letter {
    flex: none;
    display: grid;
    place-items: center;
    width: 1.7rem;
    height: 1.7rem;
    border-radius: 50%;
    border: 1px solid var(--line);
    font-size: 0.8rem;
    font-weight: 700;
}

.option.is-correct {
    border-color: var(--ok);
    background: var(--ok-soft);
}

.option.is-correct .letter {
    background: var(--ok);
    border-color: var(--ok);
    color: #fff;
}

.option.is-wrong {
    border-color: var(--bad);
    background: var(--bad-soft);
}

.option.is-wrong .letter {
    background: var(--bad);
    border-color: var(--bad);
    color: #fff;
}

.option.is-dimmed {
    opacity: 0.55;
}

.feedback {
    padding: 0.75rem 1rem;
    border-radius: var(--radius);
    border-left: 4px solid;
}

.feedback strong {
    margin-right: 0.35rem;
}

.feedback.good {
    background: var(--ok-soft);
    border-color: var(--ok);
}

.feedback.bad {
    background: var(--bad-soft);
    border-color: var(--bad);
}

.error {
    color: var(--bad);
}
</style>
