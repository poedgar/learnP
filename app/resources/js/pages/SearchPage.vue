<script setup>
import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../api';

const route = useRoute();
const router = useRouter();
const query = ref(typeof route.query.q === 'string' ? route.query.q : '');
const results = ref(null);
const error = ref('');
const loading = ref(false);

async function search(term) {
    error.value = '';
    if (term.trim().length < 2) {
        results.value = null;
        return;
    }
    loading.value = true;
    try {
        results.value = await api.search(term.trim());
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

watch(
    () => route.query.q,
    (q) => {
        query.value = typeof q === 'string' ? q : '';
        document.title = query.value ? `Search: ${query.value} | learnP` : 'Search | learnP';
        search(query.value);
    },
    { immediate: true },
);

function submit() {
    router.replace({ name: 'search', query: { q: query.value } });
}
</script>

<template>
    <div class="search-page">
        <h1>Search the lessons</h1>
        <form role="search" class="search-form" @submit.prevent="submit">
            <label for="search-query" class="visually-hidden">Search for</label>
            <input id="search-query" v-model="query" type="search" placeholder="For example: array_column, JOIN, promises" autofocus />
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <p v-if="error" class="error" role="alert">{{ error }}</p>
        <p v-else-if="loading" class="muted" aria-busy="true">Searching…</p>
        <template v-else-if="results">
            <p class="muted" role="status">
                {{ results.length ? `${results.length} ${results.length === 1 ? 'lesson' : 'lessons'} found` : 'Nothing found. Try another word.' }}
            </p>
            <ol class="results">
                <li v-for="result in results" :key="`${result.course.slug}/${result.lesson.slug}`">
                    <RouterLink :to="{ name: 'lesson', params: { course: result.course.slug, lesson: result.lesson.slug } }">
                        {{ result.lesson.title }}
                    </RouterLink>
                    <span class="course">{{ result.course.title }}</span>
                    <p>{{ result.snippet }}</p>
                </li>
            </ol>
        </template>
    </div>
</template>

<style scoped>
.search-page {
    display: grid;
    gap: 1.25rem;
    padding: 2.5rem 0 5rem;
    max-width: 48rem;
}

h1 {
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 800;
}

.search-form {
    display: flex;
    gap: 0.5rem;
}

.search-form input {
    flex: 1;
    min-width: 0;
    padding: 0.6rem 0.8rem;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface);
    color: var(--ink);
    font-size: 1.05rem;
}

.muted {
    color: var(--muted);
}

.error {
    color: var(--bad);
}

.results {
    display: grid;
    gap: 1.25rem;
}

.results a {
    font-family: var(--font-display);
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--ink);
}

.course {
    margin-left: 0.5rem;
    color: var(--muted);
    font-size: 0.9rem;
}

.results p {
    margin-top: 0.25rem;
    color: var(--ink-soft);
}
</style>
