<?php
namespace Tests\Feature\Http\View\Shop;

use App\Repositories\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class ConfirmEmailTest extends HttpTestCase
{
    /** @test */
    public function shows_the_pending_address()
    {
        // given
        $user = $this->factory->user();
        $this->app
            ->make(UserRepository::class)
            ->setEmailChange($user->getId(), "new@example.com", "testkey");

        // when
        $response = $this->get("/page/confirm_email", ["code" => "testkey"]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("new@example.com", $response->getContent());
        $this->assertStringContainsString("Adres do potwierdzenia", $response->getContent());
    }

    /** @test */
    public function shows_an_error_for_an_unknown_code()
    {
        // when
        $response = $this->get("/page/confirm_email", ["code" => "nope"]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("nieprawidłowy", $response->getContent());
    }
}
