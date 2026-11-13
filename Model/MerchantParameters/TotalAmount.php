<?php
namespace OAG\Redsys\Model\MerchantParameters;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class TotalAmount
 * @package OAG\Redsys\Model\MerchantParameters
 */
class TotalAmount
{
    /**
     * @var Currency
     */
    protected $currency;

    /**
     * Construct function
     *
     * @param Currency $currency
     */
    public function __construct(
        Currency $currency
    ) {
        $this->currency = $currency;
    }

    /**
     * Convert amount to Redsys format
     *
     * @param CartInterface $quote
     * @return string
     */
    public function execute(CartInterface $quote): string
    {
        return $this->format((float) $quote->getGrandTotal(), (string) $quote->getQuoteCurrencyCode());
    }

    /**
     * Convert a plain amount to Redsys format (minor units, no separators)
     *
     * The decimal positions of each currency are declared in Currency.
     *
     * @param float $amount
     * @param string $currencyCode Magento currency code
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException when Redsys cannot charge in that currency
     */
    public function format(float $amount, string $currencyCode): string
    {
        return number_format($amount, $this->currency->getDecimals($currencyCode), '', '');
    }
}
