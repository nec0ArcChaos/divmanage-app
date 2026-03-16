<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->workspace_id !== null;
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'priority'       => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'project_id'     => ['required', 'integer', 'exists:projects,id'],
            'task_status_id' => ['required', 'integer', 'exists:task_statuses,id'],
            'deadline'       => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'priority.in' => 'Priority must be one of: low, medium, high, critical.',
        ];
    }
}
