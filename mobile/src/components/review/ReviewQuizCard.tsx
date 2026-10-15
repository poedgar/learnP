import { useRef, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { api } from '../../api/client';
import type { Language, QuizAnswer, QuizQuestion } from '../../api/types';
import type { Card, Grade } from '../../review/schedule';
import { useTheme } from '../../theme';
import { Button } from '../Button';
import { CodeBlock } from '../markdown/CodeBlock';
import { Markdown } from '../markdown/Markdown';
import { GradeButtons } from './GradeButtons';

type Props = { question: QuizQuestion; language: Language; card: Card | undefined; onGrade: (grade: Grade) => void };

const letter = (index: number) => String.fromCharCode(65 + index);

function shuffled(count: number): number[] {
  const order = Array.from({ length: count }, (_, i) => i);
  for (let i = order.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [order[i], order[j]] = [order[j], order[i]];
  }
  return order;
}

/**
 * A quiz question asked again, with its options in a new order so the answer can't be
 * remembered by position. The answer is checked on the server but not saved to progress.
 */
export function ReviewQuizCard({ question, language, card, onGrade }: Props) {
  const theme = useTheme();
  const [order] = useState(() => shuffled(question.options.length));
  const [chosen, setChosen] = useState<number | null>(null);
  const [answer, setAnswer] = useState<QuizAnswer | null>(null);
  const [error, setError] = useState('');
  const answering = useRef(false);

  async function choose(option: number) {
    if (answer || answering.current) return;
    answering.current = true;
    setChosen(option);
    setError('');
    try {
      setAnswer(await api.answer(question.id, option));
    } catch (e) {
      setChosen(null);
      setError(e instanceof Error ? e.message : 'Something went wrong. Try again.');
    } finally {
      answering.current = false;
    }
  }

  function colors(option: number) {
    if (!answer) return { borderColor: theme.line, backgroundColor: theme.surface, opacity: 1 };
    if (option === answer.correct_option) return { borderColor: theme.ok, backgroundColor: theme.okSoft, opacity: 1 };
    if (option === chosen) return { borderColor: theme.bad, backgroundColor: theme.badSoft, opacity: 1 };
    return { borderColor: theme.line, backgroundColor: theme.surface, opacity: 0.55 };
  }

  return (
    <View style={styles.card}>
      <Markdown source={question.question} />
      {question.code ? <CodeBlock code={question.code} language={language} /> : null}

      <View style={styles.options}>
        {order.map((option, position) => (
          <Pressable
            key={option}
            accessibilityRole="button"
            accessibilityLabel={`Answer ${letter(position)}`}
            accessibilityState={{ disabled: Boolean(answer) || chosen !== null, selected: chosen === option }}
            disabled={Boolean(answer) || chosen !== null}
            onPress={() => choose(option)}
            style={[styles.option, colors(option)]}
          >
            <Text style={[styles.letter, { color: theme.muted }]}>{letter(position)}</Text>
            <View style={styles.optionText}>
              <Markdown source={question.options[option]} inline />
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
        <>
          <View
            style={[
              styles.feedback,
              { backgroundColor: answer.correct ? theme.okSoft : theme.badSoft, borderColor: answer.correct ? theme.ok : theme.bad },
            ]}
          >
            <Text style={[styles.feedbackTitle, { color: theme.ink }]}>{answer.correct ? 'Correct.' : 'Not quite.'}</Text>
            <Markdown source={answer.explanation} inline />
          </View>
          {answer.correct ? (
            <GradeButtons card={card} grades={['hard', 'good', 'easy']} onGrade={onGrade} />
          ) : (
            <Button variant="primary" title="Continue (see it again soon)" onPress={() => onGrade('again')} />
          )}
        </>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: { gap: 14 },
  text: { fontSize: 16, lineHeight: 24 },
  options: { gap: 8 },
  option: { flexDirection: 'row', alignItems: 'center', gap: 12, borderWidth: 1, borderRadius: 8, padding: 12, minHeight: 48 },
  letter: { fontSize: 14, fontWeight: '700', width: 18 },
  optionText: { flex: 1 },
  feedback: { borderWidth: 1, borderRadius: 8, padding: 12, gap: 6 },
  feedbackTitle: { fontSize: 16, fontWeight: '700' },
});
