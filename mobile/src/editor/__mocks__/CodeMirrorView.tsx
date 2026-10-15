import { useEffect, useImperativeHandle, useState, type Ref } from 'react';
import { TextInput } from 'react-native';
import type { EditorHandle } from '../CodeMirrorView';

/** Jest stand-in for the WebView editor: a TextInput with the same props and handle. */
export default function CodeMirrorView({
  encodedInitialValue,
  resetKey,
  onChange,
  onFocusChange,
  accessibilityLabel,
  ref,
}: {
  encodedInitialValue: string;
  resetKey: number;
  onChange: (value: string) => Promise<void> | void;
  onFocusChange: (focused: boolean) => Promise<void> | void;
  accessibilityLabel?: string;
  ref?: Ref<EditorHandle>;
}) {
  const initialValue = decodeURIComponent(encodedInitialValue);
  const [value, setValue] = useState(initialValue);

  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => setValue(initialValue), [resetKey]);

  useImperativeHandle(ref, () => ({
    insert: (text: string) => {
      setValue((current) => {
        const next = current + text;
        onChange(next);
        return next;
      });
    },
    focus: () => {},
  }));

  return (
    <TextInput
      testID="code-editor"
      accessibilityLabel={accessibilityLabel}
      value={value}
      onChangeText={(text) => {
        setValue(text);
        onChange(text);
      }}
      onFocus={() => onFocusChange(true)}
      onBlur={() => onFocusChange(false)}
    />
  );
}
