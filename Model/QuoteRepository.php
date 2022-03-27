<?php
namespace OAG\Redsys\Model;

use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;

class QuoteRepository 
{
    const RESERVED_ORDER_FIELD = 'reserved_order_id';

    /**
     * @var Magento\Quote\Model\QuoteFactory;
     */
    protected $quoteFactory;

    /**
     * @var Magento\Quote\Model\ResourceModel\Quote
     */
    protected $quoteResource;

    public function __construct(
        QuoteFactory $quoteFactory, 
        QuoteResource $quoteResource
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->quoteResource = $quoteResource;
    }
 
    /** @return \Magento\Quote\Model\Quote **/
    public function loadQuoteByReservedOrderId(string $incrementId) 
    {
       $quote = $this->quoteFactory->create();
       $this->quoteResource->load($quote, $incrementId, self::RESERVED_ORDER_FIELD);
       return $quote;
    }
}