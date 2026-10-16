import { StyleSheet, Text, View } from 'react-native';
import type { JsCheck, PhpCheck } from '../../api/types';
import { useTheme } from '../../theme';
import { JsOutput } from './JsOutput';
import { PhpOutput } from './PhpOutput';

/** Check reports from sandboxes that run checks themselves: PHP, and JavaScript (which adds console entries). */
export function PhpCheckDetails({ check }: { check: PhpCheck | JsCheck }) {
  const entries = 'entries' in check ? check.entries : null;
  const theme = useTheme();

  return (
    <View style={styles.details}>
      {check.error ? (
        <Text accessibilityRole="alert" style={[styles.error, { color: theme.bad, backgroundColor: theme.badSoft }]}>
          {check.error}
        </Text>
      ) : null}
      {check.checks.map((item, i) => {
        const state = !check.loaded ? 'not run' : item.passed ? 'passed' : 'failed';
        return (
          <View key={i} accessible accessibilityLabel={`${item.label} (${state})`} style={styles.check}>
            <Text style={[styles.mark, { color: state === 'passed' ? theme.ok : state === 'failed' ? theme.bad : theme.muted }]}>
              {state === 'passed' ? '✓' : state === 'failed' ? '✗' : '•'}
            </Text>
            <View style={styles.checkBody}>
              <Text style={[styles.label, { color: theme.ink }]}>{item.label}</Text>
              {check.loaded && !item.passed && item.message ? (
                <Text style={[styles.message, { color: theme.muted }]}>{item.message}</Text>
              ) : null}
            </View>
          </View>
        );
      })}
      {(entries ? entries.length > 0 : check.output.trim()) ? (
        <View style={styles.output}>
          <Text style={[styles.heading, { color: theme.ink }]}>What your code printed</Text>
          {entries ? <JsOutput entries={entries} /> : <PhpOutput output={check.output} fatal={!check.loaded} />}
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  details: { gap: 10 },
  error: { padding: 10, borderRadius: 8, fontSize: 15, lineHeight: 22 },
  check: { flexDirection: 'row', gap: 10 },
  mark: { fontSize: 18, fontWeight: '700', width: 18 },
  checkBody: { flex: 1, gap: 2 },
  label: { fontSize: 16 },
  message: { fontSize: 14, lineHeight: 20 },
  output: { gap: 6 },
  heading: { fontSize: 15, fontWeight: '700' },
});
