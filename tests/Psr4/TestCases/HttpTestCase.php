<?php
namespace Tests\Psr4\TestCases;

use Tests\Psr4\Concerns\AuthConcern;
use Tests\Psr4\Concerns\MakesHttpRequests;

class HttpTestCase extends TestCase
{
    use AuthConcern;
    use MakesHttpRequests;
}
