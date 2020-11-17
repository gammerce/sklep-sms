<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class ServicesTest extends HttpTestCase
{
    /** @test */
    public function is_loads()
    {
        // when
        $response = $this->get("/page/services");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains("Usługi", $response->getContent());
    }
}
