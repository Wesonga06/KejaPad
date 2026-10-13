<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\RentStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Concerns\CreatesRentScenario;
use Tests\TestCase;

class RentStatusTest extends TestCase
{
    use CreatesRentScenario;
    use RefreshDatabase;

    private function statusService(): RentStatusService
    {
        return app(RentStatusService::class);
    }

    private function tenantWithDue(string $dueDate, float $amount = 10000): Tenant
    {
        $landlord = $this->landlord();
        $property = $this->property($landlord);
        $unit = $this->unit($property, $amount);
        $tenant = $this->tenant($property, $unit);

        $due = Carbon::parse($dueDate);
        $this->rent($tenant, $property, $unit, (int) $due->month, (int) $due->year, $amount, $dueDate);

        return $tenant;
    }

    public function test_status_is_green_when_no_period_is_overdue(): void
    {
        $tenant = $this->tenantWithDue(now()->addDays(20)->toDateString());

        $summary = $this->statusService()->summary($tenant);

        $this->assertSame('green', $summary['status_key']);
        $this->assertSame(0.0, $summary['arrears']);
    }

    public function test_status_is_red_when_recently_overdue(): void
    {
        $tenant = $this->tenantWithDue(now()->subDays(5)->toDateString());

        $summary = $this->statusService()->summary($tenant);

        $this->assertSame('red', $summary['status_key']);
        $this->assertSame(10000.0, $summary['arrears']);
    }

    public function test_status_escalates_to_orange_after_threshold(): void
    {
        $tenant = $this->tenantWithDue(now()->subDays(20)->toDateString());

        $this->assertSame('orange', $this->statusService()->summary($tenant)['status_key']);
    }

    public function test_status_escalates_to_yellow_after_threshold(): void
    {
        $tenant = $this->tenantWithDue(now()->subDays(40)->toDateString());

        $this->assertSame('yellow', $this->statusService()->summary($tenant)['status_key']);
    }

    public function test_thresholds_are_configurable(): void
    {
        config()->set('rent.thresholds.orange_after_days', 3);
        config()->set('rent.thresholds.yellow_after_days', 100);

        $tenant = $this->tenantWithDue(now()->subDays(5)->toDateString());

        $this->assertSame('orange', $this->statusService()->summary($tenant)['status_key']);
    }

    public function test_grace_period_suppresses_recent_overdue_status(): void
    {
        config()->set('rent.grace_period_days', 10);

        $tenant = $this->tenantWithDue(now()->subDays(5)->toDateString());
        $this->assertSame('green', $this->statusService()->summary($tenant)['status_key']);

        $older = $this->tenantWithDue(now()->subDays(15)->toDateString());
        $this->assertSame('orange', $this->statusService()->summary($older)['status_key']);
    }
}
