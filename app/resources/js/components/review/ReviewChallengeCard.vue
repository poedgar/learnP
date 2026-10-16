<script setup>
import { computed, ref } from 'vue';
import { engineFor } from '../../lib/engines';
import MarkdownContent from '../MarkdownContent.vue';
import EditorPanel from '../EditorPanel.vue';
import RunResults from '../RunResults.vue';
import PhpOutput from '../PhpOutput.vue';
import JsOutput from '../JsOutput.vue';
import PagePreview from '../PagePreview.vue';
import { usePagePreview } from '../../lib/js/usePagePreview';
import SqlCheckDetails from '../lesson/SqlCheckDetails.vue';
import PhpCheckDetails from '../lesson/PhpCheckDetails.vue';
import GradeButtons from './GradeButtons.vue';

const props = defineProps({
    challenge: { type: Object, required: true },
    lessonId: { type: Number, required: true },
    language: { type: String, required: true },
    card: { type: Object, default: undefined },
});

const emit = defineEmits(['grade']);

/** Solved again from the starter code; nothing here touches the lesson's saved code or progress. */
const engine = computed(() => engineFor(props.language, props.lessonId));
const code = ref(props.challenge.starter_code ?? '');
const showHint = ref(false);
const busy = ref(null); // 'run' | 'check' | null
const run = ref(null);
const check = ref(null);
const failure = ref('');
const passed = computed(() => check.value?.passed ?? false);
const page = usePagePreview();

async function work(kind) {
    if (busy.value) return;
    busy.value = kind;
    failure.value = '';
    run.value = null;
    check.value = null;
    page.clear();
    try {
        if (kind === 'run') {
            run.value = await engine.value.run(code.value, { host: page.hostElement() });
            if (run.value.js) page.show(run.value.js);
        } else {
            check.value = await engine.value.check(props.challenge, code.value);
        }
    } catch (error) {
        failure.value = error.message;
    } finally {
        busy.value = null;
    }
}
</script>

<template>
    <section class="card" :aria-labelledby="`review-challenge-${challenge.id}`">
        <h2 :id="`review-challenge-${challenge.id}`">{{ challenge.title }}</h2>
        <MarkdownContent :source="challenge.prompt" class="prompt" />
        <p v-if="challenge.order_matters" class="order-note">Row order is checked for this challenge.</p>

        <div v-if="challenge.hint" class="hint">
            <button type="button" class="btn btn-quiet" :aria-expanded="showHint" @click="showHint = !showHint">
                {{ showHint ? 'Hide hint' : 'Show a hint' }}
            </button>
            <p v-if="showHint">{{ challenge.hint }}</p>
        </div>

        <EditorPanel
            v-model="code"
            :language="language"
            :running="busy === 'run'"
            :label="`Code for challenge: ${challenge.title}`"
            reset-label="I don't remember"
            @run="work('run')"
            @reset="emit('grade', 'again')"
        >
            <template #actions>
                <button type="button" class="btn check" :disabled="busy !== null || passed" @click="work('check')">
                    {{ busy === 'check' ? 'Checking…' : 'Check answer' }}
                </button>
            </template>
        </EditorPanel>

        <p v-if="failure" class="failure" role="alert">{{ failure }}</p>

        <template v-if="check">
            <div class="verdict" :class="passed ? 'good' : 'bad'" role="status">
                <strong>{{ check.message }}</strong>
            </div>
            <GradeButtons v-if="passed" :card="card" :grades="['hard', 'good', 'easy']" @grade="emit('grade', $event)" />
            <SqlCheckDetails v-if="language === 'sql'" :check="check" />
            <PhpCheckDetails v-else :check="check" :language="language" />
        </template>

        <template v-else-if="run">
            <RunResults v-if="run.sql" :run="run.sql" />
            <JsOutput v-else-if="run.js" v-bind="run.js" :quiet="language === 'html'" />
            <PhpOutput v-else v-bind="run.php" />
        </template>

        <PagePreview :ref="page.preview" :visible="Boolean(page.frame.value)" />
    </section>
</template>

<style scoped>
.card {
    display: grid;
    gap: 1rem;
    min-width: 0;
}

.card h2 {
    font-size: 1.6rem;
    font-weight: 800;
}

.prompt {
    font-size: 1.08rem;
    max-width: 65ch;
}

.order-note {
    font-size: 0.88rem;
    color: var(--muted);
    margin-top: -0.5rem;
}

.hint p {
    margin-top: 0.35rem;
    padding: 0.7rem 1rem;
    border-radius: var(--radius);
    background: var(--highlight-soft);
    max-width: 65ch;
}

.hint .btn {
    padding-left: 0.4rem;
    padding-right: 0.4rem;
}

.check {
    border-color: var(--ink);
}

.verdict {
    padding: 0.85rem 1rem;
    border-radius: var(--radius);
    border-left: 4px solid;
}

.verdict.good {
    background: var(--ok-soft);
    border-color: var(--ok);
}

.verdict.bad {
    background: var(--bad-soft);
    border-color: var(--bad);
}

.failure {
    color: var(--bad);
    font-weight: 700;
}
</style>
