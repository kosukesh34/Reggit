<?php

namespace Tests\Unit\Support;

use App\Support\MemberCardDesignMap;
use PHPUnit\Framework\TestCase;

class MemberCardDesignMapTest extends TestCase
{
    public function test_each_design_maps_to_a_template(): void
    {
        foreach (MemberCardDesignMap::DESIGNS as $design) {
            $this->assertNotNull(MemberCardDesignMap::templateForDesign($design));
            $this->assertTrue(MemberCardDesignMap::isValidDesign($design));
        }
    }

    public function test_new_templates_have_dedicated_design_keys(): void
    {
        $this->assertSame('simple', MemberCardDesignMap::templateForDesign('compact'));
        $this->assertSame('ticket', MemberCardDesignMap::templateForDesign('ticket'));
        $this->assertSame('wallet', MemberCardDesignMap::templateForDesign('wallet'));
        $this->assertSame('ticket', MemberCardDesignMap::designForTemplate('ticket'));
        $this->assertSame('wallet', MemberCardDesignMap::designForTemplate('wallet'));
    }
}
