<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { EditorView, basicSetup } from 'codemirror';
import { EditorState, Compartment, Prec } from '@codemirror/state';
import { keymap } from '@codemirror/view';
import { indentWithTab } from '@codemirror/commands';
import { sql, SQLite } from '@codemirror/lang-sql';
import { php } from '@codemirror/lang-php';
import { javascript } from '@codemirror/lang-javascript';
import { html } from '@codemirror/lang-html';
import { StreamLanguage } from '@codemirror/language';
import { shell } from '@codemirror/legacy-modes/mode/shell';
import { HighlightStyle, syntaxHighlighting } from '@codemirror/language';
import { tags } from '@lezer/highlight';

const props = defineProps({
    modelValue: { type: String, default: '' },
    language: { type: String, default: 'sql', validator: (value) => ['sql', 'php', 'javascript', 'typescript', 'html'].includes(value) },
    /** SQL only: tables from the lesson dataset, used for autocompletion. */
    schema: { type: Array, default: () => [] },
    label: { type: String, default: 'Code editor' },
});

const emit = defineEmits(['update:modelValue', 'run']);

const host = ref(null);
const language = new Compartment();
let view;

const highlight = HighlightStyle.define([
    { tag: tags.keyword, color: '#f5b83d', fontWeight: '600' },
    { tag: [tags.string, tags.special(tags.string)], color: '#9fdcb3' },
    { tag: [tags.number, tags.bool, tags.null], color: '#f7a6ae' },
    { tag: tags.comment, color: '#8492b3', fontStyle: 'italic' },
    { tag: [tags.operator, tags.punctuation], color: '#c9d2e6' },
    { tag: [tags.typeName, tags.className, tags.standard(tags.name)], color: '#9cc3ff' },
    { tag: tags.processingInstruction, color: '#8492b3' },
]);

const theme = EditorView.theme(
    {
        '&': { backgroundColor: '#18233f', color: '#eef1f8', fontSize: '0.95rem', minHeight: '9rem' },
        '.cm-content': {
            fontFamily: 'var(--font-code)',
            fontVariantLigatures: 'none',
            padding: '0.85rem 0',
            caretColor: '#f5b83d',
        },
        '.cm-scroller': { fontFamily: 'var(--font-code)', lineHeight: '1.65' },
        '.cm-gutters': { backgroundColor: '#18233f', color: '#5f6d92', border: 'none' },
        '.cm-activeLine, .cm-activeLineGutter': { backgroundColor: 'rgb(255 255 255 / 0.04)' },
        '&.cm-focused': { outline: 'none' },
        '&.cm-focused .cm-cursor': { borderLeftColor: '#f5b83d', borderLeftWidth: '2px' },
        '.cm-selectionBackground, &.cm-focused .cm-selectionBackground, ::selection': {
            backgroundColor: 'rgb(245 184 61 / 0.28) !important',
        },
        '.cm-matchingBracket': { backgroundColor: 'rgb(245 184 61 / 0.25)', outline: 'none' },
        '.cm-tooltip': { border: '1px solid #2b3a61', backgroundColor: '#22305a', color: '#eef1f8' },
        '.cm-tooltip-autocomplete ul li[aria-selected]': { backgroundColor: '#f5b83d', color: '#18233f' },
    },
    { dark: true },
);

function languageFor(schema) {
    if (props.language === 'php') {
        return php({ baseLanguage: null });
    }
    if (props.language === 'git') {
        return StreamLanguage.define(shell);
    }
    if (props.language === 'html') {
        return html();
    }
    if (props.language === 'javascript' || props.language === 'typescript') {
        return javascript({ typescript: props.language === 'typescript' });
    }
    return sql({
        dialect: SQLite,
        upperCaseKeywords: true,
        schema: Object.fromEntries(schema.map((table) => [table.name, table.columns.map((c) => c.name)])),
    });
}

onMounted(() => {
    view = new EditorView({
        parent: host.value,
        state: EditorState.create({
            doc: props.modelValue,
            extensions: [
                Prec.highest(
                    keymap.of([
                        {
                            key: 'Mod-Enter',
                            run: () => {
                                emit('run');
                                return true;
                            },
                        },
                    ]),
                ),
                basicSetup,
                keymap.of([indentWithTab]),
                language.of(languageFor(props.schema)),
                syntaxHighlighting(highlight),
                theme,
                EditorView.lineWrapping,
                EditorView.contentAttributes.of({ 'aria-label': props.label }),
                EditorView.updateListener.of((update) => {
                    if (update.docChanged) {
                        emit('update:modelValue', update.state.doc.toString());
                    }
                }),
            ],
        }),
    });
});

watch(
    () => props.modelValue,
    (value) => {
        if (view && value !== view.state.doc.toString()) {
            view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: value } });
        }
    },
);

watch(
    () => props.schema,
    (schema) => view?.dispatch({ effects: language.reconfigure(languageFor(schema)) }),
);

onBeforeUnmount(() => view?.destroy());

defineExpose({ focus: () => view?.focus() });
</script>

<template>
    <div ref="host" class="sql-editor"></div>
</template>

<style scoped>
.sql-editor {
    border-radius: var(--radius) var(--radius) 0 0;
    overflow: hidden;
    background: #18233f;
}
</style>
