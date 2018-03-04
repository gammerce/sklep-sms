<?php
namespace App\Kernels;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface ConsoleKernelContract
{
    /**
     * Handle an incoming console command.
     *
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    public function handle(InputInterface $input, OutputInterface $output = null);

    public function terminate(InputInterface $input, $status);
}
