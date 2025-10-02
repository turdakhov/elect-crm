<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Attributes\Scope;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    #[Scope]
    protected function workers(Builder $query)
    {
        $query->where('role', \App\Enums\UserRoleEnum::Worker->name);
    }

    #[Scope]
    protected function managers(Builder $query)
    {
        $query->where('role', \App\Enums\UserRoleEnum::Manager->name);
    }

    #[Scope]
    protected function clients(Builder $query)
    {
        $query->where('role', \App\Enums\UserRoleEnum::Client->name);
    }

    #[Scope]
    protected function designers(Builder $query)
    {
        $query->where('role', \App\Enums\UserRoleEnum::Designer->name);
    }

    #[Scope]
    protected function accountants(Builder $query)
    {
        $query->where('role', \App\Enums\UserRoleEnum::Accountant->name);
    }

    #[Scope]
    protected function supervisors(Builder $query)
    {
        $query->where('role', \App\Enums\UserRoleEnum::Supervisor->name);
    }

    #[Scope]
    protected function foremen(Builder $query)
    {
        $query->where('role', \App\Enums\UserRoleEnum::Foreman->name);
    }

    #[Scope]
    protected function notAdmins(Builder $query)
    {
        $query->whereNot('role', \App\Enums\UserRoleEnum::Admin->name);
    }
}
