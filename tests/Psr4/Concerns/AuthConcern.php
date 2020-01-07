<?php
namespace Tests\Psr4\Concerns;

use App\Models\User;
use App\System\Auth;

trait AuthConcern
{
    protected function actingAs(User $user)
    {
        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $auth->setUser($user);
    }
}
