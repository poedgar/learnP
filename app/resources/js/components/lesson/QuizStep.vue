<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { api } from '../../api';
import { lessonProgress } from '../../progress';
import MarkdownContent from '../MarkdownContent.vue';
import { highlightCode } from '../../lib/highlight';
import { scrollToTop } from '../../lib/scroll';
import Pager from '../Pager.vue';

const props = defineProps({
    lesson: { type: Object, required: true },
    /** Language of the code samples in questions. */
    language: { type: String, required: true },
});

const emit = defineEmits(['next', 'try']);

const progress = lessonProgress(props.lesson.id);
const pending = reactive({});
const errors = reactive({});
const letters = ['A', 'B', 'C', 'D', 'E', 'F'];

const answered = computed(() => props.lesson.quiz_questions.filter((q) => progress.quiz[q.id]).length);
const correct = computed(() => props.lesson.quiz_questions.filter((q) => progress.quiz[q.id]?.correct).length);
const total = computed(() => props.lesson.quiz_questions.length);
const finished = computed(() => answered.value === total.value);

// One question per page. The first unanswered question is where a learner picks up again.
const firstOpen = props.lesson.quiz_questions.findIndex((q) => !progress.quiz[q.id]);
const page = ref(firstOpen === -1 ? 1 : firstOpen + 1);
const root = ref(null);
const question = computed(() => props.lesson.quiz_questions[page.value - 1]);
const isLast = computed(() => page.value === total.value);
const states = computed(() =>
    props.lesson.quiz_questions.map((q) => (progress.quiz[q.id] ? (progress.quiz[q.id].correct ? 'correct' : 'wrong') : null)),
);

watch(page, async () => {
    await nextTick();
    scrollToTop(root.value);
});

async function choose(question, option) {
    if (progress.quiz[question.id] || pending[question.id]) return;
    pending[question.id] = true;
    errors[question.id] = '';
    try {
        const result = await api.answer(question.id, option);
        progress.quiz[question.id] = { option, ...result };
    } catch (error) {
        errors[question.id] = error.message;
    } finally {
        pending[question.id] = false;
    }
}

function optionState(question, index) {
    const answer = progress.quiz[question.id];
    if (!answer) return '';
    if (index === answer.correct_option) return 'is-correct';
    if (index === answer.option) return 'is-wrong';
    return 'is-dimmed';
}

function retake() {
    progress.quiz = {};
    page.value = 1;
}
</script>

<template>
    <div ref="root" class="quiz">
        <header class="quiz-head">
            <p>Pick one answer per question. You'll see straight away whether you were right, and why.</p>
            <p class="score" aria-live="polite">
                <strong>{{ correct }}</strong> of {{ total }} correct<template v-if="answered < total">, {{ total - answered }} to go</template>
            </p>
        </header>

        <Pager
            v-model:page="page"
            class="pager"
            :count="total"
            :states="states"
            label="Quiz questions"
            :page-label="(n) => `Question ${n}`"
            numbers-only
        />

        <section :key="question.id" class="question" :aria-labelledby="`question-${question.id}`">
            <h3 :id="`question-${question.id}`">
                <span class="q-number">{{ page }}.</span>
                <MarkdownContent :source="question.question" inline />
            </h3>
            <!-- The sandbox link appears once answered, so running the code can't give the answer away. -->
            <div v-if="question.code" class="question-code" :class="{ 'try-block': progress.quiz[question.id] }">
                <pre class="code-block"><code v-html="highlightCode(question.code, language)"></code></pre>
                <button
                    v-if="progress.quiz[question.id]"
                    type="button"
                    class="try-button"
                    @click="emit('try', question.code)"
                >
                    Try it in the sandbox
                </button>
            </div>

            <div class="options" role="group" :aria-label="`Answers for question ${page}`">
                <button
                    v-for="(option, i) in question.options"
                    :key="i"
                    type="button"
                    class="option"
                    :class="optionState(question, i)"
                    :disabled="Boolean(progress.quiz[question.id]) || pending[question.id]"
                    :aria-pressed="progress.quiz[question.id]?.option === i"
                    @click="choose(question, i)"
                >
                    <span class="letter">{{ letters[i] }}</span>
                    <MarkdownContent :source="option" inline />
                </button>
            </div>

            <p v-if="errors[question.id]" class="error" role="alert">{{ errors[question.id] }}</p>

            <div
                v-if="progress.quiz[question.id]"
                class="feedback"
                :class="progress.quiz[question.id].correct ? 'good' : 'bad'"
                role="status"
            >
                <strong>{{ progress.quiz[question.id].correct ? 'Correct.' : 'Not quite.' }}</strong>
                <MarkdownContent :source="progress.quiz[question.id].explanation" inline />
            </div>
        </section>

        <footer class="page-nav">
            <button v-if="page > 1" type="button" class="btn" @click="page--">Previous question</button>
            <button
                v-if="!isLast"
                type="button"
                class="btn next"
                :class="{ 'btn-primary': progress.quiz[question.id] }"
                @click="page++"
            >
                {{ progress.quiz[question.id] ? 'Next question' : 'Skip for now' }}
            </button>
        </footer>

        <div v-if="finished && isLast" class="done">
            <p>
                You got <strong>{{ correct }} of {{ total }}</strong> right.
                {{ correct === total ? 'Nicely done.' : 'Red numbers above mark the ones to review. Try again whenever you like.' }}
            </p>
            <div class="done-actions">
                <button type="button" class="btn btn-primary" @click="emit('next')">Go to the challenges</button>
                <button type="button" class="btn" @click="retake">Retake the quiz</button>
            </div>
        </div>
        <p v-else-if="isLast && !finished" class="unanswered">
            {{ total - answered }} {{ total - answered === 1 ? 'question is' : 'questions are' }} still unanswered. Use the
            numbers above to go back to {{ total - answered === 1 ? 'it' : 'them' }}.
        </p>
    </div>
</template>

<style scoped>
.quiz {
    max-width: 46rem;
}

.quiz-head {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.5rem 2rem;
    color: var(--muted);
    margin-bottom: 1.5rem;
}

.score {
    color: var(--ink);
    font-variant-numeric: tabular-nums;
}

.quiz {
    scroll-margin-top: 1rem;
}

.pager {
    margin-bottom: 1.75rem;
}

.page-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 2rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--line);
}

.page-nav .next {
    margin-left: auto;
}

.unanswered {
    margin-top: 1.5rem;
    color: var(--muted);
}

.question {
    min-width: 0;
}

.question h3 {
    display: flex;
    gap: 0.5rem;
    font-family: var(--font-body);
    font-size: 1.12rem;
    font-weight: 700;
    line-height: 1.45;
    letter-spacing: 0;
}

.q-number {
    color: var(--muted);
    font-variant-numeric: tabular-nums;
}

.question-code {
    margin-top: 0.75rem;
}

.options {
    display: grid;
    gap: 0.5rem;
    margin-top: 0.9rem;
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
    margin-top: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: var(--radius);
    border-left: 4px solid;
    font-size: 0.97rem;
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
    margin-top: 0.5rem;
    color: var(--bad);
}

.done {
    margin-top: 1.5rem;
    padding: 1.25rem;
    border-radius: var(--radius-lg);
    background: var(--surface);
    border: 1px solid var(--line);
}

.done-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.9rem;
}
</style>
