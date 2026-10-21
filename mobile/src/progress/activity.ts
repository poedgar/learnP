import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';

/**
 * What the home screen's dashboard needs beyond lesson progress: the lesson opened most
 * recently, and the days on which any lesson was opened (for the streak). Kept on this device.
 * The same rules as the web app's resources/js/activity.js.
 */
export interface RecentLesson {
  course: string;
  courseTitle: string;
  lesson: string;
  lessonTitle: string;
}

interface ActivityState {
  recent: RecentLesson | null;
  days: string[];
}

const KEEP_DAYS = 400;

export const useActivityStore = create<ActivityState>()(
  persist((): ActivityState => ({ recent: null, days: [] }), {
    name: 'learnp:activity:v1',
    storage: createJSONStorage(() => AsyncStorage),
  }),
);

/** A local calendar day as YYYY-MM-DD. */
export function dayKey(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

export function recordVisit(recent: RecentLesson, now = new Date()): void {
  useActivityStore.setState((state) => {
    const today = dayKey(now);
    const days = state.days.includes(today) ? state.days : [...state.days, today].slice(-KEEP_DAYS);
    return { recent, days };
  });
}

/** Days in a row with activity, ending today, or yesterday if today hasn't started yet. */
export function streak(days: string[], now = new Date()): number {
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
