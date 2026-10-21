<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../api';
import { activity, streak } from '../activity';
import { courseProgress } from '../progress';
import LoadState from '../components/LoadState.vue';

const router = useRouter();
const courses = ref(null);
const tracks = ref([]);
const error = ref(null);

onMounted(async () => {
    document.title = 'learnP: learn programming by running real code';
    try {
        // Learning paths are a nicety: without them, the plain catalogue still works.
        const [allCourses, allTracks] = await Promise.all([api.courses(), api.tracks().catch(() => [])]);
        courses.value = allCourses;
        tracks.value = allTracks;
    } catch (e) {
        error.value = e;
    }
});

const bySlug = computed(() => Object.fromEntries((courses.value ?? []).map((course) => [course.slug, course])));

/** Each track with its courses, or one list of every course when there are no tracks. */
const sections = computed(() => {
    if (!courses.value) return [];
    const withCourses = tracks.value
        .map((track) => ({ ...track, courses: track.courses.map((slug) => bySlug.value[slug]).filter(Boolean) }))
        .filter((track) => track.courses.length);

    return withCourses.length
        ? withCourses
        : [{ slug: 'all', title: 'All courses', description: '', courses: courses.value }];
});

const dashboard = computed(() => {
    if (!courses.value) return null;
    const progress = courses.value.map((course) => ({ course, ...courseProgress(course) }));
    const lessonsDone = progress.reduce((sum, p) => sum + p.completed, 0);
    const finished = progress.filter((p) => p.complete).map((p) => p.course);
    const days = streak(activity.days);

    if (!activity.recent && lessonsDone === 0) return null;
    return { recent: activity.recent, lessonsDone, finished, days };
});

function progressLabel(course) {
    const { completed, total, complete, started } = courseProgress(course);
    if (complete) return 'done';
    return started ? `${completed}/${total}` : '–';
}

function open(course) {
    router.push({ name: 'course', params: { course: course.slug } });
}
</script>

<template>
    <div class="home">
        <section class="intro">
            <h1>Learn programming by running real code.</h1>
            <p>
                Each lesson pairs a short explanation with a live sandbox, a quiz, and challenges that check your
                answers by running your code. Nothing to install.
            </p>
        </section>

        <section v-if="dashboard" class="dashboard" aria-labelledby="dashboard-heading">
            <h2 id="dashboard-heading" class="visually-hidden">Your progress</h2>
            <RouterLink
                v-if="dashboard.recent"
                class="continue"
                :to="{ name: 'lesson', params: { course: dashboard.recent.course, lesson: dashboard.recent.lesson } }"
            >
                <span class="label">Continue where you left off</span>
                <span class="lesson">{{ dashboard.recent.lessonTitle }}</span>
                <span class="course">{{ dashboard.recent.courseTitle }}</span>
            </RouterLink>
            <dl class="stats">
                <div>
                    <dt>Lessons completed</dt>
                    <dd>{{ dashboard.lessonsDone }}</dd>
                </div>
                <div>
                    <dt>Day streak</dt>
                    <dd>{{ dashboard.days }}</dd>
                </div>
                <div>
                    <dt>Courses finished</dt>
                    <dd>{{ dashboard.finished.length }}</dd>
                </div>
            </dl>
            <ul v-if="dashboard.finished.length" class="certificates">
                <li v-for="course in dashboard.finished" :key="course.slug">
                    <RouterLink :to="{ name: 'certificate', params: { course: course.slug } }">Certificate: {{ course.title }}</RouterLink>
                </li>
            </ul>
        </section>

        <LoadState :error="error" :loading="!courses">
            <section
                v-for="track in sections"
                :key="track.slug"
                class="catalog"
                :aria-labelledby="`track-${track.slug}`"
            >
                <pre class="catalog-query" aria-hidden="true"><code><span class="tok-keyword">SELECT</span> title, lessons, progress, description
