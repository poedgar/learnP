import { useImperativeHandle, type Ref } from 'react';
import type { RunnerHandle } from '../JsRunnerView';

/**
 * Jest stand-in for the WebView runner. Tests set what a request returns with
 * jest.requireMock('…/js-runtime/JsRunnerView').answer.mockImplementation(...).
 */
export const answer = jest.fn((request: { kind: string; code: string }) =>
  request.kind === 'run'
    ? { entries: [{ level: 'log', text: `ran: ${request.code}` }], output: `ran: ${request.code}`, error: null, typeErrors: [], stillRunning: false, durationMs: 1, hasPage: false }
    : { passed: true, loaded: true, error: null, output: '', entries: [], checks: [] },
);

export default function JsRunnerView({
  onResult,
  ref,
}: {
  onResult: (json: string) => Promise<void>;
  ref?: Ref<RunnerHandle>;
}) {
  useImperativeHandle(ref, () => ({
    start(json: string) {
      const request = JSON.parse(json);
      Promise.resolve()
        .then(() => answer(request))
        .then(
          (result) => onResult(JSON.stringify({ id: request.id, ok: true, result })),
          (error: Error) => onResult(JSON.stringify({ id: request.id, ok: false, error: error.message })),
        );
    },
  }));

  return null;
}
