import { useEffect, useState } from 'react';
import { Keyboard, Platform, Pressable, ScrollView, StyleSheet, Text, View, type ViewStyle } from 'react-native';
import type { Language } from '../api/types';
import { monoFont, useTheme } from '../theme';
import { KEYS, type EditorKey } from './keys';
import { useKeyBarStore } from './keyBarStore';

export function KeyBar({ language, onKey, style }: { language: Language; onKey: (key: EditorKey) => void; style?: ViewStyle }) {
  const theme = useTheme();

  return (
    <View style={[styles.bar, { backgroundColor: theme.surface, borderColor: theme.line }, style]}>
      <ScrollView horizontal keyboardShouldPersistTaps="always" contentContainerStyle={styles.keys}>
        {KEYS[language].map((key) => (
          <Pressable
            key={key.label}
            accessibilityRole="button"
            accessibilityLabel={key.accessibilityLabel}
            onPress={() => onKey(key)}
            style={({ pressed }) => [styles.key, { backgroundColor: pressed ? theme.line : theme.codeBackground }]}
          >
            <Text style={[styles.label, { color: theme.ink }]}>{key.label}</Text>
          </Pressable>
        ))}
      </ScrollView>
    </View>
  );
}

/** Rendered once at the root; floats the key bar above the keyboard while an editor has focus. */
export function KeyBarHost() {
  const active = useKeyBarStore((state) => state.active);
  const keyboardHeight = useKeyboardHeight();

  if (!active || keyboardHeight === 0) {
    return null;
  }

  return (
    <KeyBar
      language={active.language}
      onKey={(key) => active.insert(key.text, key.cursorOffset)}
      style={{ position: 'absolute', left: 0, right: 0, bottom: keyboardHeight }}
    />
  );
}

function useKeyboardHeight(): number {
  const [height, setHeight] = useState(0);

  useEffect(() => {
    const ios = Platform.OS === 'ios';
    const show = Keyboard.addListener(ios ? 'keyboardWillShow' : 'keyboardDidShow', (event) =>
      setHeight(event.endCoordinates.height),
    );
    const hide = Keyboard.addListener(ios ? 'keyboardWillHide' : 'keyboardDidHide', () => setHeight(0));
    return () => {
      show.remove();
      hide.remove();
    };
  }, []);

  return height;
}

const styles = StyleSheet.create({
  bar: { borderTopWidth: 1 },
  keys: { gap: 6, padding: 6 },
  key: { minWidth: 44, height: 40, paddingHorizontal: 10, borderRadius: 6, alignItems: 'center', justifyContent: 'center' },
  label: { fontFamily: monoFont, fontSize: 16, fontWeight: '600' },
});
