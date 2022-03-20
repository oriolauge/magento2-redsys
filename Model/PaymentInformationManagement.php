<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OAG\Redsys\Model;
use OAG\Redsys\Gateway\Config\Redsys;
use OAG\Redsys\Model\Signature;
use OAG\Redsys\Model\MerchantParameters;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Payment information management service.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentInformationManagement implements \OAG\Redsys\Api\RedsysPaymentInformationManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var Signature
     * @todo: convert to interface
     */
    private $signature;

    /**
     * @var MerchantParameters
     * @todo: convert to interface
     */
    private $merchantParameters;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @codeCoverageIgnore
     */
    public function __construct(
        Signature $signature,
        MerchantParameters $merchantParameters,
        CartRepositoryInterface $cartRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->signature = $signature;
        $this->merchantParameters = $merchantParameters;
    }


    /**
     * @inheritdoc
     */
    public function getPaymentInformation($cartId)
    {
        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->cartRepository->getActive($cartId);
            $quote->collectTotals();

            if (!$quote->getGrandTotal()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Redsys can\'t process orders with a zero balance due.')
                );
            }

            if (!$quote->getReservedOrderId()) {
                $quote->reserveOrderId();
                $this->cartRepository->save($quote);
            } else {
                /**
                 * Issue: Redsys doesn't support send the same incrementId two or more times.
                 * To fix this issue, we will change the orderId all times that the user click in "place order".
                 */
                $quote->setReservedOrderId(null);
                $quote->reserveOrderId();
                $this->cartRepository->save($quote);
            }
            $incrementId = $quote->getReservedOrderId();
            $merchantParameters = $this->merchantParameters->execute($quote);

            return json_encode([
                'Ds_SignatureVersion' => Redsys::SIGNATURE_VERSION,
                'Ds_MerchantParameters' => $merchantParameters,
                'Ds_Signature' => $this->signature->getSignature($incrementId, $merchantParameters)
            ]);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            return json_encode([$e->getMessage()]);
        }
    }
}
