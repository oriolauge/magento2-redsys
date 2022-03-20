<?php
namespace OAG\Redsys\Model;

/**
 * Payment information management service.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GuestPaymentInformationManagement implements \OAG\Redsys\Api\GuestRedsysPaymentInformationManagementInterface
{
    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var \OAG\Redsys\Api\RedsysPaymentInformationManagementInterface
     */
    protected $redsysPaymentInformationManagement;

    /**
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \OAG\Redsys\Api\RedsysPaymentInformationManagementInterface $redsysPaymentInformationManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->redsysPaymentInformationManagement = $redsysPaymentInformationManagement;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentInformation($cartId)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->redsysPaymentInformationManagement->getPaymentInformation($quoteIdMask->getQuoteId());
    }
}
