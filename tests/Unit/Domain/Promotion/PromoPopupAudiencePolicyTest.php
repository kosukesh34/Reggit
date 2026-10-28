<?php

namespace Tests\Unit\Domain\Promotion;

use App\Domain\Promotion\Policies\PromoPopupAudiencePolicy;
use App\Domain\Promotion\ValueObjects\PopupViewer;
use App\Domain\Promotion\ValueObjects\PromoPopupContent;
use PHPUnit\Framework\TestCase;

class PromoPopupAudiencePolicyTest extends TestCase
{
    /** @param list<int>|null $storeIds */
    private function popup(string $audience, array $tagIds = [], array $ranks = [], ?array $storeIds = null): PromoPopupContent
    {
        return new PromoPopupContent(1, '告知', null, null, 'modal', null, null, null, null, 'once', 1, $audience, $tagIds, $ranks, $storeIds);
    }

    public function test_会員かどうかとタグ_ランクで出し分けること(): void
    {
        $policy = new PromoPopupAudiencePolicy;
        $guest = PopupViewer::guest(null);
        $gold = new PopupViewer(true, 'gold', [3], null);

        $this->assertTrue($policy->isShownTo($this->popup('all'), $guest));
        $this->assertTrue($policy->isShownTo($this->popup('guests'), $guest));
        $this->assertFalse($policy->isShownTo($this->popup('guests'), $gold));
        $this->assertFalse($policy->isShownTo($this->popup('members'), $guest));
        $this->assertTrue($policy->isShownTo($this->popup('segment', [3]), $gold));
        $this->assertTrue($policy->isShownTo($this->popup('segment', [9], ['gold']), $gold));
        $this->assertFalse($policy->isShownTo($this->popup('segment', [9], ['silver']), $gold));
        $this->assertTrue($policy->isShownTo($this->popup('segment'), $gold));
        $this->assertFalse($policy->isShownTo($this->popup('segment'), $guest));
    }

    public function test_店舗を指定したら利用中の店舗が分かる人だけに出すこと(): void
    {
        $policy = new PromoPopupAudiencePolicy;

        $this->assertTrue($policy->isShownTo($this->popup('all', storeIds: [2]), PopupViewer::guest(2)));
        $this->assertFalse($policy->isShownTo($this->popup('all', storeIds: [2]), PopupViewer::guest(1)));
        $this->assertFalse($policy->isShownTo($this->popup('all', storeIds: [2]), PopupViewer::guest(null)));
        $this->assertTrue($policy->isShownTo($this->popup('all'), PopupViewer::guest(null)));
    }
}
