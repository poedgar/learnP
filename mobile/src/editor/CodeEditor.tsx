import { useEffect, useId, useRef } from 'react';
import { Platform, useColorScheme, View } from 'react-native';
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

  // An editor can unmount while focused (leaving the lesson); don't leave the key bar typing into it.
  useEffect(() => () => useKeyBarStore.getState().blur(id), [id]);

  // The props sent across to the WebView (functions travel by name).
  const props = { encodedInitialValue: encodeURIComponent(value), resetKey, language, dark, accessibilityLabel: label };

  return (
    <View
      accessible={false}
      style={{ height, borderWidth: 1, borderColor: theme.line, borderRadius: 8, overflow: 'hidden' }}
    >
      <CodeMirrorView
        ref={handle}
        {...props}
        onChange={async (next) => onChange(next)}
        onFocusChange={async (focused) => {
          if (focused) {
            focus({ id, language, insert: (text, offset) => handle.current?.insert(text, offset) });
            onFocus?.();
          } else {
            blur(id);
          }
        }}
        dom={{
          style: { flex: 1 },
          useExpoDOMWebView: false,
          // On Android, react-native-webview defines injectedObjectJson racing the page's own
          // scripts, so Expo's page can end up without the host OS or initial props and crash
          // ("Top OS ($$EXPO_DOM_HOST_OS) is not defined"). Provide them ourselves, and ignore
          // the page setting them to undefined. Expo re-sends the live props once the page is ready.
          injectedJavaScriptBeforeContentLoaded: `
            [['$$EXPO_DOM_HOST_OS', ${JSON.stringify(Platform.OS)}],
             ['$$EXPO_INITIAL_PROPS', ${JSON.stringify({ names: ['onChange', 'onFocusChange'], props })}]
            ].forEach(function (entry) {
              var current = window[entry[0]] !== undefined ? window[entry[0]] : entry[1];
              Object.defineProperty(window, entry[0], {
                configurable: true,
                get: function () { return current; },
                set: function (next) { if (next !== undefined) current = next; },
              });
            });
            true;`,
        }}
      />
    </View>
  );
}
