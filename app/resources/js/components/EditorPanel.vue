<script setup>
import { ref } from 'vue';
import CodeEditor from './CodeEditor.vue';

defineProps({
    modelValue: { type: String, default: '' },
    language: { type: String, default: 'sql' },
    schema: { type: Array, default: () => [] },
    running: { type: Boolean, default: false },
    label: { type: String, default: 'Code editor' },
    resetLabel: { type: String, default: 'Reset' },
});

const emit = defineEmits(['update:modelValue', 'run', 'reset']);

const editor = ref(null);
const shortcut = /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘ Enter' : 'Ctrl Enter';

defineExpose({ focus: () => editor.value?.focus() });
</script>

<template>
    <div class="editor-panel">
        <CodeEditor
            ref="editor"
            :model-value="modelValue"
            :language="language"
            :schema="schema"
            :label="label"
            @update:model-value="emit('update:modelValue', $event)"
            @run="emit('run')"
        />
        <div class="toolbar">
            <button type="button" class="btn btn-primary" :disabled="running" @click="emit('run')">
                {{ running ? 'Running…' : 'Run' }}
                <span class="kbd" aria-hidden="true">{{ shortcut }}</span>
            </button>
            <slot name="actions" />
            <button type="button" class="btn btn-quiet reset" @click="emit('reset')">{{ resetLabel }}</button>
        </div>
    </div>
</template>

<style scoped>
.editor-panel {
    border: 1px solid var(--ink);
    border-radius: var(--radius);
    background: var(--surface);
}

.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    padding: 0.6rem;
    border-top: 1px solid var(--line);
}

.reset {
    margin-left: auto;
}
</style>
