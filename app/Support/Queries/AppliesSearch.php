<?php

namespace App\Support\Queries;

use Illuminate\Database\Eloquent\Builder;

trait AppliesSearch
{
    /**
     * @param array<int, string> $columns
     */
    protected function applySearch(Builder $query, ?string $search, array $columns): void
    {
        if (! $search || count($columns) === 0) {
            return;
        }

        $query->where(function (Builder $builder) use ($columns, $search): void {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $builder->where($column, 'like', '%'.$search.'%');

                    continue;
                }

                $builder->orWhere($column, 'like', '%'.$search.'%');
            }
        });
    }
}
