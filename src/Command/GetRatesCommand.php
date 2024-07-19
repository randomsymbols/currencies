<?php declare(strict_types=1);

namespace App\Command;

use App\Service\Rates;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'app:rates-get')]
class GetRatesCommand extends Command
{
    public function __construct(
        private readonly Rates $rates,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $baseCurrency = $input->getArgument('baseCurrency');

            if (!$baseCurrency) {
                $baseCurrency = Rates::DEFAULT_BASE_CURRENCY;
            }

            $rates = $this->rates->getRates($baseCurrency);
            $ratesJson = json_encode($rates, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);

            $output->writeln($ratesJson);

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
                'baseCurrency',
                InputArgument::OPTIONAL,
            )
        ;
    }
}