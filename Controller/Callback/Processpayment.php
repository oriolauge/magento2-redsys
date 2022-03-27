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
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;

class Processpayment extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{
    /**
     * @var OAG\Redsys\Model\Base64Url
     */
    private $base64Url;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Signature
     * @todo: convert to interface
     */
    private $signature;

    /**
     * @var OAG\Redsys\Model\QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CartManagementInterface
     */
    private $quoteManagement;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        Base64Url $base64Url,
        JsonFactory $resultJsonFactory,
        Signature $signature,
        QuoteRepository $quoteRepository,
        CartRepositoryInterface $cartRepository,
        CartManagementInterface $quoteManagement
    )
    {
        parent::__construct($context);
        $this->base64Url = $base64Url;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->signature = $signature;
        $this->quoteRepository = $quoteRepository;
        $this->cartRepository = $cartRepository;
        $this->quoteManagement = $quoteManagement;
    }


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

        $quote = $this->getQuote($merchantParameters);
        if (!$quote || !$quote->getId()) {
            return $this->returnJsonError('Quote not found');
        }

        $order = $this->placeQuote($quote);

        //@todo: create order and invoce.

        $resultPage = $this->resultJsonFactory->create();
        $resultPage->setData([
            'success' => true,
            'message' => __('Order completed: ' . $order->getId())
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
     * reserve_order_id
     *
     * @param array $merchantParameters
     * @return Quote
     */
    protected function getQuote(array $merchantParameters)
    {
        $quote = false;
        if (empty($merchantParameters['Ds_MerchantData'])) {
            return $quote;
        }

        $merchantData = json_decode(
            htmlspecialchars_decode($merchantParameters['Ds_MerchantData']),
            true
        );

        if ($merchantData && !empty($merchantData['quote_id']) && is_numeric($merchantData['quote_id'])) {
            $quote = $this->cartRepository->getActive($merchantData['quote_id']);
        } else {
            $quote = $this->quoteRepository->loadQuoteByReservedOrderId($merchantParameters['Ds_Order']);
        }
        return $quote;
    }

    protected function placeQuote($quote)
    {
        $quote->collectTotals();
        return $this->quoteManagement->submit($quote);
    }
}
