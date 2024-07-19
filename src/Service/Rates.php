<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Rate;
use App\Exceptions\FileNotFoundException;
use App\Exceptions\RateNotFoundException;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\RoundingMode;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Rates
{
    public const DEFAULT_BASE_CURRENCY = 'USD';
    private const FILE_PREFIX = 'currency_rates';
    private const CRYPTO_RATES_EXCHANGE = 'coinbase';
    private const CRYPTO_RATE_SCALE = 20;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly FileSystem $fileSystem,
    ) {}

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateCryptoRates(string $baseCurrency): void
    {
        $rates = $this->fetchCryptoRates($baseCurrency);
        $fileName = self::buildCryptoRatesFileName($baseCurrency);

        $this->saveRates($rates, $fileName);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateFiatRates(string $baseCurrency): void
    {
        $rates = $this->fetchFiatRates($baseCurrency);
        $fileName = self::buildFiatRatesFileName($baseCurrency);

        $this->saveRates($rates, $fileName);
    }

    /**
     * @throws FileNotFoundException
     * @throws NumberFormatException
     * @throws DivisionByZeroException
     * @throws MathException
     */
    public function getRates(string $baseCurrency): array
    {
        $cryptoRates = $this->getCryptoRates($baseCurrency);
        $fiatRates = $this->getFiatRates($baseCurrency);

        $cryptoRatesArray = $this->buildCryptoRatesArray($baseCurrency, $cryptoRates);
        $fiatRatesArray = $this->buildFiatRatesArray($baseCurrency, $fiatRates);

        $ratesArray = array_merge($cryptoRatesArray, $fiatRatesArray);
        ksort($ratesArray);

        return $ratesArray;
    }

    /**
     * @throws DivisionByZeroException
     * @throws FileNotFoundException
     * @throws MathException
     * @throws NumberFormatException
     * @throws RateNotFoundException
     */
    public function exchange(string $amount, string $fromCurrency, string $toCurrency): array
    {
        $rates = $this->getRates($toCurrency);

        if (!array_key_exists($fromCurrency, $rates)) {
            throw new RateNotFoundException("Rate for currency $fromCurrency not found!");
        }

        /**
         * @var Rate $rate
         */
        $rate = $rates[$fromCurrency];

        $resultAmount =BigDecimal::of($amount)
            ->multipliedBy($rate->getRate())
        ;

        return [
            'amount' => $resultAmount,
            'currency_from' => [
                'rate' => $rate->getInverseRate(),
                'code' => $fromCurrency,
            ],
            'currency_to' => [
                'rate' => 1,
                'code' => $toCurrency,
            ],
        ];
    }

    /**
     * @throws NumberFormatException
     * @throws DivisionByZeroException
     * @throws MathException
     */
    private function buildCryptoRatesArray(
        string $baseCurrency,
        array  $rates,
    ): array
    {
        $ratesArray = [];

        foreach ($rates as $rate) {
            [$toCurrency, $fromCurrency] = explode('/', $rate['pair'], 2);

            if ($fromCurrency !== $baseCurrency) {
                continue;
            }

            $rate = $rate['quotes'][$baseCurrency]['price'];

            $inverseRate = BigDecimal::one()->dividedBy(
                BigDecimal::of($rate),
                self::CRYPTO_RATE_SCALE,
                RoundingMode::DOWN,
            );

            $ratesArray[$toCurrency] = new Rate(
                $baseCurrency,
                $toCurrency,
                (string) $rate,
                (string) $inverseRate,
            );
        }

        return self::addBaseRateToArray($ratesArray, $baseCurrency);
    }

    private function buildFiatRatesArray(
        string $baseCurrency,
        array $rates,
    ): array
    {
        $ratesArray = [];

        foreach ($rates as $rate) {
            $ratesArray[$rate['code']] = new Rate(
                $baseCurrency,
                $rate['code'],
                (string) $rate['rate'],
                (string) $rate['inverseRate'],
            );
        }

        return self::addBaseRateToArray($ratesArray, $baseCurrency);
    }

    private static function addBaseRateToArray(array $ratesArray, string $baseCurrency): array
    {
        $ratesArray[$baseCurrency] = new Rate(
            $baseCurrency,
            $baseCurrency,
            '1',
            '1',
        );

        return $ratesArray;
    }

    /**
     * @throws FileNotFoundException
     */
    private function getCryptoRates(string $baseCurrency): array
    {
        $fileName = self::buildCryptoRatesFileName($baseCurrency);

        return $this->getRatesFile($fileName);
    }

    /**
     * @throws FileNotFoundException
     */
    private function getFiatRates(string $baseCurrency): array
    {
        $fileName = self::buildFiatRatesFileName($baseCurrency);

        return $this->getRatesFile($fileName);
    }

    /**
     * @throws FileNotFoundException
     */
    private function getRatesFile(string $fileName): array
    {
        if (!$this->fileSystem->exists($fileName)) {
            throw new FileNotFoundException("File $fileName not found!");
        }

        $file = $this->fileSystem->readFile($fileName);

        return json_decode(
            $file,
            true,
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function fetchCryptoRates(string $baseCurrency): ResponseInterface
    {
        return $this->client->request(
            'GET',
            sprintf(
                'https://api.coinpaprika.com/v1/exchanges/%s/markets?quotes=%s',
                self::CRYPTO_RATES_EXCHANGE,
                $baseCurrency,
            ),
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function fetchFiatRates(string $baseCurrency): ResponseInterface
    {
        return $this->client->request(
            'GET',
            sprintf(
                'https://www.floatrates.com/daily/%s.json',
                strtolower($baseCurrency),
            ),
        );
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function saveRates(ResponseInterface $rates, string $fileName): void
    {
        $this->fileSystem->dumpFile(
            $fileName,
            $rates->getContent(),
        );
    }

    private static function buildCryptoRatesFileName(string $baseCurrency): string
    {
        return sprintf(
            '%s_coinpaprika_%s_%s.json',
            self::FILE_PREFIX,
            self::CRYPTO_RATES_EXCHANGE,
            $baseCurrency,
        );
    }

    private static function buildFiatRatesFileName(string $baseCurrency): string
    {
        return sprintf(
            '%s_floatrates_%s.json',
            self::FILE_PREFIX,
            $baseCurrency,
        );
    }
}