<?php

declare(strict_types=1);

namespace App\Http\Requests\Destination;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderDestinationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'destination_ids' => ['required', 'array', 'min:1'],
            'destination_ids.*' => ['required', 'integer', 'distinct', 'exists:destinations,id'],
        ];
    }
}
