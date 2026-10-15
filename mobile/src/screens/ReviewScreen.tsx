import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useMemo, useRef, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { api } from '../api/client';
import type { Challenge, QuizQuestion, ReviewLesson } from '../api/types';
import { Button } from '../components/Button';
import { LoadState } from '../components/LoadState';
import { ReviewChallengeCard } from '../components/review/ReviewChallengeCard';
import { ReviewQuizCard } from '../components/review/ReviewQuizCard';
import { plural } from '../lib/text';
import { useReviewDeck, type ReviewItem } from '../review/deck';
import { describeDue, nextDue, type Grade } from '../review/schedule';
import { useReviewStore } from '../review/store';
import { useTheme } from '../theme';

/** How many not-yet-due items "Practise anyway" brings forward. */
const PRACTICE_SIZE = 10;

type Content =
  | { kind: 'quiz'; item: ReviewItem; lesson: ReviewLesson; question: QuizQuestion }
  | { kind: 'challenge'; item: ReviewItem; lesson: ReviewLesson; challenge: Challenge };

const plainText = (markdown: string) => markdown.replace(/[`*_]/g, '');
const capitalise = (text: string) => text.charAt(0).toUpperCase() + text.slice(1);

/** Every passed quiz question and challenge, with spaced-repetition reviews (see src/review). */
export function ReviewScreen() {
  const theme = useTheme();
  const now = Date.now();
  const { items, cards, due } = useReviewDeck(now);
  const grade = useReviewStore((state) => state.grade);
  const [queue, setQueue] = useState<string[] | null>(null);
  const [position, setPosition] = useState(0);
  const [reviewed, setReviewed] = useState(0);
  const scroll = useRef<ScrollView>(null);

  const quizIds = items.filter((i) => i.kind === 'quiz').map((i) => i.id);
  const challengeIds = items.filter((i) => i.kind === 'challenge').map((i) => i.id);
  const query = useQuery({
    queryKey: ['review-items', quizIds, challengeIds],
    queryFn: () => api.reviewItems(quizIds, challengeIds),
    enabled: items.length > 0,
  });

  // Items whose content no longer exists on the server (a lesson was rewritten) are left out.
  const content = useMemo(() => {
    const byKey = new Map<string, Content>();
    if (!query.data) return byKey;
    const lessons = new Map(query.data.lessons.map((lesson) => [lesson.id, lesson]));
    const questions = new Map(query.data.quiz_questions.map((q) => [q.id, q]));
    const challenges = new Map(query.data.challenges.map((c) => [c.id, c]));
    for (const item of items) {
      const question = item.kind === 'quiz' ? questions.get(item.id) : undefined;
      const challenge = item.kind === 'challenge' ? challenges.get(item.id) : undefined;
      const lesson = lessons.get(question?.lesson_id ?? challenge?.lesson_id ?? -1);
      if (lesson && question) byKey.set(item.key, { kind: 'quiz', item, lesson, question });
      if (lesson && challenge) byKey.set(item.key, { kind: 'challenge', item, lesson, challenge });
    }
    return byKey;
  }, [query.data, items]);

  const known = items.filter((item) => content.has(item.key));
  const dueKnown = due.filter((item) => content.has(item.key));

  function start(keys: string[]) {
    setQueue(keys);
    setPosition(0);
    setReviewed(0);
  }

  function practiseAhead() {
    const soonest = [...known].sort((a, b) => (cards[a.key]?.due ?? 0) - (cards[b.key]?.due ?? 0));
    start(soonest.slice(0, PRACTICE_SIZE).map((item) => item.key));
  }

  function onGrade(key: string, value: Grade) {
    grade(key, value);
    setReviewed((count) => count + 1);
    // Like Anki, a missed card comes back before the session ends.
    if (value === 'again') setQueue((current) => [...(current ?? []), key]);
    setPosition((current) => current + 1);
    scroll.current?.scrollTo({ y: 0, animated: false });
  }

  if (items.length === 0) {
    return (
      <View style={styles.empty}>
        <Text style={[styles.title, { color: theme.ink }]}>Nothing to review yet</Text>
        <Text style={[styles.text, { color: theme.muted }]}>
          Quiz questions you answer correctly and challenges you solve will appear here, ready to review at growing
          intervals so you keep remembering them.
        </Text>
        <Button variant="primary" title="Browse courses" onPress={() => router.navigate('/')} />
      </View>
    );
  }

  if (queue) {
    const key = queue[position];
    const current = key ? content.get(key) : undefined;

    if (!current) {
      return (
        <View style={styles.empty}>
          <Text style={[styles.title, { color: theme.ink }]}>Review done</Text>
          <Text style={[styles.text, { color: theme.muted }]}>{`You reviewed ${plural(reviewed, 'card')}. Nicely done.`}</Text>
          <Button variant="primary" title="Back to the overview" onPress={() => setQueue(null)} />
        </View>
      );
    }

    return (
      <ScrollView ref={scroll} contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
        <View style={styles.sessionHead}>
          <Text style={[styles.meta, { color: theme.muted }]}>
            {`Card ${position + 1} of ${queue.length} · ${current.lesson.course.title}, ${current.lesson.title}`}
          </Text>
          <Button variant="quiet" title="End review" onPress={() => setQueue(null)} />
        </View>
        {current.kind === 'quiz' ? (
          <ReviewQuizCard
            key={`${key}-${position}`}
            question={current.question}
            language={current.lesson.course.language}
            card={cards[key]}
            onGrade={(value) => onGrade(key, value)}
          />
        ) : (
          <ReviewChallengeCard
            key={`${key}-${position}`}
            challenge={current.challenge}
            lessonId={current.lesson.id}
            language={current.lesson.course.language}
            card={cards[key]}
            onGrade={(value) => onGrade(key, value)}
          />
        )}
      </ScrollView>
    );
  }

  const upcoming = nextDue(known, cards, now);
  const groups = groupByLesson(known, content);

  return (
    <ScrollView contentContainerStyle={styles.page}>
      <LoadState loading={query.isPending} error={query.error} onRetry={() => query.refetch()}>
        <View style={[styles.summary, { backgroundColor: theme.surface, borderColor: theme.line }]}>
          <Text style={[styles.title, { color: theme.ink }]}>
            {dueKnown.length ? `${plural(dueKnown.length, 'card')} to review` : 'All caught up'}
          </Text>
          <Text style={[styles.text, { color: theme.muted }]}>
            {`${plural(known.length, 'item')} passed so far.${
              !dueKnown.length && upcoming ? ` The next review is due ${describeDue(now, upcoming)}.` : ''
            }`}
          </Text>
          {dueKnown.length ? (
            <Button variant="primary" title="Start review" onPress={() => start(dueKnown.map((item) => item.key))} />
          ) : (
            <Button title="Practise anyway" onPress={practiseAhead} />
          )}
        </View>

        {groups.map(({ lesson, entries }) => (
          <View key={lesson.id} style={styles.group}>
            <Text style={[styles.groupTitle, { color: theme.ink }]}>{lesson.title}</Text>
            <Text style={[styles.meta, { color: theme.muted }]}>{lesson.course.title}</Text>
            {entries.map((entry) => {
              const card = cards[entry.item.key];
              const status = !card ? 'New' : card.due <= now ? 'Due' : capitalise(describeDue(now, card.due));
              return (
                <View key={entry.item.key} style={[styles.row, { borderColor: theme.line }]}>
                  <Text style={[styles.kind, { color: theme.muted }]}>{entry.kind === 'quiz' ? 'Quiz' : 'Challenge'}</Text>
                  <Text style={[styles.rowText, { color: theme.ink }]} numberOfLines={2}>
                    {entry.kind === 'quiz' ? plainText(entry.question.question) : entry.challenge.title}
                  </Text>
                  <Text style={[styles.status, { color: card && card.due > now ? theme.muted : theme.ok }]}>{status}</Text>
                </View>
              );
            })}
          </View>
        ))}
      </LoadState>
    </ScrollView>
  );
}

/** Items grouped by lesson, in course and lesson order. */
function groupByLesson(items: ReviewItem[], content: Map<string, Content>) {
  const groups = new Map<number, { lesson: ReviewLesson; entries: Content[] }>();
  for (const item of items) {
    const entry = content.get(item.key);
    if (!entry) continue;
    const group = groups.get(entry.lesson.id) ?? { lesson: entry.lesson, entries: [] };
    group.entries.push(entry);
    groups.set(entry.lesson.id, group);
  }
  return [...groups.values()].sort(
    (a, b) => a.lesson.course.position - b.lesson.course.position || a.lesson.position - b.lesson.position,
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 16, paddingBottom: 120 },
  empty: { flex: 1, padding: 24, gap: 14, justifyContent: 'center' },
  title: { fontSize: 22, fontWeight: '800' },
  text: { fontSize: 16, lineHeight: 24 },
  meta: { fontSize: 14 },
  summary: { borderWidth: 1, borderRadius: 14, padding: 16, gap: 10 },
  sessionHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  group: { gap: 6 },
  groupTitle: { fontSize: 18, fontWeight: '700' },
  row: { flexDirection: 'row', alignItems: 'center', gap: 10, borderTopWidth: 1, paddingVertical: 10 },
  kind: { fontSize: 13, width: 70 },
  rowText: { flex: 1, fontSize: 15, lineHeight: 21 },
  status: { fontSize: 13, fontWeight: '600' },
});
