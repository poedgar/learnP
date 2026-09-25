<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../api';
import LoadState from '../components/LoadState.vue';

const router = useRouter();
const courses = ref(null);
const error = ref(null);

onMounted(async () => {
    document.title = 'learnP: learn programming by running real code';
    try {
        courses.value = await api.courses();
    } catch (e) {
        error.value = e;
    }
});

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

        <section class="catalog" aria-labelledby="catalog-heading">
            <h2 id="catalog-heading" class="visually-hidden">Courses</h2>
            <pre class="catalog-query" aria-hidden="true"><code><span class="tok-keyword">SELECT</span> title, lessons, description
<span class="tok-keyword">FROM</span> courses
<span class="tok-keyword">ORDER BY</span> position;</code></pre>

            <LoadState :error="error" :loading="!courses">
                <div v-if="courses" class="catalog-result">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">title</th>
                                <th scope="col" class="numeric">lessons</th>
                                <th scope="col">description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="course in courses" :key="course.id" @click="open(course)">
                                <td class="title">
                                    <RouterLink :to="{ name: 'course', params: { course: course.slug } }">
                                        {{ course.title }}
                                    </RouterLink>
                                </td>
                                <td class="numeric">{{ course.lessons_count }}</td>
                                <td class="description">{{ course.description }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="rows">
                        {{ courses.length }} {{ courses.length === 1 ? 'row' : 'rows' }}. More courses are on the way.
                    </p>
                </div>
            </LoadState>
        </section>
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

.catalog {
    margin-top: 3.5rem;
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

.catalog :deep(.state) {
    padding: 2rem 1.5rem;
}
</style>
