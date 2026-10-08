import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { SchemaTable } from '../../api/types';
import { plural } from '../../lib/text';
import { monoFont, useTheme } from '../../theme';
import { Button } from '../Button';

/** The lesson dataset's tables, collapsed by default to leave room for the editor. */
export function SchemaPanel({ tables, onPreview }: { tables: SchemaTable[]; onPreview: (table: string) => void }) {
  const theme = useTheme();
  const [open, setOpen] = useState(false);

  if (tables.length === 0) return null;

  return (
    <View style={[styles.panel, { borderColor: theme.line, backgroundColor: theme.surface }]}>
      <Button variant="quiet" title={open ? 'Hide tables' : 'Show tables'} onPress={() => setOpen(!open)} />
      {open
        ? tables.map((table) => (
            <View key={table.name} style={styles.table}>
              <Text style={[styles.name, { color: theme.ink }]}>{table.name}</Text>
              <Text style={[styles.meta, { color: theme.muted }]}>{plural(table.row_count, 'row')}</Text>
              {table.columns.map((column) => (
                <Text key={column.name} style={[styles.column, { color: theme.ink }]}>
                  {`${column.name} ${column.type}${column.primary_key ? ', primary key' : ''}`}
                </Text>
              ))}
              <Button title={`Preview ${table.name}`} onPress={() => onPreview(table.name)} />
            </View>
          ))
        : null}
    </View>
  );
}

const styles = StyleSheet.create({
  panel: { borderWidth: 1, borderRadius: 8, padding: 8, gap: 8 },
  table: { gap: 4, paddingHorizontal: 8, paddingBottom: 8 },
  name: { fontFamily: monoFont, fontSize: 16, fontWeight: '700' },
  meta: { fontSize: 13 },
  column: { fontFamily: monoFont, fontSize: 14 },
});
