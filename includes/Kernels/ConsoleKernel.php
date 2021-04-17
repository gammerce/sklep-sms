<?php
namespace App\Kernels;

use App\Install\MigrateCommand;
use App\System\Application;
use Psy\Shell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\Psr4\DatabaseSetup;

class ConsoleKernel implements ConsoleKernelContract
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(InputInterface $input, OutputInterface $output): int
    {
        $command = $input->getFirstArgument();

        if ($command === "test:setup") {
            /** @var DatabaseSetup $databaseSetup */
            $databaseSetup = $this->app->make(DatabaseSetup::class);
            $databaseSetup->runForTests();
            $output->writeln("Test environment set up.");
            return 0;
        }

        if ($command === "shop:setup") {
            /** @var DatabaseSetup $databaseSetup */
            $databaseSetup = $this->app->make(DatabaseSetup::class);
            $databaseSetup->run();
            $output->writeln("Environment set up.");
            return 0;
        }

        if ($command === "tinker") {
            date_default_timezone_set("Europe/Warsaw");
            $shell = new Shell();
            $shell->run();
            return 0;
        }

        if ($command === "migrate") {
            /** @var MigrateCommand $migration */
            $migration = $this->app->make(MigrateCommand::class);
            $migration->run(
                $input->getArgument("db_host"),
                $input->getArgument("db_port"),
                $input->getArgument("db_user"),
                $input->getArgument("db_password"),
                $input->getArgument("db_name"),
                $input->getArgument("license_token"),
                $input->getArgument("admin_username"),
                $input->getArgument("admin_email"),
                $input->getArgument("admin_password")
            );
        }

        $output->writeln("Invalid command.");
        return 1;
    }

    public function terminate(InputInterface $input, $status): void
    {
        $this->app->terminate();
    }
}
