<script setup>
import { ref, watch } from 'vue';
import { api } from '../api';
import { lessonSummary } from '../progress';
import LoadState from '../components/LoadState.vue';

const props = defineProps({
    course: { type: String, required: true },
});

const data = ref(null);
const error = ref(null);

watch(
    () => props.course,
    async (slug) => {
        data.value = null;
        error.value = null;
        try {
            data.value = await api.course(slug);
            document.title = `${data.value.title} course | learnP`;
        } catch (e) {
            error.value = e;
        }
    },
    { immediate: true },
);

function actionLabel(summary) {
    if (summary.complete) return 'Review lesson';
    return summary.started ? 'Continue lesson' : 'Start lesson';
}

function plural(count, word) {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}
</script>

<template>
    <LoadState :error="error" :loading="!data" not-found="We couldn't find that course.">
        <div v-if="data" class="course">
            <header>
                <p class="crumbs"><RouterLink to="/">All courses</RouterLink></p>
                <h1>{{ data.title }}</h1>
                <p class="description">{{ data.description }}</p>
            </header>

            <ol class="lessons">
                <li v-for="lesson in data.lessons" :key="lesson.id" class="lesson">
                    <span class="number" aria-hidden="true">{{ lesson.position }}</span>
                    <div class="body">
                        <h2>
                            <RouterLink :to="{ name: 'lesson', params: { course: data.slug, lesson: lesson.slug } }">
                                {{ lesson.title }}
                            </RouterLink>
                        </h2>
                        <p>{{ lesson.summary }}</p>
                        <p class="contents">
                            Reading, a live sandbox, {{ plural(lesson.quiz_questions_count, 'quiz question') }} and
                            {{ plural(lesson.challenges_count, 'challenge') }}.
                        </p>
                        <p v-if="lessonSummary(lesson).started" class="progress">
                            <template v-if="lessonSummary(lesson).complete">Completed.</template>
                            <template v-else>
                                Quiz {{ lessonSummary(lesson).quizCorrect }}/{{ lessonSummary(lesson).quizTotal }} correct,
                                challenges {{ lessonSummary(lesson).challengesPassed }}/{{ lessonSummary(lesson).challengeTotal }} solved.
                            </template>
                        </p>
                    </div>
                    <RouterLink
                        class="btn btn-primary start"
                        :to="{ name: 'lesson', params: { course: data.slug, lesson: lesson.slug } }"
                    >
                        {{ actionLabel(lessonSummary(lesson)) }}
                    </RouterLink>
                </li>
            </ol>

            <p class="more">More lessons are on the way.</p>
        </div>
    </LoadState>
</template>

<style scoped>
.course {
    padding: 2.5rem 0 5rem;
}

.crumbs a {
    font-weight: 700;
    text-decoration: underline;
    text-decoration-color: var(--highlight);
    text-decoration-thickness: 2px;
    text-underline-offset: 3px;
}

h1 {
    margin-top: 0.5rem;
    font-size: clamp(3rem, 9vw, 6rem);
    font-weight: 800;
    letter-spacing: -0.03em;
}

.description {
    max-width: 55ch;
    font-size: 1.2rem;
    color: var(--ink-soft);
}

.lessons {
    margin-top: 3rem;
    border-top: 2px solid var(--ink);
}

.lesson {
    display: grid;
    grid-template-columns: 3.5rem minmax(0, 1fr) auto;
    gap: 1.25rem;
    align-items: start;
    padding: 1.75rem 0;
    border-bottom: 1px solid var(--line);
}

.number {
    font-family: var(--font-display);
    font-size: 2.6rem;
    font-weight: 800;
    line-height: 1;
    color: var(--highlight);
    -webkit-text-stroke: 1px var(--ink);
}

.body h2 {
    font-size: 1.6rem;
    font-weight: 800;
}

.body p {
    margin-top: 0.4rem;
    max-width: 60ch;
}

.contents {
    color: var(--muted);
    font-size: 0.95rem;
}

.progress {
    color: #1f6a42;
    font-size: 0.95rem;
    font-weight: 700;
}

.start {
    align-self: center;
}

.more {
    margin-top: 1.5rem;
    color: var(--muted);
}

@media (max-width: 640px) {
    .lesson {
        grid-template-columns: 2.5rem minmax(0, 1fr);
    }

    .start {
        grid-column: 2;
        justify-self: start;
    }
}
</style>
