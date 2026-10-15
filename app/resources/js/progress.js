import { reactive, watch } from 'vue';

/*
 * Learner progress lives in the browser: there are no accounts yet.
 * Shape: { lessons: { [lessonId]: { read, sandboxCode, quiz: { [questionId]: {...} }, challenges: { [challengeId]: {...} } } } }
 */
const STORAGE_KEY = 'learnp:progress:v2';

function load() {
    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
        if (saved && typeof saved.lessons === 'object') {
            return saved;
        }
    } catch {
        // Storage can be unavailable or hold invalid JSON; start fresh.
    }
    return { lessons: {} };
}

const state = reactive(load());

watch(
    state,
    () => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch {
            // Progress simply won't persist in this browser.
        }
    },
    { deep: true },
);

/** Every lesson's progress, keyed by lesson id (read-only use, such as building the review deck). */
export const allProgress = state.lessons;

export function lessonProgress(lessonId) {
    if (!state.lessons[lessonId]) {
        state.lessons[lessonId] = { read: false, sandboxCode: null, quiz: {}, challenges: {} };
    }
    return state.lessons[lessonId];
}

/** Summary used by lesson lists and the step tabs. */
export function lessonSummary(lesson) {
    const progress = state.lessons[lesson.id];
    const quizCorrect = Object.values(progress?.quiz ?? {}).filter((a) => a.correct).length;
    const quizAnswered = Object.keys(progress?.quiz ?? {}).length;
    const challengesPassed = Object.values(progress?.challenges ?? {}).filter((c) => c.passed).length;
    const quizTotal = lesson.quiz_questions_count ?? lesson.quiz_questions?.length ?? 0;
    const challengeTotal = lesson.challenges_count ?? lesson.challenges?.length ?? 0;

    return {
        read: progress?.read ?? false,
        quizAnswered,
        quizCorrect,
        quizTotal,
        challengesPassed,
        challengeTotal,
        complete:
            (progress?.read ?? false) &&
            quizAnswered === quizTotal &&
            challengesPassed === challengeTotal,
        started: Boolean(progress && (progress.read || quizAnswered || challengesPassed)),
    };
}
