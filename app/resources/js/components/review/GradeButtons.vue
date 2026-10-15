<script setup>
import { GRADE_LABELS, previewWait } from '../../review';

const props = defineProps({
    card: { type: Object, default: undefined },
    grades: { type: Array, required: true },
});

const emit = defineEmits(['grade']);

const now = Date.now();
const options = props.grades.map((grade) => ({ grade, label: GRADE_LABELS[grade], wait: previewWait(props.card, grade, now) }));
</script>

<template>
    <div class="grades" role="group" aria-label="How well did you remember it?">
        <button
            v-for="option in options"
            :key="option.grade"
            type="button"
            class="btn grade"
            :class="{ 'btn-primary': option.grade === 'good' }"
            :aria-label="`${option.label}, next review in ${option.wait}`"
            @click="emit('grade', option.grade)"
        >
            <strong>{{ option.label }}</strong>
            <span>{{ option.wait }}</span>
        </button>
    </div>
</template>

<style scoped>
.grades {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(7rem, 1fr));
    gap: 0.5rem;
    max-width: 30rem;
}

.grade {
    display: grid;
    gap: 0.1rem;
    justify-items: center;
    padding-top: 0.55rem;
    padding-bottom: 0.55rem;
}

.grade span {
    font-size: 0.82rem;
    font-weight: 400;
    opacity: 0.8;
}
</style>
