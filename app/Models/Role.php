<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === config('filament-shield.super_admin.name', 'super_admin');
    }

    public function scopeWithoutSuperAdmin($query): void
    {
        if (auth()->user()->isSuperAdmin()) {
            return;
        }

        $query->where('name', '!=', config('filament-shield.super_admin.name', 'super_admin'));
    }

    public function syncPermissions(mixed ...$permissions): static
    {
        $oldPermissions = $this->permissions->pluck('name');

        parent::syncPermissions(...$permissions);

        $newPermissions = $this->fresh()->permissions->pluck('name');

        $attached = $newPermissions->diff($oldPermissions);
        $detached = $oldPermissions->diff($newPermissions);

        $format = fn ($name) => str($name)->beforeLast(':')->headline();

        if ($detached->isNotEmpty()) {
            $groupDetached = $detached->groupBy(fn ($name) => str($name)->afterLast(':'));

            activity()
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->event('detached')
                ->withProperties([
                    'attributes' => $groupDetached->mapWithKeys(fn ($perms, $group) => [
                        $group => $perms->map($format)->implode(', '),
                    ])->toArray(),
                ])
                ->log('permission detached');
        }

        if ($attached->isNotEmpty()) {
            $groupAttached = $attached->groupBy(fn ($name) => str($name)->afterLast(':'));

            activity()
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->event('attached')
                ->withProperties([
                    'attributes' => $groupAttached->mapWithKeys(fn ($perms, $group) => [
                        $group => $perms->map($format)->implode(', '),
                    ])->toArray(),
                ])
                ->log('permission attached');
        }

        return $this;
    }
}
