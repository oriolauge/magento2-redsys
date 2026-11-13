<?php
namespace OAG\Redsys\Model\MerchantParameters;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Currency
 * @package OAG\Redsys\Model\MerchantParameters
 */
class Currency
{
    /**
     * Currencies that Redsys can charge, by Magento currency code:
     * - code: Redsys currency code (ISO 4217 numeric)
     * - decimals: decimal positions that Redsys reads in the amount
     *   (the yen has none: its amount is sent as a whole number)
     *
     * A currency that is not declared here is rejected.
     */
    protected $currencies = [
        'AUD' => ['code' => '036', 'decimals' => 2],
        'CAD' => ['code' => '124', 'decimals' => 2],
        'USD' => ['code' => '840', 'decimals' => 2],
        'GBP' => ['code' => '826', 'decimals' => 2],
        'CHF' => ['code' => '756', 'decimals' => 2],
        'JPY' => ['code' => '392', 'decimals' => 0],
        'CNY' => ['code' => '156', 'decimals' => 2],
        'EUR' => ['code' => '978', 'decimals' => 2],
    ];

    /**
     * Convert Magento Currency codes to Redsys Codes
     *
     * @param string $currencyCode
     * @return string
     * @throws LocalizedException
     */
    public function getCurrency(string $currencyCode): string
    {
        return $this->getCurrencyData($currencyCode)['code'];
    }

    /**
     * Get the decimal positions of the amount for a Magento Currency code
     *
     * @param string $currencyCode
     * @return int
     * @throws LocalizedException
     */
    public function getDecimals(string $currencyCode): int
    {
        return $this->getCurrencyData($currencyCode)['decimals'];
    }

    /**
     * Get the Redsys data of a Magento Currency code
     *
     * Fail closed: an unknown currency must never be charged as euros.
     *
     * @param string $currencyCode
     * @return array
     * @throws LocalizedException
     */
    protected function getCurrencyData(string $currencyCode): array
    {
        if (!isset($this->currencies[$currencyCode])) {
            throw new LocalizedException(
                __('Redsys cannot charge in currency "%1".', $currencyCode)
            );
        }
        return $this->currencies[$currencyCode];
    }

}