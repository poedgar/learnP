import { useEffect, useMemo, useRef } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import type { Language, Lesson } from '../../api/types';
import { splitSections } from '../../lesson/sections';
import { updateLesson, useLessonProgress } from '../../progress/store';
import { useTheme } from '../../theme';
import { Button } from '../Button';
import { Markdown } from '../markdown/Markdown';

type Props = {
  lesson: Lesson;
  language: Language;
  onTry: (code: string) => void;
  onFinish: () => void;
  onSkip: () => void;
};

/** The lesson theory, one "## " section per page, as in the web app. */
export function LearnStep({ lesson, language, onTry, onFinish, onSkip }: Props) {
  const theme = useTheme();
  const progress = useLessonProgress(lesson.id);
  const sections = useMemo(() => splitSections(lesson.content, lesson.title), [lesson.content, lesson.title]);
  const page = Math.min(Math.max(progress.theoryPage, 1), sections.length);
  const scroll = useRef<ScrollView>(null);

  useEffect(() => {
    scroll.current?.scrollTo({ y: 0, animated: false });
  }, [page]);

  const setPage = (next: number) => updateLesson(lesson.id, () => ({ theoryPage: next }));
  const current = sections[page - 1];
  const previous = sections[page - 2];
  const next = sections[page];

  return (
    <ScrollView ref={scroll} contentContainerStyle={styles.page}>
      <Text style={[styles.position, { color: theme.muted }]}>{`Section ${page} of ${sections.length}`}</Text>
      <Markdown source={current.source} runnable={language} onTry={onTry} />
      <View style={styles.nav}>
        {previous ? <Button title={`Previous: ${previous.title}`} onPress={() => setPage(page - 1)} /> : null}
        {next ? <Button variant="primary" title={`Next: ${next.title}`} onPress={() => setPage(page + 1)} /> : null}
      </View>
      {next ? null : (
        <View style={[styles.finish, { borderColor: theme.line }]}>
          <Text style={[styles.finishText, { color: theme.ink }]}>
            {"That's the end of the reading. Try what you've learned in the sandbox, then take the quiz."}
          </Text>
          <Button
            variant="primary"
            title={progress.read ? 'Open the sandbox' : 'Mark as read and open the sandbox'}
            onPress={onFinish}
          />
          <Button title="Go to the quiz" onPress={onSkip} />
        </View>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 18, paddingBottom: 48 },
  position: { fontSize: 14 },
  nav: { gap: 10 },
  finish: { borderTopWidth: 1, paddingTop: 18, gap: 10 },
  finishText: { fontSize: 16, lineHeight: 24 },
});
