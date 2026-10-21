import { reactive, watch } from 'vue';

/*
 * What the home page's dashboard needs beyond lesson progress: the lesson opened most recently,
 * and the days on which any lesson was opened (for the streak). Kept in this browser only.
 * Shape: { recent: { course, courseTitle, lesson, lessonTitle } | null, days: ['2026-10-21', ...] }
 */
const STORAGE_KEY = 'learnp:activity:v1';
const KEEP_DAYS = 400;

function load() {
    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
        if (saved && Array.isArray(saved.days)) return saved;
    } catch {
        // Storage can be unavailable or hold invalid JSON; start fresh.
    }
    return { recent: null, days: [] };
}

const state = reactive(load());

watch(
    state,
    () => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch {
            // The dashboard simply starts empty next time.
        }
    },
    { deep: true },
);

export const activity = state;

/** A local calendar day as YYYY-MM-DD. */
export function dayKey(date) {
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

export function recordVisit(course, lesson, now = new Date()) {
    state.recent = { course: course.slug, courseTitle: course.title, lesson: lesson.slug, lessonTitle: lesson.title };
    const today = dayKey(now);
    if (!state.days.includes(today)) {
        state.days.push(today);
        state.days.splice(0, Math.max(0, state.days.length - KEEP_DAYS));
    }
}

/** Days in a row with activity, ending today, or yesterday if today hasn't started yet. */
export function streak(days, now = new Date()) {
    const set = new Set(days);
    const day = new Date(now);
    if (!set.has(dayKey(day))) day.setDate(day.getDate() - 1);

    let count = 0;
    while (set.has(dayKey(day))) {
        count++;
        day.setDate(day.getDate() - 1);
    }
    return count;
}
