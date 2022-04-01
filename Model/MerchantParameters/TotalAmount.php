<?php
namespace OAG\Redsys\Model\MerchantParameters;

/**
 * Class TotalAmount
 * @package OAG\Redsys\Model\MerchantParameters
 */
class TotalAmount
{
    /**
     * Convert amount to Redsys format
     *
     * @param float $amount
     * @return float
     */
    public function execute($quote): float
    {
        return floatval($quote->getGrandTotal()) * 100;
    }
}
