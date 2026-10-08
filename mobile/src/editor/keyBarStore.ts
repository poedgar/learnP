import { create } from 'zustand';
import type { Language } from '../api/types';

/** The editor that has focus, so the one key bar above the keyboard can type into it. */
export interface ActiveEditor {
  id: string;
  language: Language;
  insert: (text: string, cursorOffset: number) => void;
}

interface KeyBarState {
  active: ActiveEditor | null;
  focus: (editor: ActiveEditor) => void;
  blur: (id: string) => void;
}

export const useKeyBarStore = create<KeyBarState>((set) => ({
  active: null,
  focus: (editor) => set({ active: editor }),
  blur: (id) => set((state) => (state.active?.id === id ? { active: null } : state)),
}));
