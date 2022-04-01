<?php
namespace OAG\Redsys\Model;
use OAG\Redsys\Model\MerchantParameters\Currency;
use OAG\Redsys\Model\MerchantParameters\Language;
use OAG\Redsys\Model\MerchantParameters\ProductDescription;
use OAG\Redsys\Model\MerchantParameters\TotalAmount;
use OAG\Redsys\Model\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\UrlInterface;

class MerchantParameters
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Currency
     * @todo: conver to interface
     */
    protected $currency;

    /**
     * @var Language
     * @todo: conver to interface
     */
    protected $language;

    /**
     * @var ProductDescription
     */
    protected $productDescription;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var TotalAmount
     * @todo: conver to interface
     */
    protected $totalAmount;

    /**
     * Construct function
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Currency $currency
     * @param Language $language
     * @param ProductDescription $productDescription
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Currency $currency,
        Language $language,
        ProductDescription $productDescription,
        UrlInterface $url,
        TotalAmount $totalAmount
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->currency = $currency;
        $this->language = $language;
        $this->productDescription = $productDescription;
        $this->url = $url;
        $this->totalAmount = $totalAmount;
    }

    /**
     * Generate signature to send to Redsys platform
     *
     * @param Quote $quote
     * @return string
     */
    public function execute(Quote $quote): string
    {
        $merchantCode = $this->scopeConfig->getValue(ConfigInterface::XML_PATH_MERCHANTCODE, ScopeInterface::SCOPE_STORE);
        $transaction = $this->scopeConfig->getValue(ConfigInterface::XML_PATH_TRANSACTION_TYPE, ScopeInterface::SCOPE_STORE);
        $terminal = $this->scopeConfig->getValue(ConfigInterface::XML_PATH_TERMINAL, ScopeInterface::SCOPE_STORE);
        $codeLanguage = $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE);

        /**
         * @todo: missign optionals params:
         * Ds_Merchant_Titular
         * Ds_Merchant_MerchantName
         * DS_MERCHANT_EMV3DS
         */
        $result = [
            'Ds_Merchant_Amount' => $this->totalAmount->execute($quote),
            'Ds_Merchant_Order' => $quote->getReservedOrderId(),
            'Ds_Merchant_MerchantCode' => $merchantCode,
            'Ds_Merchant_Currency' => $this->currency->getCurrency($quote->getQuoteCurrencyCode()),
            'Ds_Merchant_TransactionType' => $transaction,
            'Ds_Merchant_Terminal' => $terminal,
            'Ds_Merchant_MerchantURL' => $this->url->getUrl(ConfigInterface::CALLBACK_PROCESS_PAYMENT_URL),
            'Ds_Merchant_UrlOK' => $this->url->getUrl(ConfigInterface::CALLBACK_SUCCESS_URL),
            'Ds_Merchant_UrlKO' => $this->url->getUrl(ConfigInterface::CALLBACK_ERROR_URL),
            'Ds_Merchant_ConsumerLanguage' => $this->language->getLanguageByCode($codeLanguage),
            'Ds_Merchant_ProductDescription' => $this->productDescription->execute($quote),
            'Ds_Merchant_PayMethods' => 'C',
            'Ds_Merchant_MerchantData' => json_encode(['quote_id' => $quote->getId()])
        ];
        return base64_encode(json_encode($result));
    }
}
