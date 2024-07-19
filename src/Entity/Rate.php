<?php declare(strict_types=1);

namespace App\Entity;

class Rate
{
    public function __construct(
        private string $baseCurrency,
        private string $code,
        private float $rate,
        private float $inverseRate,
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

    public function getRate(): float
    {
        return $this->rate;
    }

    public function setRate(float $rate): void
    {
        $this->rate = $rate;
    }

    public function getInverseRate(): float
    {
        return $this->inverseRate;
    }

    public function setInverseRate(float $inverseRate): void
    {
        $this->inverseRate = $inverseRate;
    }
}