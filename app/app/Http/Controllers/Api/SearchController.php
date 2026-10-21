<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Finds lessons by their title, summary or theory, best matches first. */
class SearchController extends Controller
{
    private const LIMIT = 20;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $query = trim($validated['q']);
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query).'%';
        $matches = fn (string $column) => fn (Builder $q) => $q->whereRaw("$column LIKE ? ESCAPE '!'", [$like]);

        $lessons = Lesson::with('course:id,slug,title,position')
            ->where(fn (Builder $q) => $q->where($matches('title'))->orWhere($matches('summary'))->orWhere($matches('content')))
            ->get(['id', 'course_id', 'slug', 'title', 'summary', 'content', 'position']);

        $results = $lessons
            ->map(function (Lesson $lesson) use ($query) {
                $rank = match (true) {
                    Str::contains($lesson->title, $query, true) => 0,
                    Str::contains($lesson->summary, $query, true) => 1,
                    default => 2,
                };

                return [
                    'rank' => $rank,
                    'course' => ['slug' => $lesson->course->slug, 'title' => $lesson->course->title, 'position' => $lesson->course->position],
                    'lesson' => ['slug' => $lesson->slug, 'title' => $lesson->title, 'position' => $lesson->position],
                    'snippet' => $rank === 2 ? $this->snippet($lesson->content, $query) : $lesson->summary,
                ];
            })
            ->sortBy([['rank', 'asc'], ['course.position', 'asc'], ['lesson.position', 'asc']])
            ->take(self::LIMIT)
            ->map(fn (array $result) => collect($result)->except('rank')->all())
            ->values();

        return response()->json(['data' => $results]);
    }

    /** About 160 characters of plain text around the first match in the theory. */
    private function snippet(string $markdown, string $query): string
    {
        $text = trim(preg_replace('/\s+/', ' ', preg_replace('/[#*`>|\[\]]/', '', $markdown)));
        $at = mb_stripos($text, $query) ?: 0;
        $start = max(0, $at - 60);
        $snippet = mb_substr($text, $start, 160);

        return ($start > 0 ? '…' : '').$snippet.($start + 160 < mb_strlen($text) ? '…' : '');
    }
}
