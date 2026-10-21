<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../api';
import { auth } from '../auth';
import { courseProgress } from '../progress';
import LoadState from '../components/LoadState.vue';

const props = defineProps({ course: { type: String, required: true } });

const course = ref(null);
const error = ref(null);
const name = ref('');

onMounted(async () => {
    try {
        course.value = await api.course(props.course);
        document.title = `Certificate: ${course.value.title} | learnP`;
    } catch (e) {
        error.value = e;
    }
});

const progress = computed(() => (course.value ? courseProgress(course.value) : null));
const shownName = computed(() => name.value.trim() || auth.user?.name || '');
const printPage = () => window.print();
const today = new Date().toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' });
</script>

<template>
    <LoadState :error="error" :loading="!course">
        <div v-if="course" class="certificate-page">
            <template v-if="progress.complete">
                <div class="controls">
                    <label for="certificate-name">Name on the certificate</label>
                    <input id="certificate-name" v-model="name" :placeholder="auth.user?.name ?? 'Your name'" autocomplete="name" />
                    <button type="button" class="btn btn-primary" @click="printPage">Print or save as PDF</button>
                </div>

                <article class="certificate" aria-label="Certificate">
                    <p class="kicker">learn<span>P</span> certificate of completion</p>
                    <p class="awarded">This is to say that</p>
                    <p class="name">{{ shownName || ' ' }}</p>
                    <p class="awarded">completed every lesson, quiz and challenge of</p>
                    <h1>{{ course.title }}</h1>
                    <p class="details">{{ progress.total }} lessons · {{ today }}</p>
                </article>
            </template>

            <div v-else class="not-yet">
                <h1>Not quite there yet</h1>
                <p>
                    You've completed {{ progress.completed }} of {{ progress.total }} lessons of {{ course.title }}. Finish
                    every lesson's reading, quiz and challenges to get your certificate.
                </p>
                <RouterLink class="btn btn-primary" :to="{ name: 'course', params: { course: course.slug } }">Back to the course</RouterLink>
            </div>
        </div>
    </LoadState>
</template>

<style scoped>
.certificate-page {
    display: grid;
    gap: 1.5rem;
    padding: 2.5rem 0 5rem;
}

.controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
}

.controls label {
    font-weight: 700;
}

.controls input {
    padding: 0.55rem 0.75rem;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface);
    color: var(--ink);
}

.certificate {
    display: grid;
    gap: 0.75rem;
    justify-items: center;
    text-align: center;
    padding: clamp(2rem, 6vw, 4.5rem) 1.5rem;
    border: 3px double var(--ink);
    border-radius: var(--radius-lg);
    background: var(--surface);
}

.kicker {
    font-family: var(--font-code);
    font-size: 0.85rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--muted);
}

.kicker span {
    padding: 0 0.15rem;
    background: var(--highlight);
    color: var(--ink);
    border-radius: var(--radius-sm);
}

.awarded {
    color: var(--ink-soft);
}

.name {
    font-family: var(--font-display);
    font-size: clamp(2rem, 6vw, 3.4rem);
    font-weight: 800;
    border-bottom: 2px solid var(--highlight);
    min-width: 60%;
}

.certificate h1 {
    font-size: clamp(1.6rem, 4vw, 2.4rem);
    font-weight: 800;
}

.details {
    color: var(--muted);
    font-variant-numeric: tabular-nums;
}

.not-yet {
    display: grid;
    gap: 1rem;
    justify-items: start;
    max-width: 60ch;
}

.not-yet h1 {
    font-size: 2rem;
    font-weight: 800;
}

@media print {
    .controls {
        display: none;
    }

    .certificate {
        border-color: #000;
    }
}
</style>
