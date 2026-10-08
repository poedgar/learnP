import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../../theme';

export type Step = 'learn' | 'sandbox' | 'quiz' | 'challenges';

type Props = {
  steps: { key: Step; label: string; status: string }[];
  current: Step;
  onChange: (step: Step) => void;
};

/** Tapped, never swiped, so it can't fight with scrolling inside the editor. */
export function StepSwitcher({ steps, current, onChange }: Props) {
  const theme = useTheme();

  return (
    <View accessibilityRole="tablist" style={[styles.bar, { backgroundColor: theme.surface, borderColor: theme.line }]}>
      {steps.map((step) => {
        const selected = step.key === current;
        return (
          <Pressable
            key={step.key}
            accessibilityRole="tab"
            accessibilityLabel={step.status ? `${step.label}, ${step.status}` : step.label}
            accessibilityState={{ selected }}
            onPress={() => onChange(step.key)}
            style={[styles.tab, selected && { borderBottomColor: theme.highlight }]}
          >
            <Text style={[styles.label, { color: selected ? theme.ink : theme.muted }]}>{step.label}</Text>
            {step.status ? <Text style={[styles.status, { color: theme.muted }]}>{step.status}</Text> : null}
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  bar: { flexDirection: 'row', borderBottomWidth: 1 },
  tab: { flex: 1, minHeight: 52, alignItems: 'center', justifyContent: 'center', borderBottomWidth: 3, borderBottomColor: 'transparent', paddingVertical: 6 },
  label: { fontSize: 15, fontWeight: '700' },
  status: { fontSize: 12 },
});
