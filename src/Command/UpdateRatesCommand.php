<?php declare(strict_types=1);

namespace App\Command;

use App\Service\Rates;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'app:rates-update')]
class UpdateRatesCommand extends Command
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

            $this->rates->updateCryptoRates($baseCurrency);
            $this->rates->updateFiatRates($baseCurrency);

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