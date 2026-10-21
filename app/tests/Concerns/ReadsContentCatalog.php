<?php

namespace Tests\Concerns;

use App\Models\Course;

/**
 * The course catalogue as the files in database/content lay it out: a folder per course,
 * named after its slug, and a Markdown file per lesson, named NN-slug.md and taken in
 * order. Content tests compare the seeded database with this, so adding a course or a
 * lesson needs no test changes.
 */
trait ReadsContentCatalog
{
    /**
     * @return array<string, list<string>> lesson slugs in order, keyed by course slug
     */
    protected function contentCatalog(): array
    {
        $catalog = [];
        foreach (glob(database_path('content/*'), GLOB_ONLYDIR) as $directory) {
            $catalog[basename($directory)] = array_map(
                fn (string $file) => preg_replace('/^\d+-/', '', basename($file, '.md')),
                glob($directory.'/*.md'),
            );
        }

        return $catalog;
    }

    /**
     * The catalogue's courses whose seeded course has one of these languages.
     *
     * @return array<string, list<string>>
     */
    protected function catalogFor(string ...$languages): array
    {
        $slugs = Course::whereIn('language', $languages)->pluck('slug')->all();

        return array_intersect_key($this->contentCatalog(), array_flip($slugs));
    }
}
