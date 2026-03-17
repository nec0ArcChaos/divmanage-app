<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && in_array(Auth::user()->global_role, ['admin', 'project_manager']);
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'username'    => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')],
            'email'       => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'job_title'   => ['required', 'string', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'global_role' => ['required', Rule::in(['admin', 'project_manager', 'developer', 'qa'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Full name is required.',
            'username.required'    => 'Username is required.',
            'username.alpha_dash'  => 'Username may only contain letters, numbers, dashes and underscores.',
            'username.unique'      => 'This username is already taken.',
            'email.required'       => 'Email address is required.',
            'email.email'          => 'Please enter a valid email address.',
            'email.unique'         => 'This email address is already registered.',
            'job_title.required'   => 'Job title / field is required.',
            'global_role.required' => 'Role is required.',
            'global_role.in'       => 'Invalid role selected.',
        ];
    }
}
