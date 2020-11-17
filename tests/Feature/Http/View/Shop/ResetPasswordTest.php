<?php
namespace Tests\Feature\Http\View\Shop;

use App\Repositories\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class ResetPasswordTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        /** @var UserRepository $userRepository */
        $userRepository = $this->app->make(UserRepository::class);
        $user = $this->factory->user();
        $resetKey = $userRepository->createResetPasswordKey($user->getId());

        // when
        $response = $this->get("/page/reset_password", ["code" => $resetKey]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains("Zresetuj hasło", $response->getContent());
    }

    /** @test */
    public function it_fails_with_invalid_code()
    {
        // when
        $response = $this->get("/page/reset_password", ["code" => "abc123"]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains("Kod resetowania hasła jest błędny", $response->getContent());
    }
}
