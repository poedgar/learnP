<script setup>
import { computed, ref, watch } from 'vue';
import { api } from '../../api';
import { lessonProgress } from '../../progress';
import { checkChallenge, runScript } from '../../lib/php/sandbox';
import MarkdownContent from '../MarkdownContent.vue';
import EditorPanel from '../EditorPanel.vue';
import RunResults from '../RunResults.vue';
import PhpOutput from '../PhpOutput.vue';
import SqlCheckDetails from './SqlCheckDetails.vue';
import PhpCheckDetails from './PhpCheckDetails.vue';
import Pager from '../Pager.vue';

const props = defineProps({
    lesson: { type: Object, required: true },
    /** sql or php */
    language: { type: String, required: true },
    schema: { type: Array, default: () => [] },
});

/**
 * How each language runs and checks answers. SQL runs on the server;
 * PHP runs in the browser (see resources/js/lib/php).
 */
const engines = {
    sql: {
        run: async (code) => ({ sql: await api.run(props.lesson.id, code) }),
        check: (challenge, code) => api.check(challenge.id, code),
    },
    php: {
        run: async (code) => ({ php: await runScript(code) }),
        async check(challenge, code) {
            const report = await checkChallenge(code, challenge.checks);
            const passedCount = report.checks.filter((c) => c.passed).length;
            const message = report.passed
                ? `Correct! All ${report.checks.length} checks pass.`
                : !report.loaded
                  ? 'Your code has an error, so the checks could not run yet.'
                  : `${passedCount} of ${report.checks.length} checks pass. See what's missing below.`;
            return { ...report, message };
        },
    },
};
const engine = computed(() => engines[props.language]);

const progress = lessonProgress(props.lesson.id);
const challenges = computed(() => props.lesson.challenges);

const firstUnsolved = challenges.value.findIndex((c) => !progress.challenges[c.id]?.passed);
const activeIndex = ref(firstUnsolved === -1 ? 0 : firstUnsolved);
const active = computed(() => challenges.value[activeIndex.value]);

const code = ref('');
const showHint = ref(false);
const busy = ref(null); // 'run' | 'check' | null
const run = ref(null);
const check = ref(null);
const failure = ref('');

function stateFor(challenge) {
    if (!progress.challenges[challenge.id]) {
        progress.challenges[challenge.id] = { passed: false, code: null };
    }
    return progress.challenges[challenge.id];
}

watch(
    active,
    (challenge) => {
        code.value = stateFor(challenge).code ?? challenge.starter_code ?? '';
        showHint.value = false;
        run.value = null;
        check.value = null;
        failure.value = '';
    },
    { immediate: true },
);

watch(code, (value) => (stateFor(active.value).code = value));

async function execute() {
    if (busy.value) return;
    busy.value = 'run';
    failure.value = '';
    check.value = null;
    try {
        run.value = await engine.value.run(code.value);
    } catch (error) {
        run.value = null;
        failure.value = error.message;
    } finally {
        busy.value = null;
    }
}

async function submit() {
    if (busy.value) return;
    busy.value = 'check';
    failure.value = '';
    run.value = null;
    try {
        check.value = await engine.value.check(active.value, code.value);
        if (check.value.passed) {
            stateFor(active.value).passed = true;
        }
    } catch (error) {
        check.value = null;
        failure.value = error.message;
    } finally {
        busy.value = null;
    }
}

function reset() {
    code.value = active.value.starter_code ?? '';
    run.value = null;
    check.value = null;
}

const nextUnsolved = computed(() => {
    const order = [...challenges.value.slice(activeIndex.value + 1), ...challenges.value.slice(0, activeIndex.value)];
    return order.find((c) => !progress.challenges[c.id]?.passed) ?? null;
});

const solvedCount = computed(() => challenges.value.filter((c) => progress.challenges[c.id]?.passed).length);

const challengeStates = computed(() => challenges.value.map((c) => (progress.challenges[c.id]?.passed ? 'done' : null)));

function goTo(challenge) {
    activeIndex.value = challenges.value.indexOf(challenge);
}
</script>

