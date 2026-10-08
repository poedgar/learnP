import { StyleSheet, Text, View } from 'react-native';
import type { SqlCheck, SqlResult } from '../../api/types';
import { useTheme } from '../../theme';
import { ResultTable } from './ResultTable';
import { RunResults } from './RunResults';

export function SqlCheckDetails({ check }: { check: SqlCheck }) {
  const theme = useTheme();
  const rowSets = check.run.results.filter((r): r is Extract<SqlResult, { type: 'rows' }> => r.type === 'rows');
  const learnerRows = rowSets[rowSets.length - 1] ?? null;
  const heading = [styles.heading, { color: theme.ink }];

  if (check.passed) {
    return learnerRows ? <ResultTable columns={learnerRows.columns} rows={learnerRows.rows} /> : null;
  }

  return (
    <View style={styles.compare}>
      <Text style={heading}>Your result</Text>
      {learnerRows ? <ResultTable columns={learnerRows.columns} rows={learnerRows.rows} /> : <RunResults run={check.run} />}
      {check.expected ? (
        <>
          <Text style={heading}>Expected result</Text>
          <ResultTable columns={check.expected.columns} rows={check.expected.rows} />
        </>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  compare: { gap: 8 },
  heading: { fontSize: 15, fontWeight: '700', marginTop: 4 },
});
