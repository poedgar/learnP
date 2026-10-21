import { ScrollView, StyleSheet, Text, View } from 'react-native';
import type { GitEntry } from '../../api/types';
import { monoFont, useTheme } from '../../theme';

/** A terminal for Git lessons, as the web app's GitOutput.vue: commands, output, and errors marked. */
export function GitOutput({ entries }: { entries: GitEntry[] }) {
  const theme = useTheme();

  return (
    <View style={styles.wrap} accessibilityLiveRegion="polite">
      <ScrollView horizontal style={styles.screen} contentContainerStyle={styles.content}>
        {entries.length ? (
          <Text selectable style={[styles.text, { color: '#eef1f8' }]}>
            {entries.map((entry, i) => (
              <Text
                key={i}
                style={
                  entry.level === 'command'
                    ? { color: theme.highlight, fontWeight: '700' }
                    : entry.level === 'error'
                      ? { color: '#ff9aa3' }
                      : null
                }
              >
                {entry.text}
                {i < entries.length - 1 ? '\n' : ''}
              </Text>
            ))}
          </Text>
        ) : (
          <Text style={[styles.text, { color: '#b9c2d8' }]}>Nothing ran. Write some commands, one per line, and press Run.</Text>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { gap: 6 },
  screen: { borderRadius: 8, backgroundColor: '#18233f' },
  content: { padding: 12 },
  text: { fontFamily: monoFont, fontSize: 13, lineHeight: 19 },
});
