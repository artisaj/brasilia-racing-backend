<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $postId = $this->route('post');

        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($postId)],
            'content' => ['required', 'string'],
            'status' => ['nullable', 'in:draft,in_review,published,scheduled'],
            'scheduled_at' => ['nullable', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'cover_focus_x' => ['nullable', 'integer', 'between:0,100'],
            'cover_focus_y' => ['nullable', 'integer', 'between:0,100'],
            'cover_zoom' => ['nullable', 'numeric', 'between:0.5,2'],
        ];
    }
}
