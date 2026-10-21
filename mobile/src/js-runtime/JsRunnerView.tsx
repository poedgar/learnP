'use dom';

import { useDOMImperativeHandle, type DOMImperativeFactory, type DOMProps } from 'expo/dom';
import { useEffect, useRef, useState, type Ref } from 'react';
import { createJsEngine, frameHtml, typescriptWorkerScript } from './engine';
import { acornSource, frameRuntimeSource, loopGuardSource, tsServiceSource } from './sources';
import { TS_VERSION } from './ts-version';

/**
 * Runs learner JavaScript and TypeScript on the device, in a WebView (an Expo DOM component),
 * with the same engine as the web app (copied from app/resources/js/lib/js). Each run gets a
 * fresh sandboxed iframe; TypeScript is compiled in a worker that loads the compiler from the API
 * server (/vendor/typescript/<version>/).
 *
 * Native sends a request with start(json): { id, kind: 'run' | 'check', language, code, checks }.
 * The answer comes back through onResult(json): { id, ok, result } or { id, ok: false, error }.
 * A run that builds a page keeps its frame visible here; what it logs later arrives via onLog.
 */
export interface RunnerHandle {
  start(request: string): void;
}

type Props = {
  apiBase: string;
  dark: boolean;
  onResult: (json: string) => Promise<void>;
  onLog: (json: string) => Promise<void>;
  ref?: Ref<RunnerHandle>;
  dom?: DOMProps;
};

type Frame = { send(message: unknown): void; onMessage(callback: (message: any) => void): void; close(): void };
type Pending = { resolve: (value: any) => void; reject: (error: Error) => void };

const html = frameHtml({ runtime: frameRuntimeSource, loopGuard: loopGuardSource, acorn: acornSource });

export default function JsRunnerView({ apiBase, dark, onResult, onLog, ref }: Props) {
  const page = useRef<HTMLDivElement>(null);
  const pool = useRef<HTMLDivElement>(null);
  const shown = useRef<Frame | null>(null);
  const callbacks = useRef({ onResult, onLog });
  useEffect(() => {
    callbacks.current = { onResult, onLog };
  });

  useEffect(() => {
    document.body.style.margin = '0';
    document.body.style.background = dark ? '#282c34' : '#ffffff';
  }, [dark]);

  // Created once; the frames themselves are only opened when code runs.
  const [engines] = useState(() => {
    // Hidden frames are 800 × 600 CSS pixels (layout checks depend on it), or as wide as a check asks.
    const openFrame = (host: HTMLElement | null, width: number | null = null): Promise<Frame> =>
      new Promise((resolve) => {
        const iframe = document.createElement('iframe');
        iframe.setAttribute('sandbox', 'allow-scripts allow-forms');
        iframe.title = 'The page your code built';
        iframe.srcdoc = html;
        iframe.style.cssText = host
          ? 'display:block;width:100%;height:100%;border:0;background:#fff;'
          : `display:block;width:${width ?? 800}px;height:600px;border:0;`;
        let callback: (message: any) => void = () => {};
        const listener = (event: MessageEvent) => {
          if (event.source === iframe.contentWindow) callback(event.data);
        };
        window.addEventListener('message', listener);
        iframe.addEventListener(
          'load',
          () =>
            resolve({
              send: (message) => iframe.contentWindow?.postMessage(message, '*'),
              onMessage: (next) => (callback = next),
              close: () => {
                window.removeEventListener('message', listener);
                iframe.remove();
              },
            }),
          { once: true },
        );
        (host ?? pool.current ?? document.body).append(iframe);
      });

    let worker: Worker | null = null;
    let nextId = 1;
    const pending = new Map<number, Pending>();
    const typescript = {
      compile(code: string, snippets: unknown[] = []) {
        if (!worker) {
          const base = `${apiBase.replace(/\/+$/, '')}/vendor/typescript/${TS_VERSION}/`;
          const script = typescriptWorkerScript(tsServiceSource, [`${base}typescript.js`, `${base}libs.js`]);
          worker = new Worker(URL.createObjectURL(new Blob([script], { type: 'text/javascript' })));
          worker.onmessage = ({ data }) => {
            const request = pending.get(data.id);
            if (!request) return;
            pending.delete(data.id);
            if (data.ok) request.resolve(data.result);
            else request.reject(new Error(data.error));
          };
          worker.onerror = (event) => {
            const error = new Error(`TypeScript could not start: ${event.message || 'the compiler did not load. Check your connection.'}`);
            for (const request of pending.values()) request.reject(error);
            pending.clear();
            worker = null;
          };
        }
        const id = nextId++;
        return new Promise((resolve, reject) => {
          pending.set(id, { resolve, reject });
          worker!.postMessage({ id, code, snippets });
        });
      },
    };

    return {
      hidden: createJsEngine({ openFrame: ({ width }) => openFrame(null, width), typescript }),
      visible: createJsEngine({ openFrame: () => openFrame(page.current), typescript }),
    };
  });

  // DOMImperativeFactory types every method as (...args: JSONValue[]); the handle keeps its own, narrower types.
  useDOMImperativeHandle(
    ref as Ref<DOMImperativeFactory>,
    () => ({
      start(...args) {
        const [json] = args as Parameters<RunnerHandle['start']>;
        const request = JSON.parse(json);
        const reply = (body: object) => callbacks.current.onResult(JSON.stringify({ id: request.id, ...body }));
        const { hidden, visible } = engines;

        shown.current?.close();
        shown.current = null;

        const work =
          request.kind === 'run'
            ? visible.run(request.code, { language: request.language, keepFrame: true }).then((result: any) => {
                const { frame, ...rest } = result;
                if (frame) {
                  shown.current = frame;
                  frame.onMessage((message: any) => {
                    if (message?.type === 'log') callbacks.current.onLog(JSON.stringify(message.entry));
                  });
                }
                return rest;
              })
            : hidden.check(request.code, request.checks, { language: request.language });

        work.then(
          (result: object) => reply({ ok: true, result }),
          (error: Error) => reply({ ok: false, error: error?.message ?? String(error) }),
        );
      },
    }),
    [],
  );

  return (
    <div style={{ height: '100vh', position: 'relative' }}>
      <div ref={page} style={{ height: '100%' }} />
      <div
        ref={pool}
        aria-hidden="true"
        style={{ position: 'fixed', left: -10000, top: 0, width: 1600, height: 600, overflow: 'hidden' }}
      />
    </div>
  );
}
