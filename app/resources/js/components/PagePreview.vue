<script setup>
import { ref } from 'vue';

/**
 * Where a JavaScript run's page appears. The element must stay in the DOM while code runs (the
 * sandbox frame is created inside it), so it's only hidden until there's a page to show.
 */
defineProps({
    visible: { type: Boolean, default: false },
});

const element = ref(null);
defineExpose({ element });
</script>

<template>
    <section v-show="visible" class="page-preview" aria-label="The page your code built">
        <h4>Your page</h4>
        <p class="note">It's live: click and type in it. Anything it logs is added to the output above.</p>
        <div ref="element" class="frame-host"></div>
    </section>
</template>

<style scoped>
.page-preview {
    display: grid;
    gap: 0.4rem;
}

h4 {
    font-weight: 700;
    font-size: 0.95rem;
}

.note {
    font-size: 0.85rem;
    color: var(--muted);
}

.frame-host :deep(iframe) {
    display: block;
    width: 100%;
    height: 22rem;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: #fff;
}
</style>
