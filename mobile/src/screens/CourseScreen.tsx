import { useQuery } from '@tanstack/react-query';
import { router, Stack } from 'expo-router';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { api } from '../api/client';
import type { LessonSummary as Lesson } from '../api/types';
import { LoadState } from '../components/LoadState';
import { plural } from '../lib/text';
import { useLessonProgress } from '../progress/store';
import { lessonSummary } from '../progress/summary';
import { useTheme } from '../theme';

export function CourseScreen({ slug }: { slug: string }) {
  const theme = useTheme();
  const query = useQuery({ queryKey: ['course', slug], queryFn: () => api.course(slug) });

  return (
    <>
      <Stack.Screen options={{ title: query.data?.title ?? '' }} />
      <LoadState
        loading={query.isPending}
        error={query.error}
        notFound="We couldn't find that course."
        onRetry={() => query.refetch()}
      >
        {query.data ? (
          <ScrollView contentContainerStyle={styles.page}>
            <Text style={[styles.description, { color: theme.muted }]}>{query.data.description}</Text>
            {query.data.lessons.map((lesson) => (
              <LessonRow key={lesson.id} lesson={lesson} courseSlug={slug} />
            ))}
          </ScrollView>
        ) : null}
      </LoadState>
    </>
  );
}

function LessonRow({ lesson, courseSlug }: { lesson: Lesson; courseSlug: string }) {
  const theme = useTheme();
  const summary = lessonSummary(lesson, useLessonProgress(lesson.id));
  const progress = summary.complete
    ? 'Completed.'
    : summary.started
      ? `${summary.read ? 'Read' : 'Not read yet'} · quiz ${summary.quizCorrect}/${summary.quizTotal} · challenges ${summary.challengesPassed}/${summary.challengeTotal}`
      : null;

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Lesson ${lesson.position}: ${lesson.title}`}
      onPress={() => router.push(`/courses/${courseSlug}/lessons/${lesson.slug}`)}
      style={({ pressed }) => [
        styles.lesson,
        { backgroundColor: theme.surface, borderColor: theme.line, opacity: pressed ? 0.8 : 1 },
      ]}
    >
      <Text style={[styles.number, { color: theme.muted }]}>{lesson.position}</Text>
      <View style={styles.body}>
        <Text style={[styles.lessonTitle, { color: theme.ink }]}>{lesson.title}</Text>
        <Text style={[styles.text, { color: theme.ink }]}>{lesson.summary}</Text>
        <Text style={[styles.meta, { color: theme.muted }]}>
          {`Reading, a live sandbox, ${plural(lesson.quiz_questions_count, 'quiz question')} and ${plural(lesson.challenges_count, 'challenge')}.`}
        </Text>
        {progress ? (
          <Text style={[styles.meta, { color: summary.complete ? theme.ok : theme.ink }]}>{progress}</Text>
        ) : null}
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 12 },
  description: { fontSize: 16, lineHeight: 24, marginBottom: 4 },
  lesson: { flexDirection: 'row', gap: 12, borderWidth: 1, borderRadius: 14, padding: 16 },
  number: { fontSize: 22, fontWeight: '800', minWidth: 24 },
  body: { flex: 1, gap: 6 },
  lessonTitle: { fontSize: 18, fontWeight: '700' },
  text: { fontSize: 16, lineHeight: 23 },
  meta: { fontSize: 14, lineHeight: 20 },
});
