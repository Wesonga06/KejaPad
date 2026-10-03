<?php

namespace App\Exports;

use App\Models\Unit;
use App\Models\Property;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OccupancyExport implements FromCollection, WithHeadings, WithStyles
{
    protected $landlordId;

    public function __construct($landlordId)
    {
        $this->landlordId = $landlordId;
    }

    public function collection(): \Illuminate\Support\Collection
    {
        $propertyIds = Property::where('user_id', $this->landlordId)->pluck('id');

        return Unit::whereIn('property_id', $propertyIds)
                    ->with(['property', 'tenant'])
                    ->get()
                    ->map(function ($u) {
                        return [
                            'Property'    => $u->property->name,
                            'Unit'        => $u->unit_number,
                            'Rent Amount' => 'KES ' . number_format($u->rent_amount, 2),
                            'Status'      => ucfirst($u->status),
                            'Tenant'      => $u->tenant?->full_name ?? '—',
                            'Tenant Phone'=> $u->tenant?->phone ?? '—',
                            'Move-in Date'=> $u->tenant?->move_in_date?->format('d M Y') ?? '—',
                        ];
                    });
    }

    public function headings(): array
    {
        return [
            'Property', 'Unit', 'Rent Amount', 'Status',
            'Tenant', 'Tenant Phone', 'Move-in Date'
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
