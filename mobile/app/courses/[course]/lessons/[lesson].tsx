import { useLocalSearchParams } from 'expo-router';
import { LessonScreen } from '../../../../src/screens/LessonScreen';

export default function LessonRoute() {
  const { course, lesson, step } = useLocalSearchParams<{ course: string; lesson: string; step?: string }>();
  // A new lesson starts with a fresh screen (visited steps, sandbox request).
  return <LessonScreen key={`${course}/${lesson}`} courseSlug={course} lessonSlug={lesson} step={step} />;
}
