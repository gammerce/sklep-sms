<?php
namespace Tests\Psr4\Concerns;

use App\System\Auth;
use App\Models\User;

trait AuthConcern
{
    protected function actingAs(User $user)
    {
        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);

        $auth->setUser($user);
    }
}
