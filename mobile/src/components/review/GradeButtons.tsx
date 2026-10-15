import { Pressable, StyleSheet, Text, View } from 'react-native';
import { previewWait, type Card, type Grade } from '../../review/schedule';
import { useTheme } from '../../theme';

const LABELS: Record<Grade, string> = { again: 'Again', hard: 'Hard', good: 'Good', easy: 'Easy' };

type Props = { card: Card | undefined; grades: Grade[]; onGrade: (grade: Grade) => void };

/** Anki-style grade buttons, each showing when the card would come back. */
export function GradeButtons({ card, grades, onGrade }: Props) {
  const theme = useTheme();
  const now = Date.now();

  return (
    <View style={styles.row}>
      {grades.map((grade) => {
        const wait = previewWait(card, grade, now);
        const primary = grade === 'good';
        return (
          <Pressable
            key={grade}
            accessibilityRole="button"
            accessibilityLabel={`${LABELS[grade]}, next review in ${wait}`}
            onPress={() => onGrade(grade)}
            style={({ pressed }) => [
              styles.button,
              {
                backgroundColor: primary ? theme.accent : theme.surface,
                borderColor: primary ? theme.accent : theme.line,
                opacity: pressed ? 0.75 : 1,
              },
            ]}
          >
            <Text style={[styles.label, { color: primary ? theme.accentInk : theme.ink }]}>{LABELS[grade]}</Text>
            <Text style={[styles.wait, { color: primary ? theme.accentInk : theme.muted }]}>{wait}</Text>
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', gap: 8 },
  button: { flex: 1, minHeight: 56, borderWidth: 1, borderRadius: 8, alignItems: 'center', justifyContent: 'center', padding: 6 },
  label: { fontSize: 16, fontWeight: '700' },
  wait: { fontSize: 13 },
});
