<?php

namespace App\Support\Queries;

use App\Http\Requests\Api\PaginatedIndexRequest;
use App\Support\Pagination\PaginatedResponse;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class BaseAdminIndexQuery
{
    use AppliesSearch;
    use AppliesSorting;

    /**
     * @param array<int, string> $searchColumns
     * @param array<int, string> $allowedSortColumns
     * @param Closure(mixed): array<string, mixed> $mapper
     * @return array<string, mixed>
     */
    public function execute(
        Builder $query,
        PaginatedIndexRequest $request,
        array $searchColumns,
        array $allowedSortColumns,
        string $defaultSortColumn,
        Closure $mapper
    ): array {
        $this->applySearch($query, $request->searchTerm(), $searchColumns);
        $this->applySorting($query, $request->sortBy(), $request->sortDirection(), $allowedSortColumns, $defaultSortColumn);

        $paginator = $query->paginate($request->perPage())->appends($request->query());

        $paginator->setCollection(
            $paginator->getCollection()->map($mapper)
        );

        return PaginatedResponse::make($paginator);
    }
}
