<script setup>
import { computed } from 'vue';
import { Marked } from 'marked';
import DOMPurify from 'dompurify';
import { canHighlight, highlightCode } from '../lib/highlight';

const props = defineProps({
    source: { type: String, required: true },
    /** Render a single paragraph without the wrapping <p>, e.g. for quiz questions. */
    inline: { type: Boolean, default: false },
    /** Add a "Try it" button under each code example in this language (the lesson's language). */
    runnable: { type: String, default: null },
});

const emit = defineEmits(['try']);

const rendered = computed(() => {
    const snippets = [];
    const marked = new Marked({
        gfm: true,
        renderer: {
            code({ text, lang }) {
                if (!canHighlight(lang)) {
                    return false;
                }
                const pre = `<pre class="code-block"><code>${highlightCode(text, lang)}</code></pre>`;
                if (props.runnable !== lang) {
                    return pre;
                }
                snippets.push(text);
                return `<div class="try-block">${pre}<button type="button" class="try-button" data-snippet="${snippets.length - 1}">Try it in the sandbox</button></div>`;
            },
        },
    });

    const html = props.inline ? marked.parseInline(props.source) : marked.parse(props.source);
    return { html: DOMPurify.sanitize(html), snippets };
});

function onClick(event) {
    const button = event.target.closest('[data-snippet]');
    if (button) {
        emit('try', rendered.value.snippets[Number(button.dataset.snippet)]);
    }
}
</script>

<template>
    <span v-if="inline" class="markdown markdown-inline" v-html="rendered.html"></span>
    <div v-else class="markdown" v-html="rendered.html" @click="onClick"></div>
</template>

<style>
.markdown > * + * {
    margin-top: 1rem;
}

.markdown h2 {
    font-size: 1.75rem;
    font-weight: 800;
    margin-top: 3rem;
    padding-top: 0.25rem;
}

.markdown h2:first-child {
    margin-top: 0;
}

.markdown h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-top: 2rem;
}

.markdown ul,
.markdown ol {
    padding-left: 1.4rem;
}

.markdown ul {
    list-style: disc;
}

.markdown ol {
    list-style: decimal;
}

.markdown li + li {
    margin-top: 0.35rem;
}

.markdown a {
    color: var(--ink);
    text-decoration: underline;
    text-decoration-color: var(--highlight);
    text-decoration-thickness: 2px;
    text-underline-offset: 3px;
}

.markdown :not(pre) > code {
    font-size: 0.86em;
    padding: 0.1em 0.35em;
    border-radius: var(--radius-sm);
    background: var(--highlight-soft);
    color: var(--ink);
}

.markdown blockquote {
    border-left: 4px solid var(--highlight);
    background: var(--surface);
    padding: 0.9rem 1.1rem;
    border-radius: 0 var(--radius) var(--radius) 0;
    color: var(--ink-soft);
}

.markdown table {
    width: max-content;
    max-width: 100%;
    border-collapse: collapse;
    font-size: 0.93rem;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    overflow: hidden;
    display: block;
    overflow-x: auto;
}

.markdown th,
.markdown td {
    padding: 0.45rem 0.8rem;
    border-bottom: 1px solid var(--line);
    text-align: left;
    white-space: nowrap;
}

.markdown th {
    font-family: var(--font-code);
    font-size: 0.8rem;
    font-weight: 600;
    border-bottom: 2px solid var(--ink);
}

.markdown tr:last-child td {
    border-bottom: none;
}

.code-block {
    background: var(--ink);
    color: #eef1f8;
    padding: 1rem 1.15rem;
    border-radius: var(--radius);
    overflow-x: auto;
    font-size: 0.92rem;
    line-height: 1.65;
}

.try-block .code-block {
    border-radius: var(--radius) var(--radius) 0 0;
}

.try-button {
    display: block;
    width: 100%;
    text-align: left;
    padding: 0.5rem 1.15rem;
    background: var(--ink-soft);
    color: #fff;
    font-weight: 700;
    font-size: 0.88rem;
    border-radius: 0 0 var(--radius) var(--radius);
    cursor: pointer;
}

.try-button:hover {
    background: #34467a;
}
</style>
