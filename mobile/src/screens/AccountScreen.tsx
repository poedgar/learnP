import { router } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, Text, TextInput, View, type TextInputProps } from 'react-native';
import { ApiError } from '../api/client';
import { register, signIn, signOut, useAuthStore } from '../auth/store';
import { Button } from '../components/Button';
import { useTheme } from '../theme';

type Mode = 'sign-in' | 'register';

/** Sign in or create an account; once signed in, who you are and a way to sign out. */
export function AccountScreen() {
  const theme = useTheme();
  const user = useAuthStore((state) => state.user);
  const [mode, setMode] = useState<Mode>('sign-in');
  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' });
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);

  const registering = mode === 'register';

  async function run(action: () => Promise<void>) {
    setBusy(true);
    setErrors({});
    setMessage('');
    try {
      await action();
    } catch (error) {
      const fieldErrors = error instanceof ApiError ? error.errors : {};
      setErrors(fieldErrors);
      setMessage(Object.keys(fieldErrors).length ? '' : error instanceof Error ? error.message : String(error));
    } finally {
      setBusy(false);
    }
  }

  function submit() {
    run(async () => {
      await (registering ? register(form) : signIn({ email: form.email, password: form.password }));
      router.back();
    });
  }

  if (user) {
    return (
      <ScrollView contentContainerStyle={styles.page}>
        <Text accessibilityRole="header" style={[styles.title, { color: theme.ink }]}>
          {user.name}
        </Text>
        <Text style={[styles.text, { color: theme.muted }]}>{user.email}</Text>
        <Text style={[styles.text, { color: theme.muted }]}>
          Your progress and review schedule are saved to your account, so they follow you to the web app and other
          devices.
        </Text>
        <Button title="Sign out" disabled={busy} onPress={() => run(signOut)} />
        {message ? <Text style={[styles.error, { color: theme.bad }]}>{message}</Text> : null}
      </ScrollView>
    );
  }

  const field = (key: keyof typeof form, label: string, props: TextInputProps) => (
    <View style={styles.field}>
      <Text style={[styles.label, { color: theme.ink }]}>{label}</Text>
      <TextInput
        accessibilityLabel={label}
        value={form[key]}
        onChangeText={(value) => setForm((current) => ({ ...current, [key]: value }))}
        autoCapitalize="none"
        autoCorrect={false}
        placeholderTextColor={theme.muted}
        style={[
          styles.input,
          { color: theme.ink, backgroundColor: theme.background, borderColor: errors[key] ? theme.bad : theme.line },
        ]}
        {...props}
      />
      {errors[key] ? <Text style={[styles.error, { color: theme.bad }]}>{errors[key][0]}</Text> : null}
    </View>
  );

  return (
    <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Text accessibilityRole="header" style={[styles.title, { color: theme.ink }]}>
        {registering ? 'Create an account' : 'Sign in'}
      </Text>
      <Text style={[styles.text, { color: theme.muted }]}>
        {`An account keeps your progress and review schedule in step across devices and the web app. Whatever you've done on this device so far comes with you.`}
      </Text>

      <View style={[styles.card, { backgroundColor: theme.surface, borderColor: theme.line }]}>
        {registering ? field('name', 'Name', { autoCapitalize: 'words', autoComplete: 'name', textContentType: 'name' }) : null}
        {field('email', 'Email', { keyboardType: 'email-address', autoComplete: 'email', textContentType: 'emailAddress' })}
        {field('password', 'Password', {
          secureTextEntry: true,
          autoComplete: registering ? 'new-password' : 'current-password',
          textContentType: registering ? 'newPassword' : 'password',
        })}
        {registering
          ? field('password_confirmation', 'Confirm password', {
              secureTextEntry: true,
              autoComplete: 'new-password',
              textContentType: 'newPassword',
            })
          : null}
        {message ? (
          <Text accessibilityRole="alert" style={[styles.error, { color: theme.bad }]}>
            {message}
          </Text>
        ) : null}
        <Button variant="primary" title={registering ? 'Create account' : 'Sign in'} disabled={busy} onPress={submit} />
      </View>

      <Button
        variant="quiet"
        title={registering ? 'Already have an account? Sign in' : 'New here? Create an account'}
        onPress={() => {
          setMode(registering ? 'sign-in' : 'register');
          setErrors({});
          setMessage('');
        }}
      />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 16, flexGrow: 1 },
  title: { fontSize: 28, fontWeight: '800', lineHeight: 34 },
  text: { fontSize: 16, lineHeight: 24 },
  card: { borderWidth: 1, borderRadius: 14, padding: 16, gap: 14 },
  field: { gap: 6 },
  label: { fontSize: 15, fontWeight: '700' },
  input: { minHeight: 44, borderWidth: 1, borderRadius: 8, paddingHorizontal: 12, fontSize: 16 },
  error: { fontSize: 14 },
});
