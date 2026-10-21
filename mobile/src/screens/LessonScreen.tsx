import { useQuery } from '@tanstack/react-query';
import { router, Stack } from 'expo-router';
import { useState, type ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';
import { api } from '../api/client';
import { LoadState } from '../components/LoadState';
import { ChallengesStep } from '../components/lesson/ChallengesStep';
import { LearnStep } from '../components/lesson/LearnStep';
import { QuizStep } from '../components/lesson/QuizStep';
import { SandboxStep } from '../components/lesson/SandboxStep';
import { StepSwitcher, type Step } from '../components/lesson/StepSwitcher';
import { updateLesson, useLessonProgress } from '../progress/store';
import { lessonSummary } from '../progress/summary';

const STEPS: Step[] = ['learn', 'sandbox', 'quiz', 'challenges'];

type Props = { courseSlug: string; lessonSlug: string; step?: string };

/**
 * A lesson's four steps. The current step lives in the URL (?step=), and steps
 * stay mounted once opened, so editor contents survive switching tabs.
 */
export function LessonScreen({ courseSlug, lessonSlug, step: stepParam }: Props) {
  const query = useQuery({
    queryKey: ['lesson', courseSlug, lessonSlug],
    queryFn: () => api.lesson(courseSlug, lessonSlug),
  });
  const step: Step = STEPS.includes(stepParam as Step) ? (stepParam as Step) : 'learn';
  const [visited, setVisited] = useState<Set<Step>>(() => new Set([step]));
  // Steps stay mounted once visited, so their state survives switching back and forth.
  if (!visited.has(step)) {
    setVisited(new Set(visited).add(step));
  }
  const [sandboxRequest, setSandboxRequest] = useState<{ code: string; key: number } | null>(null);
  const lesson = query.data?.data;
  const progress = useLessonProgress(lesson?.id ?? -1);

  const goTo = (next: Step) => router.setParams({ step: next });
  const tryInSandbox = (code: string) => {
    setSandboxRequest({ code, key: Date.now() });
    goTo('sandbox');
  };

  let content: ReactNode = null;
  if (lesson && query.data) {
    const language = lesson.course.language;
    const schema = query.data.schema;
    const summary = lessonSummary(lesson, progress);
    const views: Record<Step, () => ReactNode> = {
      learn: () => (
        <LearnStep
          lesson={lesson}
          language={language}
          onTry={tryInSandbox}
          onFinish={() => {
            updateLesson(lesson.id, () => ({ read: true }));
            goTo('sandbox');
          }}
          onSkip={() => goTo('quiz')}
        />
      ),
      sandbox: () => (
        <SandboxStep lesson={lesson} language={language} schema={schema} request={sandboxRequest} />
      ),
      quiz: () => <QuizStep lesson={lesson} language={language} onNext={() => goTo('challenges')} onTry={tryInSandbox} />,
      challenges: () => <ChallengesStep lesson={lesson} language={language} />,
    };

    content = (
      <View style={styles.flex}>
        <StepSwitcher
          current={step}
          onChange={goTo}
          steps={[
            { key: 'learn', label: 'Learn', status: summary.read ? 'Read' : '' },
            { key: 'sandbox', label: 'Sandbox', status: '' },
            { key: 'quiz', label: 'Quiz', status: summary.quizAnswered ? `${summary.quizCorrect}/${summary.quizTotal}` : '' },
            {
              key: 'challenges',
              label: 'Challenges',
              status: summary.challengesPassed ? `${summary.challengesPassed}/${summary.challengeTotal}` : '',
            },
          ]}
        />
        {STEPS.filter((s) => visited.has(s)).map((s) => (
          <View key={s} style={[styles.flex, s !== step && styles.hidden]}>
            {views[s]()}
          </View>
        ))}
      </View>
    );
  }

  return (
    <>
      <Stack.Screen options={{ title: lesson?.title ?? '' }} />
      <LoadState
        loading={query.isPending}
        error={query.error}
        notFound="We couldn't find that lesson."
        onRetry={() => query.refetch()}
      >
        {content}
      </LoadState>
    </>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  hidden: { display: 'none' },
});
