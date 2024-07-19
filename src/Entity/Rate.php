<?php declare(strict_types=1);

namespace App\Entity;

class Rate
{
    public function __construct(
        private string $baseCurrency,
        private string $code,
        private string $rate,
        private string $inverseRate,
    ) {}

    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    public function setBaseCurrency(string $baseCurrency): void
    {
        $this->baseCurrency = $baseCurrency;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function setRate(string $rate): void
    {
        $this->rate = $rate;
    }

    public function getInverseRate(): string
    {
        return $this->inverseRate;
    }

    public function setInverseRate(string $inverseRate): void
    {
        $this->inverseRate = $inverseRate;
    }
}