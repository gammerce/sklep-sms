<?php

namespace Tests\Feature\ServiceModules\ExtraFlags;

use App\ServiceModules\ExtraFlags\ExtraFlagType;
use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\TestCase;

class ExtraFlagsServiceModuleTest extends TestCase
{
    use MakePurchaseConcern;

    /** @test */
    public function purchase_using_steam_id_64()
    {
        // when
        $boughtService = $this->createRandomExtraFlagsPurchase([
            "auth_data" => "76561198004234833",
            "type" => ExtraFlagType::TYPE_SID,
        ]);

        // then
        $this->assertSame("STEAM_0:1:21984552", $boughtService->getAuthData());
    }
}
