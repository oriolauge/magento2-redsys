<?php

namespace OAG\Redsys\Controller\Callback;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Success extends Action implements HttpGetActionInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @inheritDoc
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        CartRepositoryInterface $cartRepository,
        OrderFactory $orderFactory
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function execute()
    {
        $quoteId = $this->checkoutSession->getQuoteId();
        if (is_numeric($quoteId) && $quoteId > 0) {
            try {
                $quote = $this->cartRepository->get($quoteId);
                $incrementId = $quote->getReservedOrderId();
                $storeId = $quote->getStoreId();
                /**
                 * We load order by increment id and store id because magento has an db index
                 * to find the order quickly
                 */
                $order = $this->orderFactory->create()->loadByIncrementIdAndStoreId($incrementId, $storeId);

                if (!$order->getId()) {
                    throw new \Exception(__('Sorry, we have an error to try to load your order.'));
                }

                $this->checkoutSession->setLastQuoteId($quoteId);
                $this->checkoutSession->setLastSuccessQuoteId($quoteId);
                $this->checkoutSession->setLastOrderId($order->getId());
                $this->checkoutSession->setLastRealOrderId($incrementId);
                $this->checkoutSession->setLastOrderStatus($order->getStatus());
                $this->messageManager->addSuccessMessage(__('Transaction authorized.'));
                $this->_redirect('checkout/onepage/success');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_redirect('checkout/cart');
            }
        } else {
            $this->messageManager->addErrorMessage(__('Sorry, We have an error to try to load the cart.'));
            $this->_redirect('checkout/cart');
        }
    }
}
