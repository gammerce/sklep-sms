<?php
namespace App\Kernels;

use App\System\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\Psr4\DatabaseSetup;

class ConsoleKernel implements ConsoleKernelContract
{
    /** @var Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(InputInterface $input, OutputInterface $output = null)
    {
        $command = $input->getFirstArgument();

        if ($command === 'test:setup') {
            /** @var DatabaseSetup $databaseSetup */
            $databaseSetup = $this->app->make(DatabaseSetup::class);
            $databaseSetup->runForTests();
            $output->writeln('Test environment set up.');
            return 0;
        }

        if ($command === 'shop:setup') {
            /** @var DatabaseSetup $databaseSetup */
            $databaseSetup = $this->app->make(DatabaseSetup::class);
            $databaseSetup->run();
            $output->writeln('Environment set up.');
            return 0;
        }

        $output->writeln('Invalid command.');
        return 1;
    }

    public function terminate(InputInterface $input, $status)
    {
        $this->app->terminate();
    }
}