<template>
    <div class="challenges">
        <nav class="list" aria-label="Challenges">
            <p class="solved">{{ solvedCount }} of {{ challenges.length }} solved</p>
            <ol>
                <li v-for="(challenge, i) in challenges" :key="challenge.id">
                    <button
                        type="button"
                        class="item"
                        :class="{ active: i === activeIndex, passed: progress.challenges[challenge.id]?.passed }"
                        :aria-current="i === activeIndex ? 'step' : undefined"
                        @click="activeIndex = i"
                    >
                        <span class="marker" aria-hidden="true">
                            {{ progress.challenges[challenge.id]?.passed ? '✓' : i + 1 }}
                        </span>
                        <span>{{ challenge.title }}</span>
                        <span v-if="progress.challenges[challenge.id]?.passed" class="visually-hidden">(solved)</span>
                    </button>
                </li>
            </ol>
        </nav>

        <section class="task" :aria-labelledby="`challenge-${active.id}`">
            <Pager
                class="mobile-pager"
                :page="activeIndex + 1"
                :count="challenges.length"
                :states="challengeStates"
                label="Challenges"
                :page-label="(n) => challenges[n - 1].title"
                @update:page="activeIndex = $event - 1"
            />
            <h3 :id="`challenge-${active.id}`">{{ active.title }}</h3>
            <MarkdownContent :source="active.prompt" class="prompt" />
            <p v-if="active.order_matters" class="order-note">Row order is checked for this challenge.</p>

            <div v-if="active.hint" class="hint">
                <button type="button" class="btn btn-quiet" :aria-expanded="showHint" @click="showHint = !showHint">
                    {{ showHint ? 'Hide hint' : 'Show a hint' }}
                </button>
                <p v-if="showHint">{{ active.hint }}</p>
            </div>

            <EditorPanel
                v-model="code"
                :language="language"
                :schema="schema"
                :running="busy === 'run'"
                :label="`Code for challenge: ${active.title}`"
                reset-label="Start over"
                @run="execute"
                @reset="reset"
            >
                <template #actions>
                    <button type="button" class="btn check" :disabled="busy !== null" @click="submit">
                        {{ busy === 'check' ? 'Checking…' : 'Check answer' }}
                    </button>
                </template>
            </EditorPanel>

            <p v-if="failure" class="failure" role="alert">{{ failure }}</p>

            <template v-if="check">
                <div class="verdict" :class="check.passed ? 'good' : 'bad'" role="status">
                    <strong>{{ check.message }}</strong>
                    <button
                        v-if="check.passed && nextUnsolved"
                        type="button"
                        class="btn btn-primary"
                        @click="goTo(nextUnsolved)"
                    >
                        Next challenge
                    </button>
                    <span v-else-if="check.passed">You've solved every challenge in this lesson.</span>
                </div>

                <SqlCheckDetails v-if="language === 'sql'" :check="check" />
                <PhpCheckDetails v-else :check="check" />
            </template>

            <template v-else-if="run">
                <RunResults v-if="run.sql" :run="run.sql" />
                <PhpOutput v-else v-bind="run.php" />
            </template>
        </section>
    </div>
</template>

<style scoped>
.challenges {
    display: grid;
    grid-template-columns: minmax(0, 15rem) minmax(0, 1fr);
    gap: 2rem;
}

.solved {
    font-size: 0.9rem;
    color: var(--muted);
    margin-bottom: 0.6rem;
}

.list ol {
    display: grid;
    gap: 0.25rem;
}

.item {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    width: 100%;
    text-align: left;
    padding: 0.5rem 0.6rem;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 0.97rem;
}

.item:hover {
    background: rgb(24 35 63 / 0.06);
}

.item.active {
    background: var(--surface);
    box-shadow: inset 3px 0 0 var(--highlight);
    font-weight: 700;
}

.marker {
    flex: none;
    display: grid;
    place-items: center;
    width: 1.6rem;
    height: 1.6rem;
    border-radius: 50%;
    border: 1px solid var(--line);
    font-size: 0.8rem;
    font-weight: 700;
    background: var(--surface);
}

.item.passed .marker {
    background: var(--ok);
    border-color: var(--ok);
    color: #fff;
}

.task {
    display: grid;
    gap: 1rem;
    align-content: start;
    min-width: 0;
}

.task h3 {
    font-size: 1.6rem;
    font-weight: 800;
}

.prompt {
    font-size: 1.08rem;
    max-width: 65ch;
}

.order-note {
    font-size: 0.88rem;
    color: var(--muted);
    margin-top: -0.5rem;
}

.hint p {
    margin-top: 0.35rem;
    padding: 0.7rem 1rem;
    border-radius: var(--radius);
    background: var(--highlight-soft);
    max-width: 65ch;
}

.hint .btn {
    padding-left: 0.4rem;
    padding-right: 0.4rem;
}

.check {
    border-color: var(--ink);
}

.verdict {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem 1rem;
    padding: 0.85rem 1rem;
    border-radius: var(--radius);
    border-left: 4px solid;
}

.verdict.good {
    background: var(--ok-soft);
    border-color: var(--ok);
}

.verdict.bad {
    background: var(--bad-soft);
    border-color: var(--bad);
}

.failure {
    color: var(--bad);
    font-weight: 700;
}

.mobile-pager {
    display: none;
}

@media (max-width: 760px) {
    .challenges {
        grid-template-columns: minmax(0, 1fr);
        gap: 1.25rem;
    }

    .list {
        display: none;
    }

    .mobile-pager {
        display: flex;
    }
}
</style>
