import { ScrollView, StyleSheet, Text, View } from 'react-native';
import type { Cell } from '../../api/types';
import { monoFont, useTheme } from '../../theme';

export function ResultTable({ columns, rows }: { columns: string[]; rows: Cell[][] }) {
  const theme = useTheme();

  return (
    <ScrollView horizontal style={[styles.box, { borderColor: theme.line, backgroundColor: theme.surface }]}>
      <View>
        <View style={[styles.row, { backgroundColor: theme.codeBackground }]}>
          {columns.map((column, i) => (
            <Text key={i} style={[styles.cell, styles.head, { color: theme.ink, borderColor: theme.line }]}>
              {column}
            </Text>
          ))}
        </View>
        {rows.map((row, r) => (
          <View key={r} style={styles.row}>
            {row.map((value, c) => (
              <Text
                key={c}
                style={[
                  styles.cell,
                  { color: value === null ? theme.muted : theme.ink, borderColor: theme.line },
                  value === null && styles.null,
                ]}
              >
                {value === null ? 'NULL' : String(value)}
              </Text>
            ))}
          </View>
        ))}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  box: { borderWidth: 1, borderRadius: 8 },
  row: { flexDirection: 'row' },
  cell: { minWidth: 88, maxWidth: 260, paddingVertical: 6, paddingHorizontal: 10, borderRightWidth: 1, borderBottomWidth: 1, fontFamily: monoFont, fontSize: 14 },
  head: { fontWeight: '700' },
  null: { fontStyle: 'italic' },
});
