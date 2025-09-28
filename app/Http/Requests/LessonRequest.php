<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'exam' => 'required|string|max:255',
            'courseSlug' => 'required|string|max:255',
            'lesson' => 'sometimes|exists:lessons,slug',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'exam.required' => 'Exam parameter is required.',
            'courseSlug.required' => 'Course slug is required.',
            'courseSlug.string' => 'Course slug must be a string.',
            'lesson.exists' => 'The selected lesson does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'exam' => 'exam',
            'courseSlug' => 'course slug',
            'lesson' => 'lesson',
        ];
    }
}