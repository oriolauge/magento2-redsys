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
        return number_format($quote->getGrandTotal(), 2, '', '');
    }
}
