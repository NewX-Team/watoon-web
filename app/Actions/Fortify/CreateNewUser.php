<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\CreatorType;
use App\Enums\Role;
use App\Enums\UserTier;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $role = isset($input['role']) && in_array($input['role'], [Role::User->value, Role::Kreator->value], true)
            ? Role::from($input['role'])
            : Role::User;

        $creatorType = null;
        if ($role === Role::Kreator) {
            $creatorType = isset($input['creator_type']) && in_array($input['creator_type'], [CreatorType::Film->value, CreatorType::Novel->value], true)
                ? CreatorType::from($input['creator_type'])
                : CreatorType::Film;
        }

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => $role,
            'creator_type' => $creatorType,
            'tier' => UserTier::Free,
            'subscription_expires_at' => null,
        ]);
    }
}
