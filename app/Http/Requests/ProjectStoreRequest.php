<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProjectStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->workspace_id !== null;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status'      => ['required', Rule::in(['planning', 'active', 'maintenance', 'completed', 'archived'])],
            'color'       => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'start_date'  => ['nullable', 'date', 'before_or_equal:deadline'],
            'deadline'    => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'color.regex'                => 'Color must be a valid hex code (e.g. #3b6ff8).',
            'start_date.before_or_equal' => 'Start date must be before or equal to the deadline.',
            'deadline.after_or_equal'    => 'Deadline must be on or after the start date.',
        ];
    }
}
