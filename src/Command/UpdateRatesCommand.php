<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AsCommand(name: 'app:update-rates')]
class UpdateRatesCommand extends Command
{
    private const COINBASE_EXCHANGE_ID = 'coinbase';

    public function __construct(
        private HttpClientInterface $client,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $baseCurrency = $input->getArgument('baseCurrency');

            if (!$baseCurrency) {
                $baseCurrency = 'USD';
            }

            $filesystem = new Filesystem();

            $response = $this->client->request(
                'GET',
                sprintf(
                    'https://api.coinpaprika.com/v1/exchanges/%s/markets?quotes=%s',
                    self::COINBASE_EXCHANGE_ID,
                    $baseCurrency,
                ),
            );

            $filename = sprintf(
                'currency_rates_coinpaprika_%s_%s.json',
                self::COINBASE_EXCHANGE_ID,
                $baseCurrency,
            );

            $filesystem->dumpFile(
                $filename,
                $response->getContent(),
            );

            $response = $this->client->request(
                'GET',
                sprintf(
                    'https://www.floatrates.com/daily/%s.json',
                    strtolower($baseCurrency),
                ),
            );

            $filename = sprintf(
                'currency_rates_floatrates_%s.json',
                $baseCurrency,
            );

            $filesystem->dumpFile(
                $filename,
                $response->getContent(),
            );

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
                'The base currency, default is USD.',
            )
        ;
    }
}