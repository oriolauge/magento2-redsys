<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OAG\Redsys\Model;
use OAG\Redsys\Gateway\Config\Redsys;
use OAG\Redsys\Model\Signature;
use OAG\Redsys\Model\MerchantParameters;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Session;
use Magento\Checkout\Helper\Data;
use Magento\Customer\Model\Group;
use Magento\Quote\Model\Quote;

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
     * @var Session
     */
    private $customerSession;

    /**
     * @var Data
     */
    private $checkoutHelper;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @codeCoverageIgnore
     */
    public function __construct(
        Signature $signature,
        MerchantParameters $merchantParameters,
        CartRepositoryInterface $cartRepository,
        Session $customerSession,
        Data $checkoutHelper
    ) {
        $this->cartRepository = $cartRepository;
        $this->signature = $signature;
        $this->merchantParameters = $merchantParameters;
        $this->customerSession = $customerSession;
        $this->checkoutHelper = $checkoutHelper;
    }


    /**
     * @inheritdoc
     */
    public function getPaymentInformation($cartId)
    {
        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->cartRepository->getActive($cartId);

            if ($this->getCheckoutMethod($quote) === Onepage::METHOD_GUEST) {
                $this->prepareGuestQuote($quote);
            }

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
                'Ds_Signature' => $this->signature->generateRequestSignature($incrementId, $merchantParameters)
            ]);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            return json_encode([$e->getMessage()]);
        }
    }

    /**
     * Get quote checkout method
     *
     * @return string
     */
    protected function getCheckoutMethod($quote)
    {
        if ($this->customerSession->isLoggedIn()) {
            return Onepage::METHOD_CUSTOMER;
        }
        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(Onepage::METHOD_REGISTER);
            }
        }
        return $quote->getCheckoutMethod();
    }

    /**
     * Prepare quote for guest checkout order submit
     *
     * @param Quote $quote
     * @return void
     */
    private function prepareGuestQuote(Quote $quote)
    {
        $quote->setCustomerId(null)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
    }
}
