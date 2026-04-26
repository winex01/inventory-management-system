<x-filament-activitylog::entry-content-container :label="__('Attached')">
    @if (isset($record->properties['attributes']))
        <x-filament-activitylog::attributes-table :record="$record->subject" :newAttributes="$record->properties['attributes']" />
    @endif
</x-filament-activitylog::entry-content-container>
