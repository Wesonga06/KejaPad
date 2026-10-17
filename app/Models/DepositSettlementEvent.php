<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An append-only entry in a settlement's history. Existing entries are never
 * edited or deleted, so a tenant's earlier response survives later decisions.
 */
class DepositSettlementEvent extends Model
{
    use HasFactory;

    public const EVENT_CREATED = 'created';

    public const EVENT_DEDUCTION_PROPOSED = 'deduction_proposed';

    public const EVENT_SENT_TO_TENANT = 'sent_to_tenant';

    public const EVENT_TENANT_RESPONDED = 'tenant_responded';

    public const EVENT_TENANT_DISPUTED = 'tenant_disputed';

    public const EVENT_ACKNOWLEDGED = 'acknowledged';

    public const EVENT_DECISION_RECORDED = 'decision_recorded';

    public const EVENT_REFUND_PROPOSED = 'refund_proposed';

    public const EVENT_REFUND_APPROVED = 'refund_approved';

    public const EVENT_SETTLED = 'settled';

    protected $fillable = [
        'deposit_settlement_id',
        'user_id',
        'role',
        'event',
        'message',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function settlement()
    {
        return $this->belongsTo(DepositSettlement::class, 'deposit_settlement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return match ($this->event) {
            self::EVENT_CREATED => 'Statement created',
            self::EVENT_DEDUCTION_PROPOSED => 'Deduction proposed',
            self::EVENT_SENT_TO_TENANT => 'Sent to tenant',
            self::EVENT_TENANT_RESPONDED => 'Tenant responded',
            self::EVENT_TENANT_DISPUTED => 'Tenant disputed',
            self::EVENT_ACKNOWLEDGED => 'Acknowledged by tenant',
            self::EVENT_DECISION_RECORDED => 'Landlord decision recorded',
            self::EVENT_REFUND_PROPOSED => 'Refund proposed',
            self::EVENT_REFUND_APPROVED => 'Refund approved',
            self::EVENT_SETTLED => 'Settled',
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }
}
