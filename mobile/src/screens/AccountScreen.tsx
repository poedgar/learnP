import * as Linking from 'expo-linking';
import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, ScrollView, Share, StyleSheet, Text, TextInput, View, type TextInputProps } from 'react-native';
import { api, ApiError, baseUrl } from '../api/client';
import { deleteAccount, refreshUser, register, signIn, signInWithGithub, signOut, useAuthStore } from '../auth/store';
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
  const [github, setGithub] = useState(false);
  const [notice, setNotice] = useState('');
  const [confirmation, setConfirmation] = useState('');

  const registering = mode === 'register';

  useEffect(() => {
    api.authOptions().then((options) => setGithub(options.github), () => setGithub(false));
  }, []);

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

        {!user.email_verified ? (
          <View style={[styles.card, { backgroundColor: theme.highlightSoft, borderColor: theme.highlight }]}>
            <Text style={[styles.text, { color: theme.ink }]}>
              Please confirm your email address with the link we sent you, so you can reset your password if you ever
              need to.
            </Text>
            <Button
              title="Send the link again"
              disabled={busy}
              onPress={() => run(async () => setNotice((await api.resendVerification()).message))}
            />
            <Button variant="quiet" title="I've confirmed it" disabled={busy} onPress={() => run(refreshUser)} />
            {notice ? <Text style={[styles.text, { color: theme.ink }]}>{notice}</Text> : null}
          </View>
        ) : null}

        <View style={[styles.card, { backgroundColor: theme.surface, borderColor: theme.line }]}>
          <Text style={[styles.label, { color: theme.ink }]}>Your data</Text>
          <Text style={[styles.text, { color: theme.muted }]}>
            Everything we keep about you: your account, your progress in every lesson and your review schedule.
          </Text>
          <Button
            title="Export my data"
            disabled={busy}
            onPress={() =>
              run(async () => {
                await Share.share({ title: 'learnP data', message: JSON.stringify(await api.exportAccount(), null, 2) });
              })
            }
          />
        </View>

        <View style={[styles.card, { backgroundColor: theme.surface, borderColor: theme.bad }]}>
          <Text style={[styles.label, { color: theme.ink }]}>Delete your account</Text>
          <Text style={[styles.text, { color: theme.muted }]}>
            {"This deletes your account and all your progress for good, on every device. It can't be undone."}
          </Text>
          <View style={styles.field}>
            <Text style={[styles.label, { color: theme.ink }]}>
              {user.has_password ? 'Your password' : 'Type your email address to confirm'}
            </Text>
            <TextInput
              accessibilityLabel={user.has_password ? 'Your password' : 'Type your email address to confirm'}
              value={confirmation}
              onChangeText={setConfirmation}
              secureTextEntry={user.has_password}
              autoCapitalize="none"
              autoCorrect={false}
              style={[styles.input, { color: theme.ink, backgroundColor: theme.background, borderColor: errors.password || errors.confirmation ? theme.bad : theme.line }]}
            />
            {(errors.password ?? errors.confirmation) ? (
              <Text style={[styles.error, { color: theme.bad }]}>{(errors.password ?? errors.confirmation)[0]}</Text>
            ) : null}
          </View>
          <Button
            title="Delete my account"
            disabled={busy || confirmation === ''}
            onPress={() =>
              Alert.alert('Delete your account?', 'Your account and all your progress will be deleted for good.', [
                { text: 'Cancel', style: 'cancel' },
                {
                  text: 'Delete',
                  style: 'destructive',
                  onPress: () =>
                    run(async () => {
                      await deleteAccount(user.has_password ? { password: confirmation } : { confirmation });
                      router.back();
                    }),
                },
              ])
            }
          />
        </View>
      </ScrollView>
    );
  }

  return (
    <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Text accessibilityRole="header" style={[styles.title, { color: theme.ink }]}>
        {registering ? 'Create an account' : 'Sign in'}
      </Text>
      <Text style={[styles.text, { color: theme.muted }]}>
        {`An account keeps your progress and review schedule in step across devices and the web app. Whatever you've done on this device so far comes with you.`}
      </Text>

      {github ? (
        <Button
          title="Continue with GitHub"
          disabled={busy}
          onPress={() =>
            run(async () => {
              if (await signInWithGithub()) router.back();
            })
          }
        />
      ) : null}

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
        {!registering ? (
          <Button
            variant="quiet"
            title="Forgot your password?"
            onPress={() => Linking.openURL(`${baseUrl()}/forgot-password`)}
          />
        ) : null}
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
