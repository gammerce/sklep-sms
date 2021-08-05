<?php
namespace App\Kernels;

use App\Install\MigrateCommand;
use App\System\Application;
use Psy\Shell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            $input->bind(
                new InputDefinition([
                    new InputArgument("action", InputArgument::REQUIRED),
                    new InputOption("db-name", null, InputOption::VALUE_REQUIRED),
                    new InputOption("license-token", null, InputOption::VALUE_REQUIRED),
                    new InputOption("admin-username", null, InputOption::VALUE_REQUIRED),
                    new InputOption("admin-email", null, InputOption::VALUE_REQUIRED),
                    new InputOption("admin-password", null, InputOption::VALUE_REQUIRED),
                ])
            );
            $migration->run(
                $input->getOption("db-name"),
                $input->getOption("license-token"),
                $input->getOption("admin-username"),
                $input->getOption("admin-email"),
                $input->getOption("admin-password")
            );
            $output->writeln("Migration completed");
            return 0;
        }

        $output->writeln("Invalid command.");
        return 1;
    }

    public function terminate(InputInterface $input, $status): void
    {
        $this->app->terminate();
    }
}
