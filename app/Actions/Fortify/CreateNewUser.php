<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    private function generateUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '_');
        $username = $base;
        $counter = 2;

        while (User::where('username', $username)->exists()) {
            $username = "{$base}_{$counter}";
            $counter++;
        }

        return $username;
    }

    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name'     => $input['name'],
            'email'    => $input['email'],
            'password' => $input['password'],
            'username' => $this->generateUsername($input['email']),
        ]);
    }
}
