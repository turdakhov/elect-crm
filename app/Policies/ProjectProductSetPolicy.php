<?php

namespace App\Policies;

use App\Models\ProjectProductSet;
use App\Models\User;

class ProjectProductSetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProjectProductSet $projectProductSet): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ProjectProductSet $projectProductSet): bool
    {
        return true;
    }

    public function delete(User $user, ProjectProductSet $projectProductSet): bool
    {
        return true;
    }

    public function restore(User $user, ProjectProductSet $projectProductSet): bool
    {
        return true;
    }

    public function forceDelete(User $user, ProjectProductSet $projectProductSet): bool
    {
        return true;
    }
}
