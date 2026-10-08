'use dom';

import { php } from '@codemirror/lang-php';
import { sql } from '@codemirror/lang-sql';
import { EditorState } from '@codemirror/state';
import { oneDark } from '@codemirror/theme-one-dark';
import { EditorView, basicSetup } from 'codemirror';
import { useDOMImperativeHandle, type DOMImperativeFactory, type DOMProps } from 'expo/dom';
import { useEffect, useRef, type Ref } from 'react';

/**
 * CodeMirror 6 running in a WebView (an Expo DOM component). Text only flows
 * out, through onChange; the native side replaces the text by changing
 * resetKey. That way fast typing can never be overwritten by a stale value
 * coming back across the bridge.
 */
export interface EditorHandle {
  insert(text: string, cursorOffset: number): void;
  focus(): void;
}

type Props = {
  initialValue: string;
  resetKey: number;
  language: 'sql' | 'php';
  dark: boolean;
  onChange: (value: string) => Promise<void>;
  onFocusChange: (focused: boolean) => Promise<void>;
  /** Names the editor for screen readers (and for the Jest stand-in). */
  accessibilityLabel?: string;
  ref?: Ref<EditorHandle>;
  dom?: DOMProps;
};

export default function CodeMirrorView({
  initialValue,
  resetKey,
  language,
  dark,
  onChange,
  onFocusChange,
  accessibilityLabel,
  ref,
}: Props) {
  const host = useRef<HTMLDivElement>(null);
  const view = useRef<EditorView | null>(null);
  const latest = useRef(initialValue);
  const appliedReset = useRef<number | null>(null);
  const callbacks = useRef({ onChange, onFocusChange });
  callbacks.current = { onChange, onFocusChange };

  useEffect(() => {
    document.body.style.margin = '0';
    document.body.style.background = dark ? '#282c34' : '#ffffff';

    // A new resetKey means new text from native; a theme or language change keeps what's typed.
    const doc = appliedReset.current === resetKey ? latest.current : initialValue;
    appliedReset.current = resetKey;
    latest.current = doc;

    const editor = new EditorView({
      parent: host.current!,
      state: EditorState.create({
        doc,
        extensions: [
          basicSetup,
          EditorView.lineWrapping,
          language === 'php' ? php() : sql(),
          dark ? oneDark : [],
          EditorView.theme({
            '&': { height: '100vh', fontSize: '15px' },
            '.cm-scroller': { fontFamily: 'Menlo, Consolas, monospace' },
          }),
          EditorView.updateListener.of((update) => {
            if (update.docChanged) {
              latest.current = update.state.doc.toString();
              callbacks.current.onChange(latest.current);
            }
            if (update.focusChanged) {
              callbacks.current.onFocusChange(update.view.hasFocus);
            }
          }),
        ],
      }),
    });
    view.current = editor;

    return () => {
      editor.destroy();
      view.current = null;
    };
    // initialValue is read only when resetKey changes, on purpose (see the comment above).
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [resetKey, language, dark]);

  // DOMImperativeFactory types every method as (...args: JSONValue[]); the handle keeps its own, narrower types.
  useDOMImperativeHandle(
    ref as Ref<DOMImperativeFactory>,
    () => ({
      insert(...args) {
        const [text, cursorOffset] = args as Parameters<EditorHandle['insert']>;
        const editor = view.current;
        if (!editor) return;
        const { from, to } = editor.state.selection.main;
        editor.dispatch({ changes: { from, to, insert: text }, selection: { anchor: from + cursorOffset } });
        editor.focus();
      },
      focus() {
        view.current?.focus();
      },
    }),
    [],
  );

  return <div ref={host} aria-label={accessibilityLabel} style={{ height: '100vh' }} />;
}
