<?php
namespace OAG\Redsys\Model\MerchantParameters;

/**
 * Class Currency
 * @package OAG\Redsys\Model\MerchantParameters
 */
class Currency
{
    const DEFAULT_CURRENCY = '978';

    protected $currencies = [
        'AUD' => '036',
        'CAD' => '124',
        'USD' => '840',
        'GBP' => '826',
        'CHF' => '756',
        'JPY' => '392',
        'CNY' => '156',
        'EUR' => '978',
    ];

    /**
     * Convert Magento Currency codes to Redsys Codes
     *
     * @param string $currencyCode
     * @return string
     */
    public function getCurrency(string $currencyCode): string
    {
        if (isset($this->currencies[$currencyCode])) {
            return $this->currencies[$currencyCode];
        }
        return self::DEFAULT_CURRENCY;
    }

}