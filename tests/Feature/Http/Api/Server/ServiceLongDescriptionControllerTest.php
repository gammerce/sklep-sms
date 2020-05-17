<?php
namespace Tests\Feature\Http\Api\Server;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class ServiceLongDescriptionControllerTest extends HttpTestCase
{
    /** @test */
    public function get_long_description()
    {
        // when
        $response = $this->get("/api/server/services/vippro/long_description");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains(
            "LISTA BONUSÓW, KTÓRE ZYSKUJEMY KUPUJĄC VIPA PRO NA WYBRANYM SERWERZE",
            $response->getContent()
        );
    }
}
