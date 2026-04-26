<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(config('filament-shield.super_admin.name', 'super_admin'));
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    public function update(AuthUser $authUser, User $targetUser): bool
    {
        if ($this->isSuperAdmin($targetUser) && !$this->isSuperAdmin($authUser)) {
            return false;
        }

        return $authUser->can('Update:User');
    }
    public function delete(AuthUser $authUser, User $targetUser): bool
    {
        if ($this->isSuperAdmin($targetUser) && !$this->isSuperAdmin($authUser)) {
            return false;
        }

        return $authUser->can('Delete:User');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:User');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:User');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:User');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:User');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User');
    }

    public function viewActivitylog(AuthUser $authUser, User $targetUser): bool
    {
        if ($this->isSuperAdmin($targetUser) && !$this->isSuperAdmin($authUser)) {
            return false;
        }

        return $authUser->can('ViewLog:User');
    }

    public function commentActivitylog(AuthUser $authUser): bool
    {
        return $authUser->can('CommentLog:User');
    }

}
