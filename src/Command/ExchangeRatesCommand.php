<?php declare(strict_types=1);

namespace App\Command;

use App\Service\Rates;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'app:rates-exchange')]
class ExchangeRatesCommand extends Command
{
    public function __construct(
        private readonly Rates $rates,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $amount = $input->getArgument('amount');
            $fromCurrency = $input->getArgument('fromCurrency');
            $toCurrency = $input->getArgument('toCurrency');

            $result = $this->rates->exchange($amount, $fromCurrency, $toCurrency);
            $resultJson = json_encode($result, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);

            $output->writeln($resultJson);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'amount',
                InputArgument::REQUIRED,
            )
            ->addArgument(
                'fromCurrency',
                InputArgument::REQUIRED,
            )
            ->addArgument(
                'toCurrency',
                InputArgument::REQUIRED,
            )
        ;
    }
}