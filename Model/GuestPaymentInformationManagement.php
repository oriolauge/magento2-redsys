<?php
namespace OAG\Redsys\Model;
use Magento\Quote\Model\QuoteIdMaskFactory;
use OAG\Redsys\Api\RedsysPaymentInformationManagementInterface;
use OAG\Redsys\Api\GuestRedsysPaymentInformationManagementInterface;

/**
 * Payment information management service.
 */
class GuestPaymentInformationManagement implements GuestRedsysPaymentInformationManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var RedsysPaymentInformationManagementInterface
     */
    protected $redsysPaymentInformationManagement;

    /**
     * @inheritDoc
     *
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param RedsysPaymentInformationManagementInterface $redsysPaymentInformationManagement
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        RedsysPaymentInformationManagementInterface $redsysPaymentInformationManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->redsysPaymentInformationManagement = $redsysPaymentInformationManagement;
    }

    /**
     * @inheritDoc
     *
     * @param string $cartId
     * @return void
     */
    public function getPaymentInformation($cartId)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->redsysPaymentInformationManagement->getPaymentInformation($quoteIdMask->getQuoteId());
    }
}
