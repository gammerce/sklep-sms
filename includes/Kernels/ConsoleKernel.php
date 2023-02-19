<?php
namespace App\Kernels;

use App\Install\MigrateCommand;
use App\Payment\Invoice\IssueInvoiceService;
use App\System\Application;
use App\System\CronExecutor;
use App\System\Settings;
use Exception;
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

        if ($command === "cron:run") {
            /** @var CronExecutor $cronExecutor */
            $cronExecutor = $this->app->make(CronExecutor::class);

            $this->loadSettings();
            $cronExecutor->run();
            $output->writeln("Cron executed.");
            return 0;
        }

        if ($command === "invoice:issue") {
            /** @var IssueInvoiceService $issueInvoiceService */
            $issueInvoiceService = $this->app->make(IssueInvoiceService::class);

            $input->bind(
                new InputDefinition([
                    new InputArgument("action", InputArgument::REQUIRED),
                    new InputOption("transaction-id", InputOption::VALUE_REQUIRED, "Purchase ID"),
                ])
            );

            $this->loadSettings();
            $invoiceId = $issueInvoiceService->reissue($input->getOption("transaction-id"));
            $output->writeln("Invoice issued $invoiceId");
            return 0;
        }

        if ($command === "tinker") {
            date_default_timezone_set("Europe/Warsaw");
            $this->loadSettings();
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

    private function loadSettings(): void
    {
        $settings = $this->app->make(Settings::class);

        try {
            $settings->load();
        } catch (Exception $e) {
            //
        }
    }
}
