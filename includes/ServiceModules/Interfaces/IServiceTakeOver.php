<?php
namespace App\ServiceModules\Interfaces;

/**
 * Support for service take over by a user
 */
interface IServiceTakeOver
{
    /**
     * Generates a service takeover form
     *
     * @return string
     */
    public function serviceTakeOverFormGet(): string;

    /**
     * Checks the correctness of the data entered in the service takeover form
     * and if everything is ok, it takes over
     *
     * @param array $body
     *
     * @return array
     * status => message id
     * text => message body
     * positive => whether the service took over
     */
    public function serviceTakeOver(array $body): array;
}
