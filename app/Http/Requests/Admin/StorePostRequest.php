<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug'],
            'content' => ['required', 'string'],
            'status' => ['nullable', 'in:draft,in_review,published,scheduled'],
            'scheduled_at' => ['nullable', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
