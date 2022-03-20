<?php
namespace OAG\Redsys\Api;

/**
 * Interface for managing guest payment information
 * @api
 * @since 100.0.2
 */
interface GuestRedsysPaymentInformationManagementInterface
{
    /**
     * Get redsys payment information
     *
     * @param string $cartId
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function getPaymentInformation($cartId);
}
