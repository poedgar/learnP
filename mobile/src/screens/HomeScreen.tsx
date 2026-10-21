import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useMemo } from 'react';
import { Linking, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { api, baseUrl } from '../api/client';
import { LANGUAGE_NAMES, type CourseSummary } from '../api/types';
import { useAuthStore } from '../auth/store';
import { LoadState } from '../components/LoadState';
import { plural } from '../lib/text';
import { streak, useActivityStore } from '../progress/activity';
import { useProgressStore } from '../progress/store';
import { courseProgress } from '../progress/summary';
import { useReviewDeck } from '../review/deck';
import { useTheme } from '../theme';

export function HomeScreen() {
  const theme = useTheme();
  const query = useQuery({ queryKey: ['courses'], queryFn: api.courses });
  // Learning paths are a nicety: without them, the plain list of courses still works.
  const tracks = useQuery({ queryKey: ['tracks'], queryFn: () => api.tracks().catch(() => []) });
  const lessons = useProgressStore((state) => state.lessons);
  const recent = useActivityStore((state) => state.recent);
  const days = useActivityStore((state) => state.days);
  const signedIn = useAuthStore((state) => state.user !== null);
  const deck = useReviewDeck();

  const sections = useMemo(() => {
    const courses = query.data ?? [];
    const bySlug = new Map(courses.map((course) => [course.slug, course]));
    const withCourses = (tracks.data ?? [])
      .map((track) => ({ ...track, courses: track.courses.map((slug) => bySlug.get(slug)).filter((c): c is CourseSummary => Boolean(c)) }))
      .filter((track) => track.courses.length > 0);

    return withCourses.length ? withCourses : [{ slug: 'all', title: '', description: '', courses }];
  }, [query.data, tracks.data]);

  const progressOf = (course: CourseSummary) => courseProgress(course.lessons, lessons);
  const lessonsDone = (query.data ?? []).reduce((sum, course) => sum + progressOf(course).completed, 0);
  const finished = (query.data ?? []).filter((course) => progressOf(course).complete);
  const days$ = streak(days);

  return (
    <ScrollView contentContainerStyle={styles.page}>
      <Text accessibilityRole="header" style={[styles.title, { color: theme.ink }]}>
        Learn programming by running real code.
      </Text>
      <Text style={[styles.intro, { color: theme.muted }]}>
        Each lesson pairs a short explanation with a live sandbox, a quiz, and challenges that check your answers by
        running your code.
      </Text>

      <Pressable
        accessibilityRole="search"
        accessibilityLabel="Search the lessons"
        onPress={() => router.push('/search')}
        style={({ pressed }) => [styles.searchBox, { borderColor: theme.line, backgroundColor: theme.surface, opacity: pressed ? 0.8 : 1 }]}
      >
        <Text style={[styles.meta, { color: theme.muted }]}>Search the lessons…</Text>
      </Pressable>

      {recent ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={`Continue where you left off: ${recent.lessonTitle}, ${recent.courseTitle}`}
          onPress={() => router.push(`/courses/${recent.course}/lessons/${recent.lesson}`)}
          style={({ pressed }) => [
            styles.card,
            styles.review,
            { backgroundColor: theme.highlightSoft, borderColor: theme.highlight, opacity: pressed ? 0.8 : 1 },
          ]}
        >
          <Text style={[styles.meta, { color: theme.ink }]}>Continue where you left off</Text>
          <Text style={[styles.cardTitle, { color: theme.ink }]}>{recent.lessonTitle}</Text>
          <Text style={[styles.meta, { color: theme.ink }]}>{recent.courseTitle}</Text>
        </Pressable>
      ) : null}

      {recent || lessonsDone > 0 ? (
        <View style={[styles.stats, { backgroundColor: theme.surface, borderColor: theme.line }]}>
          {[
            ['Lessons completed', lessonsDone],
            ['Day streak', days$],
            ['Courses finished', finished.length],
          ].map(([label, value]) => (
            <View key={label} style={styles.stat} accessible accessibilityLabel={`${label}: ${value}`}>
              <Text style={[styles.statValue, { color: theme.ink }]}>{value}</Text>
              <Text style={[styles.statLabel, { color: theme.muted }]}>{label}</Text>
            </View>
          ))}
        </View>
      ) : null}

      {deck.items.length ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={`Review, ${plural(deck.due.length, 'card')} due`}
          onPress={() => router.push('/review')}
          style={({ pressed }) => [
            styles.card,
            { backgroundColor: theme.surface, borderColor: theme.line, opacity: pressed ? 0.8 : 1 },
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
        {sections.map((track) => (
          <View key={track.slug} style={styles.list}>
            {track.title ? (
              <View style={styles.trackHeading}>
                <Text accessibilityRole="header" style={[styles.trackTitle, { color: theme.ink }]}>
                  {track.title}
                </Text>
                <Text style={[styles.description, { color: theme.muted }]}>{track.description}</Text>
              </View>
            ) : null}
            {track.courses.map((course) => {
              const progress = progressOf(course);
              const status = progress.complete ? 'Finished' : progress.started ? `${progress.completed} of ${progress.total} done` : null;
              return (
                <Pressable
                  key={`${track.slug}-${course.id}`}
                  accessibilityRole="button"
                  accessibilityLabel={`${course.title}, ${plural(course.lessons_count ?? 0, 'lesson')}${status ? `, ${status}` : ''}`}
                  onPress={() => router.push(`/courses/${course.slug}`)}
                  style={({ pressed }) => [
                    styles.card,
                    { backgroundColor: theme.surface, borderColor: progress.complete ? theme.ok : theme.line, opacity: pressed ? 0.8 : 1 },
                  ]}
                >
                  <Text style={[styles.cardTitle, { color: theme.ink }]}>{course.title}</Text>
                  <Text style={[styles.meta, { color: theme.muted }]}>
                    {plural(course.lessons_count ?? 0, 'lesson')} · {course.label ?? LANGUAGE_NAMES[course.language]}
                    {status ? ` · ${status}` : ''}
                  </Text>
                  <Text style={[styles.description, { color: theme.ink }]}>{course.description}</Text>
                </Pressable>
              );
            })}
          </View>
        ))}
      </LoadState>

      {signedIn && finished.length ? (
        <View style={styles.list}>
          <Text accessibilityRole="header" style={[styles.trackTitle, { color: theme.ink }]}>
            Certificates
          </Text>
          {finished.map((course) => (
            <Pressable
              key={course.slug}
              accessibilityRole="link"
              onPress={() => Linking.openURL(`${baseUrl()}/courses/${course.slug}/certificate`)}
            >
              <Text style={[styles.link, { color: theme.ink }]}>{course.title}: open your certificate</Text>
            </Pressable>
          ))}
        </View>
      ) : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 16, flexGrow: 1 },
  title: { fontSize: 28, fontWeight: '800', lineHeight: 34 },
  intro: { fontSize: 16, lineHeight: 24 },
  searchBox: { borderWidth: 1, borderRadius: 10, paddingHorizontal: 14, minHeight: 44, justifyContent: 'center' },
  list: { gap: 12 },
  trackHeading: { gap: 4, marginTop: 8 },
  trackTitle: { fontSize: 22, fontWeight: '800' },
  card: { borderWidth: 1, borderRadius: 14, padding: 16, gap: 6 },
  review: { borderWidth: 2 },
  stats: { flexDirection: 'row', borderWidth: 1, borderRadius: 14, paddingVertical: 12 },
  stat: { flex: 1, alignItems: 'center', gap: 2 },
  statValue: { fontSize: 24, fontWeight: '800' },
  statLabel: { fontSize: 12, textAlign: 'center' },
  cardTitle: { fontSize: 20, fontWeight: '700' },
  meta: { fontSize: 14 },
  description: { fontSize: 16, lineHeight: 23 },
  link: { fontSize: 16, fontWeight: '600', textDecorationLine: 'underline' },
});
