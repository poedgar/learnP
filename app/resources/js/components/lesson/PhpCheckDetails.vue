<script setup>
import PhpOutput from '../PhpOutput.vue';

defineProps({
    /** Report from checkChallenge() in resources/js/lib/php/sandbox.js. */
    check: { type: Object, required: true },
});
</script>

<template>
    <div class="details">
        <p v-if="check.error" class="error" role="alert">{{ check.error }}</p>

        <ul class="checks" aria-label="Checks">
            <li v-for="(item, i) in check.checks" :key="i" :class="check.loaded ? (item.passed ? 'pass' : 'fail') : 'pending'">
                <span class="mark" aria-hidden="true">{{ !check.loaded ? '' : item.passed ? '✓' : '✗' }}</span>
                <div>
                    <span class="label">{{ item.label }}</span>
                    <span class="visually-hidden">{{ !check.loaded ? '(not run)' : item.passed ? '(passed)' : '(failed)' }}</span>
                    <p v-if="check.loaded && !item.passed && item.message" class="message">{{ item.message }}</p>
                </div>
            </li>
        </ul>

        <div v-if="check.output.trim()">
            <h4>What your code printed</h4>
            <PhpOutput :output="check.output" :fatal="!check.loaded" />
        </div>
    </div>
</template>

<style scoped>
.details {
    display: grid;
    gap: 1rem;
}

.checks {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius);
}

.checks li {
    display: flex;
    gap: 0.75rem;
    padding: 0.6rem 0.9rem;
    border-bottom: 1px solid var(--line);
}

.checks li:last-child {
    border-bottom: none;
}

.mark {
    flex: none;
    display: grid;
    place-items: center;
    width: 1.4rem;
    height: 1.4rem;
    margin-top: 0.1rem;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 700;
    color: #fff;
}

.pass .mark {
    background: var(--ok);
}

.fail .mark {
    background: var(--bad);
}

.pending .mark {
    border: 2px solid var(--line);
}

.pending .label {
    font-weight: 400;
    color: var(--muted);
}

.label {
    font-weight: 700;
}

.pass .label {
    font-weight: 400;
    color: var(--ink-soft);
}

.message {
    margin-top: 0.15rem;
    font-family: var(--font-code);
    font-size: 0.85rem;
    color: #7d1f29;
}

.error {
    padding: 0.8rem 1rem;
    border-radius: var(--radius);
    background: var(--bad-soft);
    border-left: 4px solid var(--bad);
    color: #7d1f29;
    font-family: var(--font-code);
    font-size: 0.88rem;
}

h4 {
    font-weight: 700;
    font-size: 0.95rem;
    margin-bottom: 0.4rem;
}
</style>
