import { useId, useRef } from 'react';
import { useColorScheme, View } from 'react-native';
import type { Language } from '../api/types';
import { useTheme } from '../theme';
import CodeMirrorView, { type EditorHandle } from './CodeMirrorView';
import { useKeyBarStore } from './keyBarStore';

type Props = {
  value: string;
  resetKey: number;
  language: Language;
  onChange: (value: string) => void;
  label: string;
  height?: number;
  onFocus?: () => void;
};

/** The code editor as a native view. value is applied only when resetKey changes. */
export function CodeEditor({ value, resetKey, language, onChange, label, height = 240, onFocus }: Props) {
  const theme = useTheme();
  const dark = useColorScheme() === 'dark';
  const id = useId();
  const handle = useRef<EditorHandle>(null);
  const { focus, blur } = useKeyBarStore.getState();

  return (
    <View
      accessible={false}
      style={{ height, borderWidth: 1, borderColor: theme.line, borderRadius: 8, overflow: 'hidden' }}
    >
      <CodeMirrorView
        ref={handle}
        initialValue={value}
        resetKey={resetKey}
        language={language}
        dark={dark}
        onChange={async (next) => onChange(next)}
        onFocusChange={async (focused) => {
          if (focused) {
            focus({ id, language, insert: (text, offset) => handle.current?.insert(text, offset) });
            onFocus?.();
          } else {
            blur(id);
          }
        }}
        accessibilityLabel={label}
        dom={{ style: { flex: 1 } }}
      />
    </View>
  );
}
