import { useEffect, useRef, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { api } from '../../api/client';
import { runsInFrame, LANGUAGE_NAMES, type JsRun, type Language, type Lesson, type PhpRun, type SchemaTable, type SqlRun } from '../../api/types';
import { CodeEditor } from '../../editor/CodeEditor';
import { useJsRunner } from '../../js-runtime/useJsRunner';
import { withOpeningTag } from '../../lesson/php';
import { updateLesson, useLessonProgress } from '../../progress/store';
import { useTheme } from '../../theme';
import { Button } from '../Button';
import { JsOutput } from '../results/JsOutput';
import { PhpOutput } from '../results/PhpOutput';
import { RunResults } from '../results/RunResults';
import { SchemaPanel } from '../results/SchemaPanel';

type Props = {
  lesson: Lesson;
  language: Language;
  schema: SchemaTable[];
  /** Code sent over from a "Try it" button. A new key replaces the editor's code and runs it. */
  request: { code: string; key: number } | null;
};

type Result = { kind: 'sql'; run: SqlRun } | { kind: 'php'; run: PhpRun } | { kind: 'js'; run: JsRun };

export function SandboxStep({ lesson, language, schema, request }: Props) {
  const theme = useTheme();
  const progress = useLessonProgress(lesson.id);
  const [editor, setEditor] = useState(() => ({ value: progress.sandboxCode ?? lesson.starter_code ?? '', key: 0 }));
  const code = useRef(editor.value);
  const running = useRef(false);
  const [busy, setBusy] = useState(false);
  const [result, setResult] = useState<Result | null>(null);
  const [failure, setFailure] = useState('');
  const scroll = useRef<ScrollView>(null);
  const editorY = useRef(0);
  const javascript = runsInFrame(language);
  const runner = useJsRunner(javascript ? language : 'javascript', (entry) =>
    setResult((current) => (current?.kind === 'js' ? { kind: 'js', run: { ...current.run, entries: [...current.run.entries, entry] } } : current)),
  );

  function save(value: string) {
    code.current = value;
    updateLesson(lesson.id, () => ({ sandboxCode: value }));
  }

  function load(value: string) {
    save(value);
    setEditor((current) => ({ value, key: current.key + 1 }));
  }

  async function run() {
    if (running.current) return;
    running.current = true;
    setBusy(true);
    setFailure('');
    try {
      setResult(
        language === 'sql'
          ? { kind: 'sql', run: await api.runSql(lesson.id, code.current) }
          : javascript
            ? { kind: 'js', run: await runner.run(code.current) }
            : { kind: 'php', run: await api.runPhp(lesson.id, code.current) },
      );
    } catch (e) {
      setResult(null);
      setFailure(e instanceof Error ? e.message : 'Something went wrong. Try again.');
    } finally {
      running.current = false;
      setBusy(false);
    }
  }

  useEffect(() => {
    if (request) {
      // A "Try it" request from another step is an outside event: load its code, then run it.
      // eslint-disable-next-line react-hooks/set-state-in-effect
      load(language === 'php' ? withOpeningTag(request.code) : request.code);
      run();
    }
    // Only a new request should load code; language and lesson are fixed for this step.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [request?.key]);

  return (
    <ScrollView ref={scroll} contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Text style={[styles.intro, { color: theme.muted }]}>
        {language === 'sql'
          ? "Write any SQL you like: SELECT, INSERT, UPDATE, even DROP TABLE. Every run starts from a fresh copy of the data, so you can't break anything."
          : language === 'html'
            ? 'Write HTML, with CSS in a <style> element, and press Run to see the page. It runs on this device, in a sandbox.'
            : javascript
            ? `Write any ${LANGUAGE_NAMES[language]} you like and press Run. It runs on this device, in a sandbox. Use console.log() to print values.${
                language === 'typescript' ? " TypeScript checks your types first; code with type errors doesn't run." : ''
              }`
            : 'Write any PHP you like and press Run. It runs in a sandbox on the server. Use echo to print values.'}
      </Text>
      {language === 'sql' ? (
        <SchemaPanel
          tables={schema}
          onPreview={(table) => {
            load(`SELECT *\nFROM ${table}\nLIMIT 10;`);
            run();
          }}
        />
      ) : null}
      <View onLayout={(event) => (editorY.current = event.nativeEvent.layout.y)}>
        <CodeEditor
          value={editor.value}
          resetKey={editor.key}
          language={language}
          onChange={save}
          label={`Sandbox ${LANGUAGE_NAMES[language]} editor`}
          onFocus={() => scroll.current?.scrollTo({ y: Math.max(editorY.current - 8, 0), animated: true })}
        />
      </View>
      <View style={styles.actions}>
        <Button variant="primary" title={busy ? 'Running…' : 'Run'} onPress={run} />
        <Button
          title="Restore example"
          onPress={() => {
            load(lesson.starter_code ?? '');
            setResult(null);
            setFailure('');
            runner.hidePage();
          }}
        />
      </View>
      {failure ? (
        <Text accessibilityRole="alert" style={[styles.failure, { color: theme.bad }]}>
          {failure}
        </Text>
      ) : null}
      {result?.kind === 'sql' ? <RunResults run={result.run} /> : null}
      {result?.kind === 'php' ? <PhpOutput {...result.run} /> : null}
      {result?.kind === 'js' ? <JsOutput {...result.run} quiet={language === 'html'} /> : null}
      {javascript ? runner.view : null}
      {!result && !failure ? (
        <Text style={[styles.intro, { color: theme.muted }]}>
          {language === 'sql' ? 'Press Run to see the result of your query here.' : 'Press Run to see what your code prints.'}
        </Text>
      ) : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 14, paddingBottom: 120 },
  intro: { fontSize: 15, lineHeight: 22 },
  actions: { flexDirection: 'row', gap: 10 },
  failure: { fontSize: 15, lineHeight: 22 },
});
