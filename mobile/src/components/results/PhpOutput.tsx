import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { monoFont, useTheme } from '../../theme';

type Props = { output: string; fatal: boolean; durationMs?: number | null };

/** PHP's own error and warning lines are coloured, as in the web app's PhpOutput.vue. */
export function PhpOutput({ output, fatal, durationMs = null }: Props) {
  const theme = useTheme();
  const lines = output.split('\n');

  return (
    <View style={styles.wrap} accessibilityLiveRegion="polite">
      <ScrollView
        horizontal
        style={[styles.screen, { backgroundColor: theme.codeBackground, borderColor: fatal ? theme.bad : theme.line }]}
        contentContainerStyle={styles.content}
      >
        {output.trim() ? (
          <Text selectable style={[styles.text, { color: theme.ink }]}>
            {lines.map((line, i) => {
              const color = /^(PHP )?(Fatal error|Parse error)/.test(line)
                ? theme.bad
                : /^(PHP )?(Warning|Notice|Deprecated)/.test(line)
                  ? theme.syntax.number
                  : theme.ink;
              return (
                <Text key={i} style={{ color }}>
                  {line}
                  {i < lines.length - 1 ? '\n' : ''}
                </Text>
              );
            })}
          </Text>
        ) : (
          <Text style={[styles.silent, { color: theme.muted }]}>
            {"Your code ran but didn't print anything. Use echo to see values."}
          </Text>
        )}
      </ScrollView>
      <Text style={[styles.meta, { color: fatal ? theme.bad : theme.muted }]}>
        {fatal ? 'PHP stopped with an error.' : durationMs !== null ? `Ran in ${durationMs} ms.` : ''}
      </Text>
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
