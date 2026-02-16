<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PaginatedIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_dir' => ['sometimes', 'nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 15);
    }

    public function searchTerm(): ?string
    {
        $search = $this->validated('search');

        return is_string($search) && $search !== '' ? $search : null;
    }

    public function sortBy(): ?string
    {
        $sortBy = $this->validated('sort_by');

        return is_string($sortBy) && $sortBy !== '' ? $sortBy : null;
    }

    public function sortDirection(): string
    {
        return (string) ($this->validated('sort_dir') ?? 'asc');
    }
}