<span class="tok-keyword">FROM</span> courses<template v-if="track.slug !== 'all'">
<span class="tok-keyword">WHERE</span> track = <span class="tok-string">'{{ track.slug }}'</span></template>
<span class="tok-keyword">ORDER BY</span> step;</code></pre>
                <div class="track-heading">
                    <h2 :id="`track-${track.slug}`">{{ track.title }}</h2>
                    <p v-if="track.description">{{ track.description }}</p>
                </div>
                <div class="catalog-result">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">title</th>
                                <th scope="col" class="numeric">lessons</th>
                                <th scope="col" class="numeric">progress</th>
                                <th scope="col">description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="course in track.courses" :key="course.id" @click="open(course)">
                                <td class="title">
                                    <RouterLink :to="{ name: 'course', params: { course: course.slug } }">
                                        {{ course.title }}
                                    </RouterLink>
                                </td>
                                <td class="numeric">{{ course.lessons_count }}</td>
                                <td class="numeric progress" :class="{ done: courseProgress(course).complete }">
                                    {{ progressLabel(course) }}
                                </td>
                                <td class="description">{{ course.description }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="rows">{{ track.courses.length }} {{ track.courses.length === 1 ? 'row' : 'rows' }}.</p>
                </div>
            </section>
        </LoadState>
    </div>
</template>

<style scoped>
.home {
    padding: 4.5rem 0 5rem;
}

.intro h1 {
    font-size: clamp(2.6rem, 7vw, 5rem);
    font-weight: 800;
    line-height: 1.02;
    letter-spacing: -0.025em;
    max-width: 14ch;
}

.intro p {
    margin-top: 1.25rem;
    max-width: 52ch;
    font-size: 1.2rem;
    color: var(--ink-soft);
}

.dashboard {
    margin-top: 2.5rem;
    display: grid;
    gap: 1rem;
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
    align-items: stretch;
}

.continue {
    display: grid;
    gap: 0.2rem;
    padding: 1.25rem 1.5rem;
    border-radius: var(--radius-lg);
    background: var(--highlight-soft);
    border: 2px solid var(--highlight);
    color: var(--ink);
}

.continue .label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--ink-soft);
}

.continue .lesson {
    font-family: var(--font-display);
    font-size: 1.5rem;
    font-weight: 800;
}

.continue .course {
    color: var(--ink-soft);
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    padding: 1rem;
    border-radius: var(--radius-lg);
    background: var(--surface);
    border: 1px solid var(--line);
}

.stats div {
    display: grid;
    align-content: center;
    text-align: center;
}

.stats dt {
    font-size: 0.8rem;
    color: var(--muted);
}

.stats dd {
    font-family: var(--font-display);
    font-size: 2rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}

.certificates {
    grid-column: 1 / -1;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1.25rem;
}

.certificates a {
    font-weight: 700;
    color: var(--ink);
    text-decoration: underline;
}

@media (max-width: 720px) {
    .dashboard {
        grid-template-columns: 1fr;
    }
}

.catalog {
    margin-top: 3rem;
    border: 1px solid var(--ink);
    border-radius: var(--radius-lg);
    overflow: hidden;
    background: var(--surface);
    box-shadow: 6px 6px 0 var(--ink);
}

.catalog-query {
    margin: 0;
    padding: 1.25rem 1.5rem;
    background: var(--ink);
    color: #eef1f8;
    font-size: clamp(0.9rem, 2vw, 1.1rem);
    line-height: 1.7;
    overflow-x: auto;
}

.track-heading {
    padding: 1.25rem 1.5rem 0.25rem;
}

.track-heading h2 {
    font-size: 1.5rem;
    font-weight: 800;
}

.track-heading p {
    margin-top: 0.25rem;
    max-width: 70ch;
    color: var(--ink-soft);
}

.catalog-result {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    font-family: var(--font-code);
    font-size: 0.8rem;
    font-weight: 600;
    text-align: left;
    padding: 0.8rem 1.5rem;
    border-bottom: 2px solid var(--ink);
}

td {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid var(--line);
    vertical-align: top;
}

tbody tr {
    cursor: pointer;
}

tbody tr:hover td {
    background: var(--highlight-soft);
}

.numeric {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.progress {
    font-family: var(--font-code);
    color: var(--muted);
}

.progress.done {
    color: var(--ok);
    font-weight: 700;
}

.title a {
    font-family: var(--font-display);
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--ink);
}

.description {
    color: var(--ink-soft);
    max-width: 50ch;
    min-width: 16rem;
}

.rows {
    padding: 0.8rem 1.5rem;
    font-size: 0.88rem;
    color: var(--muted);
}

.home :deep(.state) {
    padding: 2rem 1.5rem;
}
</style>
