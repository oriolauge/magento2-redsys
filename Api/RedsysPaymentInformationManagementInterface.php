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
     * @return string JSON with Ds_SignatureVersion, Ds_MerchantParameters and Ds_Signature
     */
    public function getPaymentInformation($cartId);
}
