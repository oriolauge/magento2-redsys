<?php

namespace OAG\Redsys\Controller\Callback;

use OAG\Redsys\Model\Base64Url;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Controller\Result\JsonFactory;
use OAG\Redsys\Model\Signature;
use OAG\Redsys\Model\QuoteRepository;
use OAG\Redsys\Model\MerchantParameters\TotalAmount;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use OAG\Redsys\Model\ConfigInterface;
use OAG\Redsys\Model\MerchantParameters\Currency;
use Psr\Log\LoggerInterface;

class Processpayment implements CsrfAwareActionInterface, HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Base64Url
     */
    protected $base64Url;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Signature
     * @todo: convert to interface
     */
    protected $signature;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * @var TotalAmount
     */
    protected $totalAmount;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Currency
     */
    protected $currency;

    /**
     * @inheritDoc
     *
     * @param Base64Url $base64Url
     * @param JsonFactory $resultJsonFactory
     * @param Signature $signature
     * @param QuoteRepository $quoteRepository
     * @param CartRepositoryInterface $cartRepository
     * @param CartManagementInterface $quoteManagement
     * @param TotalAmount $totalAmount
     * @param RequestInterface $request
     * @param RedirectFactory $resultRedirectFactory
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Currency $currency
     */
    public function __construct(
        Base64Url $base64Url,
        JsonFactory $resultJsonFactory,
        Signature $signature,
        QuoteRepository $quoteRepository,
        CartRepositoryInterface $cartRepository,
        CartManagementInterface $quoteManagement,
        TotalAmount $totalAmount,
        RequestInterface $request,
        RedirectFactory $resultRedirectFactory,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        Currency $currency
    )
    {
        $this->base64Url = $base64Url;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->signature = $signature;
        $this->quoteRepository = $quoteRepository;
        $this->cartRepository = $cartRepository;
        $this->quoteManagement = $quoteManagement;
        $this->totalAmount = $totalAmount;
        $this->request = $request;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->currency = $currency;
    }

    /**
     * Execute controller action
     *
     * Every failure branch is logged: when this endpoint rejects a notification
     * the customer may already have been charged, and the log is the only
     * trace the merchant has to reconcile it with the Redsys panel.
     *
     * @return jsonResponse
     */
    public function execute()
    {
        $rawParameters = $this->request->getParam('Ds_MerchantParameters');
        $rawSignature = $this->request->getParam('Ds_Signature');
        if (!is_string($rawParameters) || !is_string($rawSignature)) {
            return $this->fail('Missing MerchantParameters or Signature', [], 'warning');
        }

        $merchantParameters = json_decode($this->base64Url->decode($rawParameters), true);
        if (!is_array($merchantParameters) || count($merchantParameters) === 0) {
            return $this->fail('Missing MerchantParameters or error format', [], 'warning');
        }

        // Redsys is not consistent with the case of the keys (Ds_Order / DS_ORDER)
        $merchantParameters = array_change_key_case($merchantParameters, CASE_LOWER);

        $dsOrder = $merchantParameters['ds_order'] ?? null;
        if (!is_scalar($dsOrder) || (string) $dsOrder === '') {
            return $this->fail('Missing Ds_Order', [], 'warning');
        }
        $dsOrder = (string) $dsOrder;
        $context = ['ds_order' => $dsOrder];

        try {
            $expectedSignature = $this->signature->generateResponseSignature($dsOrder, $rawParameters);
        } catch (\Exception $e) {
            return $this->fail('Signature could not be verified', $context + ['error' => $e->getMessage()], 'critical');
        }

        if (!hash_equals($expectedSignature, $rawSignature)) {
            return $this->fail('Signature not match', $context, 'warning');
        }

        // From here the notification is authentic (signed by Redsys).
        $response = $merchantParameters['ds_response'] ?? '';
        if (!is_scalar($response) || !ctype_digit((string) $response) || (int) $response > 99) {
            $this->logger->info('[Redsys] Transaction not authorized', $context + ['ds_response' => $response]);
            return $this->returnJsonError('Transaction not autorized');
        }

        try {
            $quote = $this->getQuote($merchantParameters);
            if (!$quote || !$quote->getId()) {
                return $this->fail('Quote not found', $context, 'critical');
            }
            $context['quote_id'] = $quote->getId();

            /**
             * We don't know if quote was loaded by increment id or quote_id, so we
             * will check if current increment id is the same that Redsys
             * send to our system
             */
            if ((string) $quote->getReservedOrderId() !== $dsOrder) {
                return $this->fail(
                    'Quote Increment Id is not the same',
                    $context + ['quote_reserved_order_id' => $quote->getReservedOrderId()],
                    'critical'
                );
            }

            $storeId = $quote->getStoreId();
            $merchantCode = trim((string) $this->scopeConfig->getValue(
                ConfigInterface::XML_PATH_MERCHANT_CODE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            ));
            if (!isset($merchantParameters['ds_merchantcode'])
                || (string) $merchantParameters['ds_merchantcode'] !== $merchantCode) {
                return $this->fail('Merchant code is not the same', $context, 'critical');
            }

            if (!isset($merchantParameters['ds_currency'])
                || (string) $merchantParameters['ds_currency']
                    !== $this->currency->getCurrency((string) $quote->getQuoteCurrencyCode())) {
                return $this->fail('Currency is not the same', $context, 'critical');
            }

            /**
             * Totals are recalculated BEFORE comparing the amount. Comparing the
             * persisted grand total and recalculating later (when the order is
             * submitted) could create an order for a different amount than the
             * one Redsys charged.
             */
            $quote->collectTotals();
            $paidAmount = isset($merchantParameters['ds_amount']) ? (string) $merchantParameters['ds_amount'] : '';
            if ($paidAmount === '' || $this->totalAmount->execute($quote) !== $paidAmount) {
                return $this->fail(
                    'Quote amount is not the same',
                    $context + ['ds_amount' => $paidAmount, 'quote_amount' => $this->totalAmount->execute($quote)],
                    'critical'
                );
            }

            /**
             * This is an extra validation because if the order has some problem,
             * we will be sure that we can contact to the client
             */
            if (!$quote->getCustomerEmail()) {
                return $this->fail('Quote has not an email', $context, 'critical');
            }

            $order = $this->placeQuote($quote);
            if (!$order) {
                return $this->fail('No order has been created', $context, 'critical');
            }
        } catch (\Exception $e) {
            return $this->fail('Order could not be placed', $context + ['error' => $e->getMessage()], 'critical');
        }

        // Last safety net: the order that exists now must match what was charged.
        if ($this->totalAmount->format((float) $order->getGrandTotal()) !== $paidAmount) {
            $this->logger->critical(
                '[Redsys] Order grand total differs from the amount charged',
                $context + ['order_id' => $order->getId(), 'ds_amount' => $paidAmount]
            );
        }

        $this->logger->info('[Redsys] Order placed', $context + [
            'order_id' => $order->getId(),
            'ds_response' => $response,
            'ds_authorisationcode' => $merchantParameters['ds_authorisationcode'] ?? null
        ]);

        $resultPage = $this->resultJsonFactory->create();
        $resultPage->setData([
            'success' => true,
            'message' => __('Order completed: %1', $order->getId())
        ]);
        return $resultPage;
    }

	/**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('/');

        return new InvalidRequestException(
            $resultRedirect,
            [new Phrase('Missign required params.')]
        );
    }

    /**
     * Validate if Redsys send us the required params
     *
     * @param RequestInterface $request
     * @return boolean|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        $params = $request->getParams();
        if (!empty($params['Ds_SignatureVersion'])
            && !empty($params['Ds_MerchantParameters'])
            && !empty($params['Ds_Signature'])) {
            return true;
        }
        return false;
    }

    /**
     * Convert message to json response
     *
     * @param string $message
     * @return jsonObjetc
     */
    protected function returnJsonError(string $message)
    {
        $resultPage = $this->resultJsonFactory->create();
        $resultPage->setHttpResponseCode(500);
        return $resultPage->setData([
            'success'   => false,
            'message' => __($message),
        ]);
    }

    /**
     * Log a failure and convert it to a json error response.
     *
     * The log level is 'critical' when the notification was authentic but we
     * could not place the order (the customer has probably been charged).
     *
     * @param string $message
     * @param array $context
     * @param string $level
     * @return jsonObjetc
     */
    protected function fail(string $message, array $context, string $level)
    {
        $this->logger->{$level}('[Redsys] ' . $message, $context);
        return $this->returnJsonError($message);
    }

    /**
     * Get quote
     *
     * We send quote_id in request call because reserved_order_id isn't a primary key,
     * so it's more efficient to load quote by his entity_id.
     *
     * if for some reason we don't have the quote_id, we will try to load quote by
     * reserve_order_id (worst case)
     *
     * @param array $merchantParameters keys in lower case
     * @return Quote|boolean
     */
    protected function getQuote(array $merchantParameters)
    {
        $merchantData = [];
        if (!empty($merchantParameters['ds_merchantdata']) && is_string($merchantParameters['ds_merchantdata'])) {
            $merchantData = json_decode(
                urldecode($merchantParameters['ds_merchantdata']),
                true
            );
        }

        try {
            if ($merchantData && !empty($merchantData['quote_id']) && is_numeric($merchantData['quote_id'])) {
                $quote = $this->cartRepository->getActive($merchantData['quote_id']);
            } else {
                $quote = $this->quoteRepository->getActiveByReservedOrderId((string) $merchantParameters['ds_order']);
            }
            return $quote;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Place order
     *
     * @param CartInterface $quote
     * @return Order
     */
    protected function placeQuote(CartInterface $quote)
    {
        $quote->collectTotals();
        return $this->quoteManagement->submit($quote);
    }
}
