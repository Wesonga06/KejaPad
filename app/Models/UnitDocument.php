<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitDocument extends Model
{
    use HasFactory;

    public const TYPE_INSPECTION_REPORT = 'inspection_report';

    public const TYPE_PHOTO = 'photo';

    public const TYPE_MAINTENANCE_REPORT = 'maintenance_report';

    public const TYPE_INVOICE = 'invoice';

    public const TYPE_RECEIPT = 'receipt';

    public const TYPE_HANDOVER = 'handover';

    public const TYPE_TENANCY = 'tenancy_document';

    public const TYPE_OTHER = 'other';

    public const VISIBILITY_LANDLORD = 'landlord_only';

    public const VISIBILITY_TENANT = 'tenant_visible';

    protected $fillable = [
        'unit_id',
        'property_id',
        'uploaded_by',
        'title',
        'type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'visibility',
        'related_type',
        'related_id',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_INSPECTION_REPORT => 'Inspection report',
            self::TYPE_PHOTO => 'Property photograph',
            self::TYPE_MAINTENANCE_REPORT => 'Maintenance report',
            self::TYPE_INVOICE => 'Repair invoice',
            self::TYPE_RECEIPT => 'Receipt',
            self::TYPE_HANDOVER => 'Handover record',
            self::TYPE_TENANCY => 'Tenancy document',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function visibilities(): array
    {
        return [
            self::VISIBILITY_LANDLORD => 'Landlord and caretaker only',
            self::VISIBILITY_TENANT => 'Shared with tenant',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? ucfirst(str_replace('_', ' ', (string) $this->type));
    }

    public function isTenantVisible(): bool
    {
        return $this->visibility === self::VISIBILITY_TENANT;
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;

        if ($bytes <= 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = (int) floor(log($bytes, 1024));

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function related()
    {
        return $this->morphTo();
    }
}
