import { StyleSheet, Text, View } from 'react-native';
import type { SqlResult, SqlRun } from '../../api/types';
import { plural } from '../../lib/text';
import { monoFont, useTheme } from '../../theme';
import { ResultTable } from './ResultTable';

/** Wording follows the web app's RunResults.vue. */
function rowsCaption(result: Extract<SqlResult, { type: 'rows' }>): string {
  const count = result.truncated ? `First ${result.rows.length} rows` : plural(result.rows.length, 'row');
  return `${count} in ${result.duration_ms} ms`;
}

function commandCaption(result: Extract<SqlResult, { type: 'command' }>): string {
  const verb = result.sql.trim().split(/\s+/)[0].toUpperCase();
  return result.affected_rows === null
    ? `${verb} ran successfully.`
    : `${verb} changed ${plural(result.affected_rows, 'row')}.`;
}

function preview(sql: string): string {
  const line = sql.replace(/\s+/g, ' ').trim();
  return line.length > 70 ? `${line.slice(0, 70)}…` : line;
}

export function RunResults({ run }: { run: SqlRun }) {
  const theme = useTheme();
  const multiple = run.results.length + (run.error ? 1 : 0) > 1;

  return (
    <View style={styles.list} accessibilityLiveRegion="polite">
      {run.results.map((result, index) => (
        <View key={index} style={styles.result}>
          {multiple ? (
            <Text style={[styles.statement, { color: theme.muted }]}>{`${index + 1}. ${preview(result.sql)}`}</Text>
          ) : null}
          {result.type === 'rows' ? (
            <>
              <ResultTable columns={result.columns} rows={result.rows} />
              <Text style={[styles.meta, { color: theme.muted }]}>
                {`${rowsCaption(result)}${result.truncated ? '. Add a LIMIT to see specific rows.' : '.'}`}
              </Text>
            </>
          ) : (
            <Text style={[styles.meta, { color: theme.ink }]}>{commandCaption(result)}</Text>
          )}
        </View>
      ))}
      {run.error ? (
        <Text accessibilityRole="alert" style={[styles.error, { color: theme.bad, backgroundColor: theme.badSoft }]}>
          {run.error.statement === null ? run.error.message : `Statement ${run.error.statement + 1}: ${run.error.message}`}
        </Text>
      ) : null}
      {run.results.length === 0 && !run.error ? (
        <Text style={[styles.meta, { color: theme.muted }]}>Your SQL ran but returned nothing.</Text>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  list: { gap: 14 },
  result: { gap: 6 },
  statement: { fontFamily: monoFont, fontSize: 13 },
  meta: { fontSize: 14 },
  error: { padding: 10, borderRadius: 8, fontSize: 15, lineHeight: 22 },
});
