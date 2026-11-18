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
     * @return string JSON with Ds_SignatureVersion, Ds_MerchantParameters and Ds_Signature
     */
    public function getPaymentInformation($cartId);
}
