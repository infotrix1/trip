<?php

declare(strict_types=1);

namespace App\Http\Requests\Map;

use Illuminate\Foundation\Http\FormRequest;

final class SearchLocationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:3', 'max:150'],
            'country_code' => ['sometimes', 'string', 'size:2'],
        ];
    }
}
