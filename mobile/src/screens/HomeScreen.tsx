import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { api } from '../api/client';
import { LoadState } from '../components/LoadState';
import { plural } from '../lib/text';
import { useReviewDeck } from '../review/deck';
import { useTheme } from '../theme';

export function HomeScreen() {
  const theme = useTheme();
  const query = useQuery({ queryKey: ['courses'], queryFn: api.courses });
  const deck = useReviewDeck();

  return (
    <ScrollView contentContainerStyle={styles.page}>
      <Text accessibilityRole="header" style={[styles.title, { color: theme.ink }]}>
        Learn programming by running real code.
      </Text>
      <Text style={[styles.intro, { color: theme.muted }]}>
        Each lesson pairs a short explanation with a live sandbox, a quiz, and challenges that check your answers by
        running your code.
      </Text>
      {deck.items.length ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={`Review, ${plural(deck.due.length, 'card')} due`}
          onPress={() => router.push('/review')}
          style={({ pressed }) => [
            styles.card,
            styles.review,
            { backgroundColor: theme.highlightSoft, borderColor: theme.highlight, opacity: pressed ? 0.8 : 1 },
          ]}
        >
          <Text style={[styles.cardTitle, { color: theme.ink }]}>Review</Text>
          <Text style={[styles.meta, { color: theme.ink }]}>
            {deck.due.length ? `${plural(deck.due.length, 'card')} due now` : 'All caught up'} ·{' '}
            {plural(deck.items.length, 'item')} passed
          </Text>
        </Pressable>
      ) : null}
      <LoadState loading={query.isPending} error={query.error} onRetry={() => query.refetch()}>
        <View style={styles.list}>
          {query.data?.map((course) => (
            <Pressable
              key={course.id}
              accessibilityRole="button"
              accessibilityLabel={`${course.title}, ${plural(course.lessons_count ?? 0, 'lesson')}`}
              onPress={() => router.push(`/courses/${course.slug}`)}
              style={({ pressed }) => [
                styles.card,
                { backgroundColor: theme.surface, borderColor: theme.line, opacity: pressed ? 0.8 : 1 },
              ]}
            >
              <Text style={[styles.cardTitle, { color: theme.ink }]}>{course.title}</Text>
              <Text style={[styles.meta, { color: theme.muted }]}>
                {plural(course.lessons_count ?? 0, 'lesson')} · {course.language === 'sql' ? 'SQL' : 'PHP'}
              </Text>
              <Text style={[styles.description, { color: theme.ink }]}>{course.description}</Text>
            </Pressable>
          ))}
        </View>
      </LoadState>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 16, flexGrow: 1 },
  title: { fontSize: 28, fontWeight: '800', lineHeight: 34 },
  intro: { fontSize: 16, lineHeight: 24 },
  list: { gap: 12 },
  card: { borderWidth: 1, borderRadius: 14, padding: 16, gap: 6 },
  review: { borderWidth: 2 },
  cardTitle: { fontSize: 20, fontWeight: '700' },
  meta: { fontSize: 14 },
  description: { fontSize: 16, lineHeight: 23 },
});
