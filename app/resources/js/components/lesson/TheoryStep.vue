<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { lessonProgress } from '../../progress';
import { scrollToTop } from '../../lib/scroll';
import MarkdownContent from '../MarkdownContent.vue';
import Pager from '../Pager.vue';

const props = defineProps({
    lesson: { type: Object, required: true },
    language: { type: String, required: true },
});

const emit = defineEmits(['try', 'finish', 'skip']);

/** One page per "## " section; any text before the first heading joins the first page. */
const sections = computed(() => {
    const chunks = props.lesson.content.split(/^(?=## )/m).filter((chunk) => chunk.trim());
    if (chunks.length > 1 && !chunks[0].startsWith('## ')) {
        chunks.splice(0, 2, `${chunks[1]}\n\n${chunks[0]}`);
    }
    return chunks.map((source) => ({
        source,
        // Titles appear as plain text in navigation, so drop inline-code backticks.
        title: source.match(/^## (.+)$/m)?.[1].replace(/`/g, '').trim() ?? props.lesson.title,
    }));
});

const progress = lessonProgress(props.lesson.id);
const page = ref(Math.min(progress.theoryPage ?? 1, sections.value.length));
const root = ref(null);

const current = computed(() => sections.value[page.value - 1]);
const previous = computed(() => sections.value[page.value - 2] ?? null);
const next = computed(() => sections.value[page.value] ?? null);
const isLast = computed(() => page.value === sections.value.length);

watch(page, async (value) => {
    progress.theoryPage = value;
    await nextTick();
    scrollToTop(root.value);
});
</script>

<template>
    <div ref="root" class="theory-layout">
        <article class="theory" :aria-label="current.title">
            <p class="position">Section {{ page }} of {{ sections.length }}</p>
            <MarkdownContent :key="page" :source="current.source" :runnable="language" @try="emit('try', $event)" />

            <footer class="page-nav">
                <button v-if="previous" type="button" class="btn page-link" @click="page--">
                    <span class="direction">Previous</span>
                    {{ previous.title }}
                </button>
                <button v-if="next" type="button" class="btn btn-primary page-link next" @click="page++">
                    <span class="direction">Next</span>
                    {{ next.title }}
                </button>
            </footer>

            <div v-if="isLast" class="finish">
                <p>That's the end of the reading. Try what you've learned in the sandbox, then take the quiz.</p>
                <div class="finish-actions">
                    <button type="button" class="btn btn-primary" @click="emit('finish')">
                        {{ progress.read ? 'Open the sandbox' : 'Mark as read and open the sandbox' }}
                    </button>
                    <button type="button" class="btn" @click="emit('skip')">Go to the quiz</button>
                </div>
            </div>

            <Pager
                v-model:page="page"
                class="mobile-pager"
                :count="sections.length"
                label="Lesson sections"
                :page-label="(n) => sections[n - 1].title"
                numbers-only
            />
        </article>

        <nav class="toc" aria-label="Sections in this lesson">
            <h3>In this lesson</h3>
            <ol>
                <li v-for="(section, i) in sections" :key="i">
                    <button
                        type="button"
                        :class="{ current: i + 1 === page }"
                        :aria-current="i + 1 === page ? 'page' : undefined"
                        @click="page = i + 1"
                    >
                        <span class="number">{{ i + 1 }}</span>
                        {{ section.title }}
                    </button>
                </li>
            </ol>
        </nav>
    </div>
</template>

<style scoped>
.theory-layout {
    display: grid;
    grid-template-columns: minmax(0, 44rem) minmax(12rem, 16rem);
    justify-content: space-between;
    gap: 3rem;
    scroll-margin-top: 1rem;
}

.theory {
    font-size: 1.1rem;
    line-height: 1.7;
    min-width: 0;
}

.position {
    font-size: 0.9rem;
    color: var(--muted);
    margin-bottom: 0.5rem;
}

.page-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 3rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--line);
}

.page-link {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.15rem;
    padding: 0.65rem 1rem;
    text-align: left;
    max-width: 100%;
}

.page-link.next {
    margin-left: auto;
    align-items: flex-end;
    text-align: right;
}

.direction {
    font-size: 0.78rem;
    font-weight: 400;
    opacity: 0.8;
}

.finish {
    margin-top: 1.5rem;
    padding: 1.25rem;
    border-radius: var(--radius-lg);
    background: var(--surface);
    border: 1px solid var(--line);
    font-size: 1rem;
}

.finish-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.9rem;
}

.toc {
    position: sticky;
    top: 1.5rem;
    align-self: start;
}

.toc h3 {
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 0.6rem;
}

.toc ol {
    border-left: 2px solid var(--line);
}

.toc button {
    display: flex;
    gap: 0.6rem;
    width: 100%;
    text-align: left;
    padding: 0.4rem 0.75rem;
    margin-left: -2px;
    border-left: 2px solid transparent;
    color: var(--muted);
    font-size: 0.93rem;
    line-height: 1.4;
    cursor: pointer;
}

.toc button:hover {
    color: var(--ink);
}

.toc button.current {
    color: var(--ink);
    font-weight: 700;
    border-left-color: var(--highlight);
}

.toc .number {
    font-variant-numeric: tabular-nums;
    min-width: 1.1rem;
}

.mobile-pager {
    display: none;
    margin-top: 1.5rem;
}

@media (max-width: 900px) {
    .theory-layout {
        grid-template-columns: minmax(0, 1fr);
    }

    .toc {
        display: none;
    }

    .mobile-pager {
        display: flex;
    }
}
</style>
