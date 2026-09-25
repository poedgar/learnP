<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait SyncsLessonContent
{
    /**
     * Upserts rows by position so IDs stay stable across re-seeds; learner
     * progress in the browser is keyed by those IDs.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    protected function syncByPosition(HasMany $relation, array $rows): void
    {
        foreach ($rows as $row) {
            $relation->updateOrCreate(['position' => $row['position']], $row);
        }

        $relation->where('position', '>', count($rows))->delete();
    }

    /**
     * Numbers rows from 1 in the order they're listed.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function positioned(array $rows): array
    {
        return array_map(
            fn (array $row, int $index) => [...$row, 'position' => $index + 1],
            $rows,
            array_keys($rows),
        );
    }
}
