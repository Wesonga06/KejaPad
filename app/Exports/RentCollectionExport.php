<?php

namespace App\Exports;

use App\Models\Property;
use App\Models\RentRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RentCollectionExport implements FromCollection, WithHeadings, WithStyles
{
    protected $month;

    protected $year;

    protected $propertyId;

    protected $landlordId;

    public function __construct($month, $year, $propertyId, $landlordId)
    {
        $this->month = $month;
        $this->year = $year;
        $this->propertyId = $propertyId;
        $this->landlordId = $landlordId;
    }

    public function collection(): Collection
    {
        $propertyIds = Property::where('user_id', $this->landlordId)->pluck('id');

        $query = RentRecord::whereIn('property_id', $propertyIds)
            ->with(['tenant', 'property', 'unit']);

        if ($this->month) {
            $query->where('month', $this->month);
        }
        if ($this->year) {
            $query->where('year', $this->year);
        }
        if ($this->propertyId) {
            $query->where('property_id', $this->propertyId);
        }

        return $query->get()->map(function ($r) {
            return [
                'Tenant' => $r->tenant->full_name,
                'Property' => $r->property->name,
                'Unit' => $r->unit->unit_number,
                'Period' => $r->monthName().' '.$r->year,
                'Amount Due' => 'KES '.number_format($r->amount_due, 2),
                'Amount Paid' => 'KES '.number_format($r->amount_paid, 2),
                'Balance' => 'KES '.number_format($r->balance(), 2),
                'Due Date' => $r->due_date->format('d M Y'),
                'Payment Date' => $r->payment_date?->format('d M Y') ?? '—',
                'Transaction' => $r->transaction_code ?? '—',
                'Status' => ucfirst($r->status),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Tenant', 'Property', 'Unit', 'Period',
            'Amount Due', 'Amount Paid', 'Balance',
            'Due Date', 'Payment Date', 'Transaction', 'Status',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
