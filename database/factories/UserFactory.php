<?php

namespace Database\Factories;

use App\Enums\CreatorType;
use App\Enums\Role;
use App\Enums\UserTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => Role::User,
            'tier' => UserTier::Free,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => Role::Admin,
        ]);
    }

    /**
     * Indicate that the model is a kreator.
     */
    public function kreator(CreatorType|string|null $type = CreatorType::Film): static
    {
        $creatorType = is_string($type) ? CreatorType::tryFrom($type) : $type;

        return $this->state(fn () => [
            'role' => Role::Kreator,
            'creator_type' => $creatorType ?? CreatorType::Film,
        ]);
    }

    /**
     * Indicate that the model is a film kreator.
     */
    public function filmKreator(): static
    {
        return $this->kreator(CreatorType::Film);
    }

    /**
     * Indicate that the model is a novel kreator.
     */
    public function novelKreator(): static
    {
        return $this->kreator(CreatorType::Novel);
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'tier' => UserTier::Free,
        ]);
    }

    public function pro(): static
    {
        return $this->state(fn () => [
            'tier' => UserTier::Pro,
        ]);
    }

    public function vip(): static
    {
        return $this->state(fn () => [
            'tier' => UserTier::Vip,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static {}
}
