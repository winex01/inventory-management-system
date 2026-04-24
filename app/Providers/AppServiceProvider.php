<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Filament\Support\Enums\Width;
use Illuminate\Support\ServiceProvider;
use Swis\Filament\Activitylog\Actions\ActivitylogAction;
use Swis\Filament\Activitylog\AttributeTable\Builder;
use Swis\Filament\Activitylog\Facades\FilamentActivitylog;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentActivitylog::registerAttributeTableValueFormatter(
            function (Builder $builder, mixed $value, string $key, array $attributes, string $recordClass) {
                $timestampKeys = ['created_at', 'updated_at', 'deleted_at'];

                if (in_array($key, $timestampKeys) && !empty($value)) {
                    try {
                        return Carbon::parse($value)
                            ->setTimezone(config('app.timezone'))
                            ->format('Y-m-d H:i:s');
                    } catch (\Exception) {
                        return $value;
                    }
                }

                if ($key === 'causer' && $value instanceof User) {
                    return "User: {$value->name} ({$value->email})";
                }

                return null;
            }
        );

        ActivitylogAction::configureUsing(function (ActivitylogAction $action) {
            $action->modalWidth(Width::ThreeExtraLarge);
        });
    }
}
