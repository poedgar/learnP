import { useRef, useState, type ReactNode } from 'react';
import { StyleSheet, Text, useColorScheme, View } from 'react-native';
import type { ConsoleEntry, FrameLanguage, JsCheck, JsRun } from '../api/types';
import { domProps } from '../editor/domProps';
import { useTheme } from '../theme';
import JsRunnerView, { type RunnerHandle } from './JsRunnerView';

type Language = FrameLanguage;
type Checks = { label: string; code?: string; compiles?: string; rejects?: string; width?: number }[];
type Pending = { resolve: (value: any) => void; reject: (error: Error) => void; timer: ReturnType<typeof setTimeout> };

/** Longer than the engine's own limits, so this only fires if the WebView itself is stuck. */
const ANSWER_TIMEOUT_MS = 30000;

export function apiBase(): string {
  return (process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000').replace(/\/+$/, '');
}

/**
 * Runs JavaScript and TypeScript on the device (see JsRunnerView). Render `view` once; it stays
 * one pixel tall until a run builds a page, which then shows in it. onLog receives what that page
 * logs later, such as after a tap.
 */
export function useJsRunner(language: Language, onLog?: (entry: ConsoleEntry) => void): {
  view: ReactNode;
  run: (code: string) => Promise<JsRun>;
  check: (code: string, checks: Checks) => Promise<JsCheck>;
  hidePage: () => void;
} {
  const theme = useTheme();
  const dark = useColorScheme() === 'dark';
  const handle = useRef<RunnerHandle>(null);
  const pending = useRef(new Map<number, Pending>());
  const nextId = useRef(1);
  const [showPage, setShowPage] = useState(false);
  const logListener = useRef(onLog);
  logListener.current = onLog;

  async function send(request: object): Promise<any> {
    // The WebView sets up its handle once it has loaded; wait for it briefly.
    for (let tries = 0; !handle.current && tries < 50; tries++) {
      await new Promise((resolve) => setTimeout(resolve, 100));
    }
    if (!handle.current) throw new Error('The JavaScript sandbox did not start. Try again.');

    const id = nextId.current++;
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        pending.current.delete(id);
        reject(new Error('The JavaScript sandbox stopped answering. Try again.'));
      }, ANSWER_TIMEOUT_MS);
      pending.current.set(id, { resolve, reject, timer });
      handle.current!.start(JSON.stringify({ id, language, ...request }));
    });
  }

  async function onResult(json: string) {
    const { id, ok, result, error } = JSON.parse(json);
    const request = pending.current.get(id);
    if (!request) return;
    pending.current.delete(id);
    clearTimeout(request.timer);
    if (ok) request.resolve(result);
    else request.reject(new Error(error));
  }

  const props = { apiBase: apiBase(), dark };

  const view = (
    <View style={showPage ? styles.page : styles.hidden} accessible={false}>
      {showPage ? <Text style={[styles.heading, { color: theme.ink }]}>Your page (it's live: tap and type in it)</Text> : null}
      <View style={[styles.frame, showPage ? { borderColor: theme.line, borderWidth: 1 } : null]}>
        <JsRunnerView
          ref={handle}
          {...props}
          onResult={onResult}
          onLog={async (entry) => logListener.current?.(JSON.parse(entry))}
          dom={domProps(['onResult', 'onLog'], props)}
        />
      </View>
    </View>
  );

  return {
    view,
    async run(code) {
      setShowPage(false);
      const result: JsRun = await send({ kind: 'run', code });
      setShowPage(result.hasPage);
      return result;
    },
    async check(code, checks) {
      setShowPage(false);
      return send({ kind: 'check', code, checks });
    },
    hidePage: () => setShowPage(false),
  };
}

const styles = StyleSheet.create({
  hidden: { height: 1, opacity: 0, overflow: 'hidden' },
  page: { gap: 6 },
  heading: { fontSize: 15, fontWeight: '700' },
  frame: { height: 300, borderRadius: 8, overflow: 'hidden' },
});
