<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\CategoryData;
use App\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories')->where(function ($query) {
                    return $query->where('user_id', $this->user()?->id)
                        ->where('type', $this->input('type'));
                }),
            ],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'icon' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:20'],
        ];
    }

    /**
     * Convert validated input to CategoryData DTO.
     */
    public function toCategoryData(): CategoryData
    {
        return new CategoryData(
            name: (string) $this->input('name'),
            type: CategoryType::from((string) $this->input('type')),
            icon: (string) $this->input('icon'),
            color: (string) $this->input('color'),
        );
    }
}
