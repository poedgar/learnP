import { useRef, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { api } from '../../api/client';
import { runsInFrame, type Challenge, type JsCheck, type JsRun, type Language, type PhpCheck, type PhpRun, type SqlCheck, type SqlRun, type GitCheck, type GitRun } from '../../api/types';
import { CodeEditor } from '../../editor/CodeEditor';
import { checkGit, runGit } from '../../git';
import { useJsRunner } from '../../js-runtime/useJsRunner';
import { phpCheckMessage } from '../../lesson/php';
import type { Card, Grade } from '../../review/schedule';
import { useTheme } from '../../theme';
import { Button } from '../Button';
import { Markdown } from '../markdown/Markdown';
import { PhpCheckDetails } from '../results/PhpCheckDetails';
import { GitOutput } from '../results/GitOutput';
import { JsOutput } from '../results/JsOutput';
import { PhpOutput } from '../results/PhpOutput';
import { RunResults } from '../results/RunResults';
import { SqlCheckDetails } from '../results/SqlCheckDetails';
import { GradeButtons } from './GradeButtons';

type Props = {
  challenge: Challenge;
  lessonId: number;
  language: Language;
  card: Card | undefined;
  onGrade: (grade: Grade) => void;
  onEditorFocus?: () => void;
};

type RunView = { kind: 'sql'; run: SqlRun } | { kind: 'php'; run: PhpRun } | { kind: 'js'; run: JsRun } | { kind: 'git'; run: GitRun };
type CheckView =
  | { kind: 'sql'; check: SqlCheck; message: string }
  | { kind: 'php'; check: PhpCheck; message: string }
  | { kind: 'js'; check: JsCheck; message: string }
  | { kind: 'git'; check: GitCheck; message: string };

/**
 * A challenge solved again from its starter code, checked exactly as in the lesson.
 * Nothing here touches the lesson's saved code or progress.
 */
export function ReviewChallengeCard({ challenge, lessonId, language, card, onGrade, onEditorFocus }: Props) {
  const theme = useTheme();
  const code = useRef(challenge.starter_code ?? '');
  const working = useRef(false);
  const [busy, setBusy] = useState<'run' | 'check' | null>(null);
  const [run, setRun] = useState<RunView | null>(null);
  const [check, setCheck] = useState<CheckView | null>(null);
  const [failure, setFailure] = useState('');
  const [showHint, setShowHint] = useState(false);
  const passed = check?.check.passed ?? false;
  const javascript = runsInFrame(language);
  const runner = useJsRunner(javascript ? language : 'javascript', (entry) =>
    setRun((current) => (current?.kind === 'js' ? { kind: 'js', run: { ...current.run, entries: [...current.run.entries, entry] } } : current)),
  );

  async function work(kind: 'run' | 'check') {
    if (working.current) return;
    working.current = true;
    const submitted = code.current;
    setBusy(kind);
    setFailure('');
    setRun(null);
    setCheck(null);
    try {
      if (kind === 'run') {
        setRun(
          language === 'sql'
            ? { kind: 'sql', run: await api.runSql(lessonId, submitted) }
            : language === 'git'
              ? { kind: 'git', run: runGit(submitted) }
            : javascript
              ? { kind: 'js', run: await runner.run(submitted) }
              : { kind: 'php', run: await api.runPhp(lessonId, submitted) },
        );
      } else if (language === 'git') {
        const result = checkGit(submitted, challenge.checks ?? []);
        setCheck({ kind: 'git', check: result, message: phpCheckMessage(result) });
      } else if (javascript) {
        const result = await runner.check(submitted, challenge.checks ?? []);
        setCheck({ kind: 'js', check: result, message: phpCheckMessage(result) });
      } else if (language === 'sql') {
        const result = await api.checkSql(challenge.id, submitted);
        setCheck({ kind: 'sql', check: result, message: result.message });
      } else {
        const result = await api.checkPhp(challenge.id, submitted);
        setCheck({ kind: 'php', check: result, message: phpCheckMessage(result) });
      }
    } catch (e) {
      setFailure(e instanceof Error ? e.message : 'Something went wrong. Try again.');
    } finally {
      working.current = false;
      setBusy(null);
    }
  }

  return (
    <View style={styles.card}>
      <Text accessibilityRole="header" style={[styles.title, { color: theme.ink }]}>
        {challenge.title}
      </Text>
      <Markdown source={challenge.prompt} />
      {challenge.order_matters ? (
        <Text style={[styles.note, { color: theme.muted }]}>Row order is checked for this challenge.</Text>
      ) : null}
      {challenge.hint ? (
        <View style={styles.hint}>
          <Button variant="quiet" title={showHint ? 'Hide hint' : 'Show a hint'} onPress={() => setShowHint(!showHint)} />
          {showHint ? <Text style={[styles.text, { color: theme.ink }]}>{challenge.hint}</Text> : null}
        </View>
      ) : null}

      <CodeEditor
        value={challenge.starter_code ?? ''}
        resetKey={0}
        language={language}
        onChange={(value) => (code.current = value)}
        label={`Code for challenge: ${challenge.title}`}
        onFocus={onEditorFocus}
      />

      {passed ? null : (
        <View style={styles.actions}>
          <Button title={busy === 'run' ? 'Running…' : 'Run'} onPress={() => work('run')} disabled={busy !== null} />
          <Button
            variant="primary"
            title={busy === 'check' ? 'Checking…' : 'Check answer'}
            onPress={() => work('check')}
            disabled={busy !== null}
          />
          <Button variant="quiet" title="I don't remember" onPress={() => onGrade('again')} />
        </View>
      )}

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
              { backgroundColor: passed ? theme.okSoft : theme.badSoft, borderColor: passed ? theme.ok : theme.bad },
            ]}
          >
            <Text style={[styles.verdictText, { color: theme.ink }]}>{check.message}</Text>
          </View>
          {passed ? <GradeButtons card={card} grades={['hard', 'good', 'easy']} onGrade={onGrade} /> : null}
          {check.kind === 'sql' ? <SqlCheckDetails check={check.check} /> : <PhpCheckDetails check={check.check} git={check.kind === 'git'} />}
        </View>
      ) : null}

      {run?.kind === 'sql' ? <RunResults run={run.run} /> : null}
      {run?.kind === 'php' ? <PhpOutput {...run.run} /> : null}
      {run?.kind === 'js' ? <JsOutput {...run.run} quiet={language === 'html'} /> : null}
      {run?.kind === 'git' ? <GitOutput entries={run.run.entries} /> : null}
      {javascript ? runner.view : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: { gap: 14 },
  text: { fontSize: 16, lineHeight: 24 },
  title: { fontSize: 22, fontWeight: '800' },
  note: { fontSize: 14 },
  hint: { gap: 6, alignItems: 'flex-start' },
  actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  result: { gap: 12 },
  verdict: { borderWidth: 1, borderRadius: 8, padding: 12 },
  verdictText: { fontSize: 16, fontWeight: '700', lineHeight: 23 },
});
