<?php

namespace App\Exports;

use App\Models\RentRecord;
use App\Models\Property;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OutstandingBalancesExport implements FromCollection, WithHeadings, WithStyles
{
    protected $landlordId;

    public function __construct($landlordId)
    {
        $this->landlordId = $landlordId;
    }

    public function collection(): \Illuminate\Support\Collection
    {
        $propertyIds = Property::where('user_id', $this->landlordId)->pluck('id');

        return RentRecord::whereIn('property_id', $propertyIds)
                          ->whereIn('status', ['pending', 'partial', 'overdue'])
                          ->with(['tenant', 'property', 'unit'])
                          ->get()
                          ->map(function ($r) {
                              return [
                                  'Tenant'    => $r->tenant->full_name,
                                  'Phone'     => $r->tenant->phone,
                                  'Property'  => $r->property->name,
                                  'Unit'      => $r->unit->unit_number,
                                  'Period'    => $r->monthName() . ' ' . $r->year,
                                  'Amount Due'=> 'KES ' . number_format($r->amount_due, 2),
                                  'Paid'      => 'KES ' . number_format($r->amount_paid, 2),
                                  'Balance'   => 'KES ' . number_format($r->balance(), 2),
                                  'Due Date'  => $r->due_date->format('d M Y'),
                                  'Status'    => ucfirst($r->status),
                              ];
                          });
    }

    public function headings(): array
    {
        return [
            'Tenant', 'Phone', 'Property', 'Unit', 'Period',
            'Amount Due', 'Paid', 'Balance', 'Due Date', 'Status'
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
