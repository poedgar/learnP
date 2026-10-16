import { ScrollView, StyleSheet, Text, View } from 'react-native';
import type { ConsoleEntry } from '../../api/types';
import { monoFont, useTheme } from '../../theme';

type Props = {
  entries: ConsoleEntry[];
  error?: string | null;
  typeErrors?: { line: number; message: string }[];
  stillRunning?: boolean;
  durationMs?: number | null;
  hasPage?: boolean;
  /** Show nothing when nothing was printed and nothing went wrong (HTML, where the page is the result). */
  quiet?: boolean;
};

/** What JavaScript printed, as in the web app's JsOutput.vue: warnings and errors coloured, type errors listed. */
export function JsOutput({ entries, error = null, typeErrors = [], stillRunning = false, durationMs = null, hasPage = false, quiet = false }: Props) {
  const theme = useTheme();
  const failed = Boolean(error) || typeErrors.length > 0;
  if (quiet && !failed && entries.length === 0) return null;

  const meta = typeErrors.length
    ? `TypeScript found ${typeErrors.length === 1 ? 'a type error' : `${typeErrors.length} type errors`}, so the code didn't run.`
    : `${error ? 'Your code stopped with an error.' : durationMs !== null ? `Ran in ${durationMs} ms.` : ''}${
        stillRunning ? ' A timer was still running after 5 seconds, so the sandbox stopped waiting for it.' : ''
      }`;

  return (
    <View style={styles.wrap} accessibilityLiveRegion="polite">
      <ScrollView
        horizontal
        style={[styles.screen, { backgroundColor: theme.codeBackground, borderColor: failed ? theme.bad : theme.line }]}
        contentContainerStyle={styles.content}
      >
        {typeErrors.length || entries.length || error ? (
          <Text selectable style={[styles.text, { color: theme.ink }]}>
            {typeErrors.map((problem, i) => (
              <Text key={`t${i}`} style={{ color: theme.bad }}>
                {`Line ${problem.line}: ${problem.message}\n`}
              </Text>
            ))}
            {entries.map((entry, i) => (
              <Text key={i} style={{ color: entry.level === 'error' ? theme.bad : entry.level === 'warn' ? theme.syntax.number : theme.ink }}>
                {entry.text}
                {i < entries.length - 1 || error ? '\n' : ''}
              </Text>
            ))}
            {error ? <Text style={{ color: theme.bad, fontWeight: '700' }}>{error}</Text> : null}
          </Text>
        ) : (
          <Text style={[styles.silent, { color: theme.muted }]}>
            {hasPage
              ? "Your code ran but didn't print anything. See the page it built below."
              : "Your code ran but didn't print anything. Use console.log() to see values."}
          </Text>
        )}
      </ScrollView>
      {meta ? <Text style={[styles.meta, { color: failed ? theme.bad : theme.muted }]}>{meta}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { gap: 6 },
  screen: { borderWidth: 1, borderRadius: 8, maxHeight: 320 },
  content: { padding: 12 },
  text: { fontFamily: monoFont, fontSize: 14, lineHeight: 20 },
  silent: { fontSize: 15 },
  meta: { fontSize: 14 },
});
