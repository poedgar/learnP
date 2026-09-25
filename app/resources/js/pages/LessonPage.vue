<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../api';
import { lessonProgress, lessonSummary } from '../progress';
import TheoryStep from '../components/lesson/TheoryStep.vue';
import SqlSandboxStep from '../components/lesson/SqlSandboxStep.vue';
import PhpSandboxStep from '../components/lesson/PhpSandboxStep.vue';
import QuizStep from '../components/lesson/QuizStep.vue';
import ChallengesStep from '../components/lesson/ChallengesStep.vue';
import LoadState from '../components/LoadState.vue';

const props = defineProps({
    course: { type: String, required: true },
    lesson: { type: String, required: true },
    step: { type: String, default: '' },
});

const router = useRouter();
const data = ref(null);
const error = ref(null);
const visited = reactive(new Set());
const sandboxRequest = ref(null);

const current = computed(() => props.step || 'learn');
const language = computed(() => data.value?.data.course.language);
const summary = computed(() => (data.value ? lessonSummary(data.value.data) : null));

const steps = computed(() => {
    const s = summary.value;
    return [
        { key: 'learn', label: 'Learn', status: s?.read ? 'Read' : '' },
        { key: 'sandbox', label: 'Sandbox', status: '' },
        { key: 'quiz', label: 'Quiz', status: s?.quizAnswered ? `${s.quizCorrect}/${s.quizTotal}` : '' },
        { key: 'challenges', label: 'Challenges', status: s?.challengesPassed ? `${s.challengesPassed}/${s.challengeTotal}` : '' },
    ];
});

async function load() {
    data.value = null;
    error.value = null;
    visited.clear();
    try {
        data.value = await api.lesson(props.course, props.lesson);
        document.title = `${data.value.data.title}: ${data.value.data.course.title} | learnP`;
    } catch (e) {
        error.value = e;
    }
}

watch(() => [props.course, props.lesson], load, { immediate: true });
watch(current, (step) => visited.add(step), { immediate: true });

function goTo(step) {
    router.push({ name: 'lesson', params: { course: props.course, lesson: props.lesson, step: step === 'learn' ? '' : step } });
}

function tryInSandbox(code) {
    sandboxRequest.value = { code, key: Date.now() };
    goTo('sandbox');
}

function finishReading() {
    lessonProgress(data.value.data.id).read = true;
    goTo('sandbox');
}
</script>

<template>
    <LoadState :error="error" :loading="!data" not-found="We couldn't find that lesson.">
        <div v-if="data" class="lesson">
            <header class="lesson-head">
                <p class="crumbs">
                    <RouterLink :to="{ name: 'course', params: { course } }">{{ data.data.course.title }}</RouterLink>
                    <span aria-hidden="true">/</span>
                    Lesson {{ data.data.position }}
                </p>
                <h1>{{ data.data.title }}</h1>
                <p class="summary">{{ data.data.summary }}</p>

                <nav class="steps" aria-label="Lesson steps">
                    <RouterLink
                        v-for="(s, i) in steps"
                        :key="s.key"
                        :to="{ name: 'lesson', params: { course, lesson, step: s.key === 'learn' ? '' : s.key } }"
                        class="step"
                        :class="{ active: current === s.key }"
                        :aria-current="current === s.key ? 'step' : undefined"
                    >
                        <span class="step-number">{{ i + 1 }}</span>
                        <span class="step-label">{{ s.label }}</span>
                        <span v-if="s.status" class="step-status">{{ s.status }}</span>
                    </RouterLink>
                </nav>
            </header>

            <div class="step-body">
                <TheoryStep
                    v-if="visited.has('learn')"
                    v-show="current === 'learn'"
                    :lesson="data.data"
                    :language="language"
                    @try="tryInSandbox"
                    @finish="finishReading"
                    @skip="goTo('quiz')"
                />

                <template v-if="visited.has('sandbox')">
                    <SqlSandboxStep
                        v-if="language === 'sql'"
                        v-show="current === 'sandbox'"
                        :lesson="data.data"
                        :schema="data.schema"
                        :request="sandboxRequest"
                    />
                    <PhpSandboxStep
                        v-else
                        v-show="current === 'sandbox'"
                        :lesson="data.data"
                        :request="sandboxRequest"
                    />
                </template>

                <QuizStep
                    v-if="visited.has('quiz')"
                    v-show="current === 'quiz'"
                    :lesson="data.data"
                    :language="language"
                    @next="goTo('challenges')"
                    @try="tryInSandbox"
                />

                <ChallengesStep
                    v-if="visited.has('challenges')"
                    v-show="current === 'challenges'"
                    :lesson="data.data"
                    :language="language"
                    :schema="data.schema"
                />
            </div>

            <nav v-if="data.navigation.previous || data.navigation.next" class="lesson-nav" aria-label="Other lessons">
                <RouterLink
                    v-if="data.navigation.previous"
                    :to="{ name: 'lesson', params: { course, lesson: data.navigation.previous.slug } }"
                >
                    Previous: {{ data.navigation.previous.title }}
                </RouterLink>
                <RouterLink
                    v-if="data.navigation.next"
                    class="next"
                    :to="{ name: 'lesson', params: { course, lesson: data.navigation.next.slug } }"
                >
                    Next: {{ data.navigation.next.title }}
                </RouterLink>
            </nav>
        </div>
    </LoadState>
</template>

<style scoped>
.lesson {
    padding-bottom: 4rem;
}

.lesson-head {
    padding: 2.5rem 0 0;
}

.crumbs {
    display: flex;
    gap: 0.5rem;
    font-size: 0.95rem;
    color: var(--muted);
}

.crumbs a {
    color: var(--ink);
    font-weight: 700;
    text-decoration: underline;
    text-decoration-color: var(--highlight);
    text-decoration-thickness: 2px;
    text-underline-offset: 3px;
}

h1 {
    margin-top: 0.5rem;
    font-size: clamp(2.2rem, 5vw, 3.4rem);
    font-weight: 800;
}

.summary {
    margin-top: 0.6rem;
    max-width: 60ch;
    font-size: 1.12rem;
    color: var(--ink-soft);
}

.steps {
    display: flex;
    gap: 0.25rem;
    margin-top: 2rem;
    border-bottom: 1px solid var(--line);
    overflow-x: auto;
}

.step {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.7rem 1rem 0.8rem;
    color: var(--muted);
    font-weight: 700;
    white-space: nowrap;
    border-bottom: 4px solid transparent;
    margin-bottom: -1px;
}

.step:hover {
    color: var(--ink);
}

.step.active {
    color: var(--ink);
    border-bottom-color: var(--highlight);
}

.step-number {
    display: grid;
    place-items: center;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    border: 1px solid currentColor;
    font-size: 0.78rem;
}

.step.active .step-number {
    background: var(--ink);
    border-color: var(--ink);
    color: #fff;
}

.step-status {
    font-size: 0.78rem;
    font-weight: 400;
    padding: 0.05rem 0.45rem;
    border-radius: 999px;
    background: var(--ok-soft);
    color: #1f6a42;
    font-variant-numeric: tabular-nums;
}

@media (max-width: 640px) {
    .steps {
        gap: 0;
    }

    .step {
        gap: 0.4rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        font-size: 0.95rem;
    }

    .step-status {
        display: none;
    }
}

@media (max-width: 420px) {
    .step-number {
        display: none;
    }
}

.step-body {
    padding-top: 2rem;
}

.lesson-nav {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 4rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--line);
    font-weight: 700;
}

.lesson-nav .next {
    margin-left: auto;
}
</style>
