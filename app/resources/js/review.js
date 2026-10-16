import { reactive, watch } from 'vue';

/*
 * Spaced repetition with SM-2, the algorithm behind Anki. The same rules are in the
 * mobile app's src/review/schedule.ts; keep the two in step.
 *
 * Review schedules live in the browser next to progress, but separate from it:
 * reviewing never changes a lesson's quiz answers or saved code.
 * Shape: { cards: { ['quiz:12' | 'challenge:21']: { due, interval, ease, reps, lapses } } }
 */
const STORAGE_KEY = 'learnp:review:v1';
const STARTING_EASE = 2.5;
const MINIMUM_EASE = 1.3;
const AGAIN_DELAY_MS = 10 * 60_000;

export const GRADE_LABELS = { again: 'Again', hard: 'Hard', good: 'Good', easy: 'Easy' };

function load() {
    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
        if (saved && typeof saved.cards === 'object') {
            return saved;
        }
    } catch {
        // Storage can be unavailable or hold invalid JSON; start fresh.
    }
    return { cards: {} };
}

const state = reactive(load());

watch(
    state,
    () => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch {
            // Review schedules simply won't persist in this browser.
        }
    },
    { deep: true },
);

export const reviewCards = state.cards;

export function replaceCard(key, card) {
    state.cards[key] = card;
}

export function clearCards() {
    for (const key of Object.keys(state.cards)) {
        delete state.cards[key];
    }
}

export const itemKey = (kind, id) => `${kind}:${id}`;

/** Local midnight, `days` days after `now`, so a card is due all day rather than from a set minute. */
function startOfDayAfter(now, days) {
    const date = new Date(now);
    date.setHours(0, 0, 0, 0);
    date.setDate(date.getDate() + days);
    return date.getTime();
}

export function schedule(card, grade, now) {
    const current = card ?? { due: now, interval: 0, ease: STARTING_EASE, reps: 0, lapses: 0 };
    const ease = (change) => Math.max(MINIMUM_EASE, current.ease + change);

    if (grade === 'again') {
        return {
            due: now + AGAIN_DELAY_MS,
            interval: 0,
            ease: current.reps > 0 ? ease(-0.2) : current.ease,
            reps: 0,
            lapses: current.reps > 0 ? current.lapses + 1 : current.lapses,
        };
    }

    // New and relearning cards step up to a first interval; others grow from the last one.
    let interval;
    if (current.reps === 0) {
        interval = grade === 'easy' ? 4 : 1;
    } else if (grade === 'hard') {
        interval = Math.max(current.interval + 1, Math.round(current.interval * 1.2));
    } else if (grade === 'good') {
        interval = Math.max(current.interval + 1, Math.round(current.interval * current.ease));
    } else {
        interval = Math.max(current.interval + 1, Math.round(current.interval * current.ease * 1.3));
    }

    return {
        due: startOfDayAfter(now, interval),
        interval,
        ease: grade === 'hard' ? ease(-0.15) : grade === 'easy' ? ease(0.15) : current.ease,
        reps: current.reps + 1,
        lapses: current.lapses,
    };
}

export function gradeCard(key, grade, now = Date.now()) {
    state.cards[key] = schedule(state.cards[key], grade, now);
}

/** How long until a time, in words: "10 min", "1 day", "3 mo". */
export function describeWait(from, to) {
    const minutes = Math.max(1, Math.round((to - from) / 60_000));
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.round(minutes / 60);
    if (hours < 24) return `${hours} h`;
    const days = Math.round(hours / 24);
    if (days < 30) return `${days} ${days === 1 ? 'day' : 'days'}`;
    if (days < 365) return `${Math.round(days / 30)} mo`;
    return `${Math.round((days / 365) * 10) / 10} yr`;
}

/** When a due date falls, in words: "in 10 min" (later today), "tomorrow", "in 4 days". */
export function describeDue(now, due) {
    const days = Math.round((startOfDayAfter(due, 0) - startOfDayAfter(now, 0)) / 86_400_000);
    if (days <= 0) return `in ${describeWait(now, due)}`;
    if (days === 1) return 'tomorrow';
    return `in ${describeWait(0, days * 86_400_000)}`;
}

/** What a grade would mean for this card, for the label under each grade button: "10 min", "4 days". */
export function previewWait(card, grade, now) {
    const next = schedule(card, grade, now);
    // Due dates fall on midnight, so count whole days rather than hours until then.
    return next.interval > 0 ? describeWait(0, next.interval * 86_400_000) : describeWait(now, next.due);
}

/** Every quiz question answered correctly and every challenge passed, in lesson order. */
export function passedItems(lessons) {
    return Object.entries(lessons).flatMap(([lessonKey, progress]) => {
        const lessonId = Number(lessonKey);
        const quiz = Object.entries(progress.quiz ?? {})
            .filter(([, answer]) => answer.correct)
            .map(([id]) => ({ key: itemKey('quiz', Number(id)), kind: 'quiz', id: Number(id), lessonId }));
        const challenges = Object.entries(progress.challenges ?? {})
            .filter(([, challenge]) => challenge.passed)
            .map(([id]) => ({ key: itemKey('challenge', Number(id)), kind: 'challenge', id: Number(id), lessonId }));
        return [...quiz, ...challenges];
    });
}

/** Items due now: overdue reviews first (most overdue first), then items never reviewed. */
export function dueItems(items, cards, now) {
    const reviews = items
        .filter((item) => cards[item.key] && cards[item.key].due <= now)
        .sort((a, b) => cards[a.key].due - cards[b.key].due);
    const fresh = items.filter((item) => !cards[item.key]);
    return [...reviews, ...fresh];
}

/** When the next item that isn't due yet becomes due, or null. */
export function nextDue(items, cards, now) {
    const upcoming = items.map((item) => cards[item.key]?.due).filter((due) => due !== undefined && due > now);
    return upcoming.length ? Math.min(...upcoming) : null;
}
