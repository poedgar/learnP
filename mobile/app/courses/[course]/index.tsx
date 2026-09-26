import { useLocalSearchParams } from 'expo-router';
import { CourseScreen } from '../../../src/screens/CourseScreen';

export default function CourseRoute() {
  const { course } = useLocalSearchParams<{ course: string }>();
  return <CourseScreen slug={course} />;
}
