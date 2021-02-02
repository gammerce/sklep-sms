<?php
namespace Tests\Psr4\Concerns;

use App\Models\User;
use App\System\Auth;

trait AuthConcern
{
    protected function actingAs(User $user): void
    {
        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $auth->setUser($user);
    }
}
