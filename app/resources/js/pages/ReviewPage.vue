<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { api } from '../api';
import { allProgress } from '../progress';
import { describeDue, dueItems, gradeCard, nextDue, passedItems, reviewCards } from '../review';
import LoadState from '../components/LoadState.vue';
import ReviewQuizCard from '../components/review/ReviewQuizCard.vue';
import ReviewChallengeCard from '../components/review/ReviewChallengeCard.vue';

/** How many not-yet-due items "Practise anyway" brings forward. */
const PRACTICE_SIZE = 10;

const now = ref(Date.now());
let clock;
onMounted(() => {
    document.title = 'Review · learnP';
    clock = setInterval(() => (now.value = Date.now()), 60_000);
});
onUnmounted(() => clearInterval(clock));

const items = computed(() => passedItems(allProgress));
const data = ref(null);
const error = ref(null);

async function load() {
    error.value = null;
    if (!items.value.length) return;
    try {
        data.value = await api.reviewItems(
            items.value.filter((i) => i.kind === 'quiz').map((i) => i.id),
            items.value.filter((i) => i.kind === 'challenge').map((i) => i.id),
        );
    } catch (e) {
        error.value = e;
    }
}

watch(() => items.value.map((i) => i.key).join(), load, { immediate: true });

// Items whose content no longer exists on the server (a lesson was rewritten) are left out.
const content = computed(() => {
    const byKey = new Map();
    if (!data.value) return byKey;
    const lessons = new Map(data.value.lessons.map((lesson) => [lesson.id, lesson]));
    const questions = new Map(data.value.quiz_questions.map((q) => [q.id, q]));
    const challenges = new Map(data.value.challenges.map((c) => [c.id, c]));
    for (const item of items.value) {
        const question = item.kind === 'quiz' ? questions.get(item.id) : undefined;
        const challenge = item.kind === 'challenge' ? challenges.get(item.id) : undefined;
        const lesson = lessons.get(question?.lesson_id ?? challenge?.lesson_id);
        if (lesson && (question || challenge)) byKey.set(item.key, { item, lesson, question, challenge });
    }
    return byKey;
});

const known = computed(() => items.value.filter((item) => content.value.has(item.key)));
const due = computed(() => dueItems(known.value, reviewCards, now.value));
const upcoming = computed(() => nextDue(known.value, reviewCards, now.value));

/** Items grouped by lesson, in course and lesson order. */
const groups = computed(() => {
    const byLesson = new Map();
    for (const item of known.value) {
        const entry = content.value.get(item.key);
        const group = byLesson.get(entry.lesson.id) ?? { lesson: entry.lesson, entries: [] };
        group.entries.push(entry);
        byLesson.set(entry.lesson.id, group);
    }
    return [...byLesson.values()].sort(
        (a, b) => a.lesson.course.position - b.lesson.course.position || a.lesson.position - b.lesson.position,
    );
});

function status(item) {
    const card = reviewCards[item.key];
    if (!card) return { text: 'New', due: true };
    if (card.due <= now.value) return { text: 'Due', due: true };
    const when = describeDue(now.value, card.due);
    return { text: when.charAt(0).toUpperCase() + when.slice(1), due: false };
}

