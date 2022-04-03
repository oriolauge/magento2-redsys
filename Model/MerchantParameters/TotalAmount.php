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
     * @return float
     */
    public function execute(CartInterface $quote): float
    {
        return floatval($quote->getGrandTotal()) * 100;
    }
}
