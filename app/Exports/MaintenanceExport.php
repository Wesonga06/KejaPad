<?php

namespace App\Exports;

use App\Models\MaintenanceRequest;
use App\Models\Property;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaintenanceExport implements FromCollection, WithHeadings, WithStyles
{
    protected $landlordId;

    public function __construct($landlordId)
    {
        $this->landlordId = $landlordId;
    }

    public function collection(): Collection
    {
        $propertyIds = Property::where('user_id', $this->landlordId)->pluck('id');

        return MaintenanceRequest::whereIn('property_id', $propertyIds)
            ->with(['tenant', 'property', 'unit', 'assignedTo'])
            ->get()
            ->map(function ($m) {
                return [
                    'Title' => $m->title,
                    'Tenant' => $m->tenant->full_name,
                    'Property' => $m->property->name,
                    'Unit' => $m->unit->unit_number,
                    'Category' => ucfirst($m->category),
                    'Priority' => ucfirst($m->priority),
                    'Status' => ucfirst(str_replace('_', ' ', $m->status)),
                    'Assigned To' => $m->assignedTo?->name ?? '—',
                    'Submitted' => $m->created_at->format('d M Y'),
                    'Resolved' => $m->resolved_at?->format('d M Y') ?? '—',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Title', 'Tenant', 'Property', 'Unit',
            'Category', 'Priority', 'Status',
            'Assigned To', 'Submitted', 'Resolved',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
