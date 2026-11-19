<?php
namespace OAG\Redsys\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use OAG\Redsys\Model\Ui\RedsysConfigProvider;
use Psr\Log\LoggerInterface;

/**
 * Keep the quote of a payment that Redsys can still notify
 *
 * The order is created when Redsys notifies the payment, from the quote that
 * was sent to Redsys. On login, \Magento\Checkout\Model\Session::loadCustomerQuote()
 * takes the quote of the session and either merges it into the cart of the
 * customer and deletes it, or converts it into a customer quote and collects
 * its totals again. In both cases the notification can no longer create the
 * order that was paid.
 *
 * loadCustomerQuote() dispatches load_customer_quote_before before doing any
 * of that. When the quote of the session was sent to Redsys, this observer
 * detaches it from the session: the core continues as if the shopper had
 * logged in without a cart and the quote stays as it was signed.
 */
class KeepQuoteAwaitingNotification implements ObserverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        LoggerInterface $logger
    ) {
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
    }

    /**
     * Detach from the session a quote that is waiting for the Redsys notification
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $checkoutSession = $observer->getEvent()->getData('checkout_session');
        if (!$checkoutSession instanceof Session || !$checkoutSession->getQuoteId()) {
            return;
        }

        try {
            $quote = $this->cartRepository->getActive($checkoutSession->getQuoteId());
        } catch (NoSuchEntityException $e) {
            return;
        }

        if (!$quote->getReservedOrderId()
            || $quote->getPayment()->getMethod() !== RedsysConfigProvider::CODE) {
            return;
        }

        $checkoutSession->clearQuote();
        $this->logger->info('[Redsys] Quote detached from the session on login: it was sent to Redsys', [
            'quote_id' => $quote->getId(),
            'reserved_order_id' => $quote->getReservedOrderId()
        ]);
    }
}
