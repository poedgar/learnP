import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useState } from 'react';
import { FlatList, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { api } from '../api/client';
import { useTheme } from '../theme';

/** Finds lessons by title, summary or theory, using the API's search. */
export function SearchScreen() {
  const theme = useTheme();
  const [text, setText] = useState('');
  const [submitted, setSubmitted] = useState('');
  const query = useQuery({
    queryKey: ['search', submitted],
    queryFn: () => api.search(submitted),
    enabled: submitted.length >= 2,
  });

  return (
    <View style={styles.page}>
      <TextInput
        accessibilityLabel="Search for"
        value={text}
        onChangeText={setText}
        onSubmitEditing={() => setSubmitted(text.trim())}
        returnKeyType="search"
        autoFocus
        autoCapitalize="none"
        autoCorrect={false}
        placeholder="For example: array_column, JOIN, promises"
        placeholderTextColor={theme.muted}
        style={[styles.input, { color: theme.ink, backgroundColor: theme.surface, borderColor: theme.line }]}
      />
      {submitted.length >= 2 && query.isPending ? <Text style={[styles.meta, { color: theme.muted }]}>Searching…</Text> : null}
      {query.error ? <Text style={[styles.meta, { color: theme.bad }]}>{query.error.message}</Text> : null}
      {query.data ? (
        <FlatList
          data={query.data}
          keyExtractor={(result) => `${result.course.slug}/${result.lesson.slug}`}
          ListHeaderComponent={
            <Text style={[styles.meta, { color: theme.muted }]}>
              {query.data.length ? `${query.data.length} ${query.data.length === 1 ? 'lesson' : 'lessons'} found` : 'Nothing found. Try another word.'}
            </Text>
          }
          contentContainerStyle={styles.list}
          renderItem={({ item }) => (
            <Pressable
              accessibilityRole="button"
              accessibilityLabel={`${item.lesson.title}, ${item.course.title}`}
              onPress={() => router.push(`/courses/${item.course.slug}/lessons/${item.lesson.slug}`)}
              style={({ pressed }) => [styles.result, { borderColor: theme.line, opacity: pressed ? 0.7 : 1 }]}
            >
              <Text style={[styles.title, { color: theme.ink }]}>{item.lesson.title}</Text>
              <Text style={[styles.meta, { color: theme.muted }]}>{item.course.title}</Text>
              <Text style={[styles.snippet, { color: theme.ink }]} numberOfLines={3}>
                {item.snippet}
              </Text>
            </Pressable>
          )}
        />
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  page: { flex: 1, padding: 20, gap: 12 },
  input: { minHeight: 44, borderWidth: 1, borderRadius: 10, paddingHorizontal: 12, fontSize: 16 },
  list: { gap: 12, paddingBottom: 40 },
  result: { borderBottomWidth: 1, paddingBottom: 12, gap: 2 },
  title: { fontSize: 18, fontWeight: '700' },
  meta: { fontSize: 14 },
  snippet: { fontSize: 15, lineHeight: 21, marginTop: 4 },
});
