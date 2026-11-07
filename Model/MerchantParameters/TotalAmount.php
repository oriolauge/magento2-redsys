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
     * Convert amount to Redsys format
     *
     * @param CartInterface $quote
     * @return string
     */
    public function execute(CartInterface $quote): string
    {
        return $this->format((float) $quote->getGrandTotal());
    }

    /**
     * Convert a plain amount to Redsys format (minor units, no separators)
     *
     * @param float $amount
     * @return string
     */
    public function format(float $amount): string
    {
        return number_format($amount, 2, '', '');
    }
}
