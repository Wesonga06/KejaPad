<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnitCapacityService
{
    public function createUnit(Property $property, array $attributes): Unit
    {
        return DB::transaction(function () use ($property, $attributes) {
            $lockedProperty = Property::query()->whereKey($property->id)->lockForUpdate()->firstOrFail();
            $registeredUnits = $lockedProperty->units()->count();

            if ($registeredUnits >= (int) $lockedProperty->total_units) {
                throw ValidationException::withMessages([
                    'capacity' => $this->capacityReachedMessage((int) $lockedProperty->total_units, $registeredUnits),
                ]);
            }

            if ($lockedProperty->units()->where('unit_number', $attributes['unit_number'])->exists()) {
                throw ValidationException::withMessages([
                    'unit_number' => 'This unit number already exists in this property.',
                ]);
            }

            return $lockedProperty->units()->create($attributes);
        });
    }

    public function ensureCapacityCanBeSet(Property $property, int $capacity): void
    {
        $registeredUnits = $property->units()->count();

        if ($capacity < $registeredUnits) {
            throw ValidationException::withMessages([
                'total_units' => "The listed capacity cannot be reduced to {$capacity} because {$registeredUnits} units are already registered. Set capacity to at least {$registeredUnits} units.",
            ]);
        }
    }

    public function capacityReachedMessage(int $capacity, int $registeredUnits): string
    {
        return "You have reached the maximum number of units listed for this property. Your property allows {$capacity} units, and {$registeredUnits} have already been registered.";
    }
}
