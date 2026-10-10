<?php

namespace Tests\Feature;

use App\Enums\CouponDeliveryType;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CouponBulkIssueAtomicityTest extends TestCase
{
    use RefreshDatabase;

    public static function failurePoints(): array
    {
        return [['second_member'], ['delivery_history']];
    }

    #[DataProvider('failurePoints')]
    public function test_failure_rolls_back_all_issues_and_history(string $failurePoint): void
    {
        $project = Project::factory()->create();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        Member::factory()->count(2)->create(['project_id' => $project->id]);
        $issued = 0;
        if ($failurePoint === 'second_member') {
            MemberCoupon::created(function () use (&$issued): void {
                if (++$issued === 2) {
                    throw new \RuntimeException('Injected bulk issue failure');
                }
            });
        } else {
            CouponDelivery::creating(function (): void {
                throw new \RuntimeException('Injected bulk issue failure');
            });
        }

        try {
            app(AdminCouponBulkIssueUseCase::class)->handle($project->id, $coupon->id, [
                'delivery_type' => CouponDeliveryType::IMMEDIATE_ALL,
                'send_notification' => true,
            ]);
            $this->fail('The injected failure must propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Injected bulk issue failure', $exception->getMessage());
        }

        $this->assertSame(0, MemberCoupon::withoutGlobalScopes()->where('coupon_id', $coupon->id)->count());
        $this->assertSame(0, CouponDelivery::withoutGlobalScopes()->where('coupon_id', $coupon->id)->count());
    }
}
