<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Word-by-word search with names that start with the term listed first.
 *
 * Typing "screen" at the counter should put "Screen Assembly - …" and
 * "Screen Replacement" at the top, not wherever they fall alphabetically
 * among everything that merely contains the word. And "screen iphone 15"
 * should find "Screen Assembly - Apple iPhone 15", which a single
 * LIKE '%screen iphone 15%' never matches — so each word is matched on its
 * own, against any of the searchable columns.
 */
trait RankedSearch
{
    /**
     * @param  array<int, string>  $columns
     */
    protected static function applyRankedSearch(Builder $query, ?string $term, array $columns, string $nameColumn = 'name'): Builder
    {
        $term = trim(preg_replace('/\s+/', ' ', (string) $term));

        if ($term === '') {
            return $query;
        }

        foreach (explode(' ', $term) as $word) {
            $query->where(function (Builder $q) use ($word, $columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', '%'.$word.'%');
                }
            });
        }

        $table = $query->getModel()->getTable();

        return $query
            ->orderByRaw(
                "CASE WHEN {$table}.{$nameColumn} LIKE ? THEN 0 WHEN {$table}.{$nameColumn} LIKE ? THEN 1 ELSE 2 END",
                [$term.'%', '% '.$term.'%'],
            )
            ->orderBy("{$table}.{$nameColumn}");
    }
}
