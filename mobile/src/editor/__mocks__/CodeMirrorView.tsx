import { useImperativeHandle, useState, type Ref } from 'react';
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
  const [appliedReset, setAppliedReset] = useState(resetKey);
  // A new resetKey means new text from the screen, like the real editor.
  if (appliedReset !== resetKey) {
    setAppliedReset(resetKey);
    setValue(initialValue);
  }

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
