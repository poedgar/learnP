import { useRef, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { api } from '../../api/client';
import { runsInFrame, type Challenge, type JsCheck, type JsRun, type Language, type Lesson, type PhpCheck, type PhpRun, type SqlCheck, type SqlRun } from '../../api/types';
import { CodeEditor } from '../../editor/CodeEditor';
import { useJsRunner } from '../../js-runtime/useJsRunner';
import { phpCheckMessage } from '../../lesson/php';
import { updateLesson, useLessonProgress, useProgressStore } from '../../progress/store';
import { useTheme } from '../../theme';
import { Button } from '../Button';
import { Markdown } from '../markdown/Markdown';
import { PhpCheckDetails } from '../results/PhpCheckDetails';
import { JsOutput } from '../results/JsOutput';
import { PhpOutput } from '../results/PhpOutput';
import { RunResults } from '../results/RunResults';
import { SqlCheckDetails } from '../results/SqlCheckDetails';

type Props = { lesson: Lesson; language: Language };

type RunView = { kind: 'sql'; run: SqlRun } | { kind: 'php'; run: PhpRun } | { kind: 'js'; run: JsRun };
type CheckView =
  | { kind: 'sql'; check: SqlCheck; message: string }
  | { kind: 'php'; check: PhpCheck; message: string }
  | { kind: 'js'; check: JsCheck; message: string };

function savedCode(lessonId: number, challenge: Challenge): string {
  return useProgressStore.getState().lessons[lessonId]?.challenges[challenge.id]?.code ?? challenge.starter_code ?? '';
}

/** Tasks checked on the server: SQL against a reference result, PHP against the challenge's checks. */
export function ChallengesStep({ lesson, language }: Props) {
  const theme = useTheme();
  const challenges = lesson.challenges;
  const progress = useLessonProgress(lesson.id);
  const [index, setIndex] = useState(() => {
    const first = challenges.findIndex((c) => !progress.challenges[c.id]?.passed);
    return first === -1 ? 0 : first;
  });
  const [editor, setEditor] = useState(() => ({
    value: challenges.length ? savedCode(lesson.id, challenges[index]) : '',
    key: 0,
  }));
  const code = useRef(editor.value);
  const working = useRef(false);
  const [busy, setBusy] = useState<'run' | 'check' | null>(null);
  const [run, setRun] = useState<RunView | null>(null);
  const [check, setCheck] = useState<CheckView | null>(null);
  const [failure, setFailure] = useState('');
  const [showHint, setShowHint] = useState(false);
  const scroll = useRef<ScrollView>(null);
  const editorY = useRef(0);
  const shownId = useRef<number | null>(null);
  const javascript = runsInFrame(language);
  const runner = useJsRunner(javascript ? language : 'javascript', (entry) =>
    setRun((current) => (current?.kind === 'js' ? { kind: 'js', run: { ...current.run, entries: [...current.run.entries, entry] } } : current)),
  );

  if (challenges.length === 0) {
    return (
      <View style={styles.page}>
        <Text style={[styles.text, { color: theme.ink }]}>This lesson has no challenges yet.</Text>
      </View>
    );
  }

  const active = challenges[index];
  shownId.current = active.id;
  const solved = challenges.filter((c) => progress.challenges[c.id]?.passed).length;
  const order = [...challenges.slice(index + 1), ...challenges.slice(0, index)];
  const nextUnsolved = order.find((c) => !progress.challenges[c.id]?.passed) ?? null;

  function save(value: string) {
    code.current = value;
    updateLesson(lesson.id, (current) => ({
      challenges: {
        ...current.challenges,
        [active.id]: { passed: current.challenges[active.id]?.passed ?? false, code: value },
      },
    }));
  }

  function load(value: string) {
    code.current = value;
    setEditor((current) => ({ value, key: current.key + 1 }));
  }

  function open(challenge: Challenge) {
    setIndex(challenges.indexOf(challenge));
    load(savedCode(lesson.id, challenge));
    setRun(null);
    setCheck(null);
    setFailure('');
    setShowHint(false);
    runner.hidePage();
  }

  /** Marks a challenge solved. Its code is already saved on every edit, so only fill it in if it never was. */
  function markSolved(challenge: Challenge, submitted: string) {
    updateLesson(lesson.id, (current) => ({
      challenges: {
        ...current.challenges,
        [challenge.id]: { passed: true, code: current.challenges[challenge.id]?.code ?? submitted },
      },
    }));
  }

  async function work(kind: 'run' | 'check') {
    if (working.current) return;
    working.current = true;
    // The learner can switch challenges while this is on its way, so pin what was sent.
    const challenge = active;
    const submitted = code.current;
    const stillShown = () => shownId.current === challenge.id;
    setBusy(kind);
    setFailure('');
    setRun(null);
    setCheck(null);
    try {
      if (kind === 'run') {
        const view: RunView =
          language === 'sql'
            ? { kind: 'sql', run: await api.runSql(lesson.id, submitted) }
            : javascript
              ? { kind: 'js', run: await runner.run(submitted) }
              : { kind: 'php', run: await api.runPhp(lesson.id, submitted) };
        if (stillShown()) setRun(view);
      } else if (javascript) {
        const result = await runner.check(submitted, challenge.checks ?? []);
        if (result.passed) markSolved(challenge, submitted);
        if (stillShown()) setCheck({ kind: 'js', check: result, message: phpCheckMessage(result) });
      } else if (language === 'sql') {
        const result = await api.checkSql(challenge.id, submitted);
        if (result.passed) markSolved(challenge, submitted);
        if (stillShown()) setCheck({ kind: 'sql', check: result, message: result.message });
      } else {
        const result = await api.checkPhp(challenge.id, submitted);
        if (result.passed) markSolved(challenge, submitted);
        if (stillShown()) setCheck({ kind: 'php', check: result, message: phpCheckMessage(result) });
      }
    } catch (e) {
      if (stillShown()) setFailure(e instanceof Error ? e.message : 'Something went wrong. Try again.');
    } finally {
      working.current = false;
      setBusy(null);
    }
  }

  return (
    <ScrollView ref={scroll} contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Text style={[styles.solved, { color: theme.muted }]}>{`${solved} of ${challenges.length} solved`}</Text>
      <View style={styles.chips}>
        {challenges.map((challenge, i) => {
          const passed = progress.challenges[challenge.id]?.passed ?? false;
          return (
            <Pressable
              key={challenge.id}
              accessibilityRole="button"
              accessibilityLabel={`Challenge ${i + 1}: ${challenge.title}`}
              accessibilityState={{ selected: i === index }}
              onPress={() => open(challenge)}
              style={[
                styles.chip,
                {
                  borderColor: i === index ? theme.ink : passed ? theme.ok : theme.line,
                  backgroundColor: passed ? theme.okSoft : theme.surface,
                },
              ]}
            >
              <Text style={[styles.chipText, { color: passed ? theme.ok : theme.ink }]}>{passed ? '✓' : i + 1}</Text>
            </Pressable>
          );
        })}
      </View>

      <Text accessibilityRole="header" style={[styles.title, { color: theme.ink }]}>
        {active.title}
      </Text>
      <Markdown source={active.prompt} />
      {active.order_matters ? (
        <Text style={[styles.note, { color: theme.muted }]}>Row order is checked for this challenge.</Text>
      ) : null}
      {active.hint ? (
        <View style={styles.hint}>
          <Button variant="quiet" title={showHint ? 'Hide hint' : 'Show a hint'} onPress={() => setShowHint(!showHint)} />
          {showHint ? <Text style={[styles.text, { color: theme.ink }]}>{active.hint}</Text> : null}
        </View>
      ) : null}

      <View onLayout={(event) => (editorY.current = event.nativeEvent.layout.y)}>
        <CodeEditor
          value={editor.value}
          resetKey={editor.key}
          language={language}
          onChange={(value) => save(value)}
          label={`Code for challenge: ${active.title}`}
          onFocus={() => scroll.current?.scrollTo({ y: Math.max(editorY.current - 8, 0), animated: true })}
        />
      </View>
      <View style={styles.actions}>
        <Button title={busy === 'run' ? 'Running…' : 'Run'} onPress={() => work('run')} disabled={busy !== null} />
        <Button
          variant="primary"
          title={busy === 'check' ? 'Checking…' : 'Check answer'}
          onPress={() => work('check')}
          disabled={busy !== null}
        />
        <Button
          variant="quiet"
          title="Start over"
          onPress={() => {
            save(active.starter_code ?? '');
            load(active.starter_code ?? '');
            setRun(null);
            setCheck(null);
            runner.hidePage();
          }}
        />
      </View>

      {failure ? (
        <Text accessibilityRole="alert" style={[styles.text, { color: theme.bad }]}>
          {failure}
        </Text>
      ) : null}

      {check ? (
        <View style={styles.result}>
          <View
            style={[
              styles.verdict,
              {
                backgroundColor: check.check.passed ? theme.okSoft : theme.badSoft,
                borderColor: check.check.passed ? theme.ok : theme.bad,
              },
            ]}
          >
            <Text style={[styles.verdictText, { color: theme.ink }]}>{check.message}</Text>
            {check.check.passed && nextUnsolved ? (
              <Button variant="primary" title="Next challenge" onPress={() => open(nextUnsolved)} />
            ) : null}
            {check.check.passed && !nextUnsolved ? (
              <Text style={[styles.text, { color: theme.ink }]}>You've solved every challenge in this lesson.</Text>
            ) : null}
          </View>
          {check.kind === 'sql' ? <SqlCheckDetails check={check.check} /> : <PhpCheckDetails check={check.check} />}
        </View>
      ) : null}

      {run?.kind === 'sql' ? <RunResults run={run.run} /> : null}
      {run?.kind === 'php' ? <PhpOutput {...run.run} /> : null}
      {run?.kind === 'js' ? <JsOutput {...run.run} quiet={language === 'html'} /> : null}
      {javascript ? runner.view : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 14, paddingBottom: 120 },
  text: { fontSize: 16, lineHeight: 24 },
  solved: { fontSize: 14 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: { width: 40, height: 40, borderRadius: 20, borderWidth: 2, alignItems: 'center', justifyContent: 'center' },
  chipText: { fontSize: 15, fontWeight: '700' },
  title: { fontSize: 22, fontWeight: '800' },
  note: { fontSize: 14 },
  hint: { gap: 6, alignItems: 'flex-start' },
  actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  result: { gap: 12 },
  verdict: { borderWidth: 1, borderRadius: 8, padding: 12, gap: 10 },
  verdictText: { fontSize: 16, fontWeight: '700', lineHeight: 23 },
});
