<?php

namespace OAG\Redsys\Controller\Callback;

use OAG\Redsys\Model\Base64Url;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Controller\Result\JsonFactory;
use OAG\Redsys\Model\Signature;
use OAG\Redsys\Model\QuoteRepository;
use OAG\Redsys\Model\MerchantParameters\TotalAmount;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\CartInterface;

class Processpayment extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{
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
     * @inheritDoc
     *
     * @param Context $context
     * @param Base64Url $base64Url
     * @param JsonFactory $resultJsonFactory
     * @param Signature $signature
     * @param QuoteRepository $quoteRepository
     * @param CartRepositoryInterface $cartRepository
     * @param CartManagementInterface $quoteManagement
     * @param TotalAmount $totalAmount
     */
    public function __construct(
        Context $context,
        Base64Url $base64Url,
        JsonFactory $resultJsonFactory,
        Signature $signature,
        QuoteRepository $quoteRepository,
        CartRepositoryInterface $cartRepository,
        CartManagementInterface $quoteManagement,
        TotalAmount $totalAmount
    )
    {
        parent::__construct($context);
        $this->base64Url = $base64Url;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->signature = $signature;
        $this->quoteRepository = $quoteRepository;
        $this->cartRepository = $cartRepository;
        $this->quoteManagement = $quoteManagement;
        $this->totalAmount = $totalAmount;
    }

    /**
     * Execute controller action
     *
     * @return jsonResponse
     */
    public function execute()
    {
        $merchantParametersJson = $this->base64Url->decode(
            $this->getRequest()->getParam('Ds_MerchantParameters')
        );
        $merchantParameters = json_decode($merchantParametersJson, true);
        if (count($merchantParameters) == 0) {
            return $this->returnJsonError('Missign MerchantParameters');
        }

        if (empty($merchantParameters['Ds_Order'])) {
            return $this->returnJsonError('Missign Ds_Order');
        }

        $signature = $this->signature->generateResponseSignature(
            $merchantParameters['Ds_Order'],
            $this->getRequest()->getParam('Ds_MerchantParameters')
        );

        if ($signature !== $this->getRequest()->getParam('Ds_Signature')) {
            return $this->returnJsonError('Signature not match');
        }

        if (!empty($merchantParameters['Ds_Response']) && intval($merchantParameters['Ds_Response']) <= 99) {
            $quote = $this->getQuote($merchantParameters);
            if (!$quote || !$quote->getId()) {
                return $this->returnJsonError('Quote not found');
            }

            /**
             * We don't know if quote was loaded by increment id or quote_id, so we
             * will check if current increment id is the same that Redsys
             * send to our system
             */
            if ($quote->getReservedOrderId() != $merchantParameters['Ds_Order']) {
                return $this->returnJsonError('Quote Increment Id is not the same');
            }

            if (empty($merchantParameters['Ds_Amount']) ||
                $this->totalAmount->execute($quote) != $merchantParameters['Ds_Amount']) {
                return $this->returnJsonError('Quote amount is not the same');
            }

            /**
             * This is an extra validation because if the order has some problem,
             * we will be sure that we can contact to the client
             */
            if (!$quote->getCustomerEmail()) {
                return $this->returnJsonError('Quote has not an email');
            }

            try {
                $order = $this->placeQuote($quote);
            } catch(\Exception $e) {
                return $this->returnJsonError($e->getMessage());
            }

            $resultPage = $this->resultJsonFactory->create();
            $resultPage->setData([
                'success' => true,
                'message' => __('Order completed: ' . $order->getId())
            ]);
            return $resultPage;
        }
        return $this->returnJsonError('Transaction not autorized');
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
     * Get quote
     *
     * We send quote_id in request call because reserved_order_id isn't a primary key,
     * so it's more efficient to load quote by his entity_id.
     *
     * if for some reason we don't have the quote_id, we will try to load quote by
     * reserve_order_id (worst case)
     *
     * @param array $merchantParameters
     * @return Quote|boolean
     */
    protected function getQuote(array $merchantParameters)
    {
        $quote = false;
        if (empty($merchantParameters['Ds_MerchantData'])) {
            return $quote;
        }

        $merchantData = json_decode(
            urldecode($merchantParameters['Ds_MerchantData']),
            true
        );

        try {
            if ($merchantData && !empty($merchantData['quote_id']) && is_numeric($merchantData['quote_id'])) {
                $quote = $this->cartRepository->getActive($merchantData['quote_id']);
            } else {
                $quote = $this->quoteRepository->getActiveByReservedOrderId($merchantParameters['Ds_Order']);
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