const plainText = (markdown) => markdown.replace(/[`*_]/g, '');
const plural = (count, word) => `${count} ${word}${count === 1 ? '' : 's'}`;

// The session: a queue of item keys, worked through in order.
const queue = ref(null);
const position = ref(0);
const reviewed = ref(0);
const current = computed(() => (queue.value ? content.value.get(queue.value[position.value]) : null));

function start(keys) {
    queue.value = keys;
    position.value = 0;
    reviewed.value = 0;
    window.scrollTo({ top: 0 });
}

function practiseAhead() {
    const soonest = [...known.value].sort((a, b) => (reviewCards[a.key]?.due ?? 0) - (reviewCards[b.key]?.due ?? 0));
    start(soonest.slice(0, PRACTICE_SIZE).map((item) => item.key));
}

function grade(value) {
    const key = queue.value[position.value];
    gradeCard(key, value);
    reviewed.value++;
    // Like Anki, a missed card comes back before the session ends.
    if (value === 'again') queue.value.push(key);
    position.value++;
    now.value = Date.now();
    window.scrollTo({ top: 0 });
}
</script>

<template>
    <div class="review">
        <template v-if="!items.length">
            <h1>Nothing to review yet</h1>
            <p class="lead">
                Quiz questions you answer correctly and challenges you solve will appear here, ready to review at growing
                intervals so you keep remembering them.
            </p>
            <RouterLink to="/" class="btn btn-primary">Browse courses</RouterLink>
        </template>

        <template v-else-if="queue && current">
            <header class="session-head">
                <p>
                    Card {{ position + 1 }} of {{ queue.length }} ·
                    <RouterLink :to="{ name: 'lesson', params: { course: current.lesson.course.slug, lesson: current.lesson.slug } }">
                        {{ current.lesson.course.title }}, {{ current.lesson.title }}
                    </RouterLink>
                </p>
                <button type="button" class="btn btn-quiet" @click="queue = null">End review</button>
            </header>
            <ReviewQuizCard
                v-if="current.question"
                :key="`${current.item.key}-${position}`"
                :question="current.question"
                :language="current.lesson.course.language"
                :card="reviewCards[current.item.key]"
                @grade="grade"
            />
            <ReviewChallengeCard
                v-else
                :key="`${current.item.key}-${position}`"
                :challenge="current.challenge"
                :lesson-id="current.lesson.id"
                :language="current.lesson.course.language"
                :card="reviewCards[current.item.key]"
                @grade="grade"
            />
        </template>

        <template v-else-if="queue">
            <h1>Review done</h1>
            <p class="lead">You reviewed {{ plural(reviewed, 'card') }}. Nicely done.</p>
            <button type="button" class="btn btn-primary" @click="queue = null">Back to the overview</button>
        </template>

        <LoadState v-else :error="error" :loading="!data">
            <section class="summary">
                <h1>{{ due.length ? `${plural(due.length, 'card')} to review` : 'All caught up' }}</h1>
                <p class="lead">
                    {{ plural(known.length, 'item') }} passed so far.
                    <template v-if="!due.length && upcoming">The next review is due {{ describeDue(now, upcoming) }}.</template>
                </p>
                <button v-if="due.length" type="button" class="btn btn-primary" @click="start(due.map((item) => item.key))">
                    Start review
                </button>
                <button v-else type="button" class="btn" @click="practiseAhead">Practise anyway</button>
            </section>

            <section v-for="group in groups" :key="group.lesson.id" class="group" :aria-labelledby="`review-lesson-${group.lesson.id}`">
                <h2 :id="`review-lesson-${group.lesson.id}`">
                    <RouterLink :to="{ name: 'lesson', params: { course: group.lesson.course.slug, lesson: group.lesson.slug } }">
                        {{ group.lesson.title }}
                    </RouterLink>
                    <span class="course">{{ group.lesson.course.title }}</span>
                </h2>
                <ul>
                    <li v-for="entry in group.entries" :key="entry.item.key">
                        <span class="kind">{{ entry.question ? 'Quiz' : 'Challenge' }}</span>
                        <span class="text">{{ entry.question ? plainText(entry.question.question) : entry.challenge.title }}</span>
                        <span class="status" :class="{ due: status(entry.item).due }">{{ status(entry.item).text }}</span>
                    </li>
                </ul>
            </section>
        </LoadState>
    </div>
</template>

<style scoped>
.review {
    display: grid;
    gap: 1.25rem;
    padding: 2.5rem 0 5rem;
}

h1 {
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 800;
}

.lead {
    color: var(--muted);
    max-width: 60ch;
}

.review > .btn {
    justify-self: start;
}

.summary {
    display: grid;
    gap: 0.75rem;
    justify-items: start;
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    background: var(--surface);
    border: 1px solid var(--line);
    margin-bottom: 1rem;
}

.session-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem 1rem;
    color: var(--muted);
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--line);
}

.group h2 {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.25rem 0.75rem;
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.4rem;
}

.group h2 a {
    color: var(--ink);
}

.course {
    font-size: 0.9rem;
    font-weight: 400;
    color: var(--muted);
}

.group ul {
    display: grid;
    border-top: 1px solid var(--line);
}

.group li {
    display: grid;
    grid-template-columns: 6rem minmax(0, 1fr) auto;
    align-items: baseline;
    gap: 1rem;
    padding: 0.6rem 0;
    border-bottom: 1px solid var(--line);
}

.kind {
    font-size: 0.85rem;
    color: var(--muted);
}

.text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.status {
    font-size: 0.85rem;
    color: var(--muted);
    font-variant-numeric: tabular-nums;
}

.status.due {
    color: var(--ok);
    font-weight: 700;
}

@media (max-width: 640px) {
    .group li {
        grid-template-columns: minmax(0, 1fr) auto;
    }

    .kind {
        display: none;
    }
}
</style>
