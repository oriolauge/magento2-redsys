<?php
namespace OAG\Redsys\Api;

/**
 * Interface for managing quote payment information
 * @api
 * @since 100.0.2
 */
interface RedsysPaymentInformationManagementInterface
{
    /**
     * Get redsys payment information
     *
     * @param int $cartId
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function getPaymentInformation($cartId);
}
