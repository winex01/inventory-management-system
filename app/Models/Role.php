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

    public function scopeWithoutSuperAdmin($query): void
    {
        $query->where('name', '!=', config('filament-shield.super_admin.name', 'super_admin'));
    }

    public function syncPermissions(mixed ...$permissions): static
    {
        $oldPermissions = $this->permissions->pluck('name');

        parent::syncPermissions(...$permissions);

        $newPermissions = $this->fresh()->permissions->pluck('name');

        $attached = $newPermissions->diff($oldPermissions);
        $detached = $oldPermissions->diff($newPermissions);

        if ($attached->isNotEmpty() || $detached->isNotEmpty()) {
            $groupOld = $oldPermissions->groupBy(fn ($name) => str($name)->afterLast(':'));
            $groupNew = $newPermissions->groupBy(fn ($name) => str($name)->afterLast(':'));

            $allGroups = $groupOld->keys()->merge($groupNew->keys())->unique();

            $format = fn ($name) => str($name)->beforeLast(':')->headline();

            activity()
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->event('updated')
                ->withProperties([
                    'old' => $allGroups->mapWithKeys(fn ($group) => [
                        $group => $groupOld->get($group, collect())->map($format)->implode(', '),
                    ])->toArray(),
                    'attributes' => $allGroups->mapWithKeys(fn ($group) => [
                        $group => $groupNew->get($group, collect())->map($format)->implode(', '),
                    ])->toArray(),
                ])
                ->log('permission ' . ($attached->isNotEmpty() ? 'attached' : 'detached'));
        }

        return $this;
    }
}
