<script setup>
defineProps({
    loading: { type: Boolean, default: false },
    error: { type: Object, default: null },
    notFound: { type: String, default: "We couldn't find that page." },
});
</script>

<template>
    <div v-if="error" class="state" role="alert">
        <h1>{{ error.status === 404 ? notFound : 'Something went wrong' }}</h1>
        <p v-if="error.status !== 404">{{ error.message }}</p>
        <RouterLink to="/" class="btn">See all courses</RouterLink>
    </div>
    <div v-else-if="loading" class="state loading" aria-busy="true">Loading…</div>
    <slot v-else />
</template>

<style scoped>
.state {
    padding: 5rem 0;
    display: grid;
    gap: 1rem;
    justify-items: start;
}

.state h1 {
    font-size: 2rem;
    font-weight: 800;
}

.loading {
    color: var(--muted);
}
</style>
