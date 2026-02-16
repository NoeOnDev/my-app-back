<?php

namespace App\Support\Queries;

use Illuminate\Database\Eloquent\Builder;

trait AppliesSorting
{
    /**
     * @param array<int, string> $allowedColumns
     */
    protected function applySorting(
        Builder $query,
        ?string $sortBy,
        string $sortDir,
        array $allowedColumns,
        string $defaultColumn,
        string $defaultDirection = 'asc'
    ): void {
        $column = in_array($sortBy, $allowedColumns, true) ? $sortBy : $defaultColumn;
        $direction = in_array(strtolower($sortDir), ['asc', 'desc'], true) ? strtolower($sortDir) : strtolower($defaultDirection);

        $query->orderBy($column, $direction);
    }
}
