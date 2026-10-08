import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { api } from '../../api/client';
import type { Language, Lesson } from '../../api/types';
import { updateLesson, useLessonProgress } from '../../progress/store';
import { useTheme } from '../../theme';
import { Button } from '../Button';
import { CodeBlock } from '../markdown/CodeBlock';
import { Markdown } from '../markdown/Markdown';

type Props = { lesson: Lesson; language: Language; onNext: () => void; onTry: (code: string) => void };

const LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];

/** One question per page; answers are checked on the server and kept in progress. */
export function QuizStep({ lesson, language, onNext, onTry }: Props) {
  const theme = useTheme();
  const questions = lesson.quiz_questions;
  const progress = useLessonProgress(lesson.id);
  const [page, setPage] = useState(() => {
    const firstOpen = questions.findIndex((q) => !progress.quiz[q.id]);
    return firstOpen === -1 ? 0 : firstOpen;
  });
  const [pending, setPending] = useState(false);
  const [error, setError] = useState('');

  if (questions.length === 0) {
    return (
      <View style={styles.page}>
        <Text style={[styles.text, { color: theme.ink }]}>This lesson has no quiz yet.</Text>
      </View>
    );
  }

  const question = questions[page];
  const answer = progress.quiz[question.id];
  const total = questions.length;
  const answered = questions.filter((q) => progress.quiz[q.id]).length;
  const correct = questions.filter((q) => progress.quiz[q.id]?.correct).length;
  const isLast = page === total - 1;

  function goTo(next: number) {
    setError('');
    setPage(next);
  }

  async function choose(option: number) {
    if (answer || pending) return;
    setPending(true);
    setError('');
    try {
      const result = await api.answer(question.id, option);
      updateLesson(lesson.id, (current) => ({ quiz: { ...current.quiz, [question.id]: { option, ...result } } }));
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Something went wrong. Try again.');
    } finally {
      setPending(false);
    }
  }

  function optionColors(index: number) {
    if (!answer) return { borderColor: theme.line, backgroundColor: theme.surface, opacity: 1 };
    if (index === answer.correct_option) return { borderColor: theme.ok, backgroundColor: theme.okSoft, opacity: 1 };
    if (index === answer.option) return { borderColor: theme.bad, backgroundColor: theme.badSoft, opacity: 1 };
    return { borderColor: theme.line, backgroundColor: theme.surface, opacity: 0.55 };
  }

  return (
    <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Text style={[styles.score, { color: theme.ink }]}>
        {`${correct} of ${total} correct${answered < total ? `, ${total - answered} to go` : ''}`}
      </Text>

      <View style={styles.pager}>
        {questions.map((q, i) => {
          const record = progress.quiz[q.id];
          const color = record ? (record.correct ? theme.ok : theme.bad) : theme.line;
          return (
            <Pressable
              key={q.id}
              accessibilityRole="button"
              accessibilityLabel={`Question ${i + 1}`}
              accessibilityState={{ selected: i === page }}
              onPress={() => goTo(i)}
              style={[
                styles.dot,
                { borderColor: i === page ? theme.ink : color, backgroundColor: record ? color : theme.surface },
              ]}
            >
              <Text style={[styles.dotText, { color: record ? theme.surface : theme.ink }]}>{i + 1}</Text>
            </Pressable>
          );
        })}
      </View>

      <View style={styles.question}>
        <Text style={[styles.questionNumber, { color: theme.muted }]}>{`${page + 1}.`}</Text>
        <View style={styles.optionText}>
          <Markdown source={question.question} inline />
        </View>
      </View>

      {question.code ? (
        <View style={styles.codeGroup}>
          <CodeBlock code={question.code} language={language} />
          {/* The sandbox link appears once answered, so running the code can't give the answer away. */}
          {answer ? <Button title="Try it in the sandbox" onPress={() => onTry(question.code ?? '')} /> : null}
        </View>
      ) : null}

      <View style={styles.options}>
        {question.options.map((option, i) => (
          <Pressable
            key={i}
            accessibilityRole="button"
            accessibilityLabel={`Answer ${LETTERS[i]}`}
            accessibilityState={{ disabled: Boolean(answer) || pending, selected: answer?.option === i }}
            disabled={Boolean(answer) || pending}
            onPress={() => choose(i)}
            style={[styles.option, optionColors(i)]}
          >
            <Text style={[styles.letter, { color: theme.muted }]}>{LETTERS[i]}</Text>
            <View style={styles.optionText}>
              <Markdown source={option} inline />
            </View>
          </Pressable>
        ))}
      </View>

      {error ? (
        <Text accessibilityRole="alert" style={[styles.text, { color: theme.bad }]}>
          {error}
        </Text>
      ) : null}

      {answer ? (
        <View
          style={[
            styles.feedback,
            { backgroundColor: answer.correct ? theme.okSoft : theme.badSoft, borderColor: answer.correct ? theme.ok : theme.bad },
          ]}
        >
          <Text style={[styles.feedbackTitle, { color: theme.ink }]}>{answer.correct ? 'Correct.' : 'Not quite.'}</Text>
          <Markdown source={answer.explanation} inline />
        </View>
      ) : null}

      <View style={styles.nav}>
        {page > 0 ? <Button title="Previous question" onPress={() => goTo(page - 1)} /> : null}
        {isLast ? null : (
          <Button
            variant={answer ? 'primary' : 'default'}
            title={answer ? 'Next question' : 'Skip for now'}
            onPress={() => goTo(page + 1)}
          />
        )}
      </View>

      {isLast && answered === total ? (
        <View style={styles.done}>
          <Text style={[styles.text, { color: theme.ink }]}>
            {`You got ${correct} of ${total} right. ${correct === total ? 'Nicely done.' : 'Red numbers above mark the ones to review. Try again whenever you like.'}`}
          </Text>
          <Button variant="primary" title="Go to the challenges" onPress={onNext} />
          <Button
            title="Retake the quiz"
            onPress={() => {
              updateLesson(lesson.id, () => ({ quiz: {} }));
              goTo(0);
            }}
          />
        </View>
      ) : null}

      {isLast && answered < total ? (
        <Text style={[styles.text, { color: theme.muted }]}>
          {`${total - answered} ${total - answered === 1 ? 'question is' : 'questions are'} still unanswered. Use the numbers above to go back.`}
        </Text>
      ) : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 16, paddingBottom: 48 },
  text: { fontSize: 16, lineHeight: 24 },
  score: { fontSize: 16, fontWeight: '600' },
  pager: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  dot: { width: 36, height: 36, borderRadius: 18, borderWidth: 2, alignItems: 'center', justifyContent: 'center' },
  dotText: { fontSize: 14, fontWeight: '700' },
  codeGroup: { gap: 8 },
  question: { flexDirection: 'row', gap: 8 },
  questionNumber: { fontSize: 16, lineHeight: 24, fontWeight: '700' },
  options: { gap: 8 },
  option: { flexDirection: 'row', alignItems: 'center', gap: 12, borderWidth: 1, borderRadius: 8, padding: 12, minHeight: 48 },
  letter: { fontSize: 14, fontWeight: '700', width: 18 },
  optionText: { flex: 1 },
  feedback: { borderWidth: 1, borderRadius: 8, padding: 12, gap: 6 },
  feedbackTitle: { fontSize: 16, fontWeight: '700' },
  nav: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, justifyContent: 'space-between' },
  done: { gap: 10 },
});
