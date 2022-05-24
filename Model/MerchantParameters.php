<?php
namespace OAG\Redsys\Model;
use OAG\Redsys\Model\MerchantParameters\Currency;
use OAG\Redsys\Model\MerchantParameters\Language;
use OAG\Redsys\Model\MerchantParameters\ProductDescription;
use OAG\Redsys\Model\MerchantParameters\TotalAmount;
use OAG\Redsys\Model\MerchantParameters\Emv3ds;
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
     * @var Emv3ds
     */
    protected $emv3ds;

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
        TotalAmount $totalAmount,
        Emv3ds $emv3ds
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->currency = $currency;
        $this->language = $language;
        $this->productDescription = $productDescription;
        $this->url = $url;
        $this->totalAmount = $totalAmount;
        $this->emv3ds = $emv3ds;
    }

    /**
     * Generate signature to send to Redsys platform
     *
     * @param Quote $quote
     * @return string
     */
    public function execute(Quote $quote): string
    {
        $merchantCode = $this->scopeConfig->getValue(ConfigInterface::XML_PATH_MERCHANT_CODE, ScopeInterface::SCOPE_STORE);
        $merchantName = $this->scopeConfig->getValue(ConfigInterface::XML_PATH_MERCHANT_NAME, ScopeInterface::SCOPE_STORE);
        $transaction = $this->scopeConfig->getValue(ConfigInterface::XML_PATH_TRANSACTION_TYPE, ScopeInterface::SCOPE_STORE);
        $terminal = $this->scopeConfig->getValue(ConfigInterface::XML_PATH_TERMINAL, ScopeInterface::SCOPE_STORE);
        $codeLanguage = $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE);
        $sendEmv3ds = $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE);

        /**
         * @todo: missign optionals params:
         * Ds_Merchant_Titular
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

        if (!empty($merchantName) && strlen($merchantName) <= ConfigInterface::MERCHANT_NAME_MAX_LENGTH) {
            $result['Ds_Merchant_MerchantName'] = $merchantName;
        }

        if ($sendEmv3ds) {
            $emv3ds = $this->emv3ds->execute($quote);
            if ($emv3ds) {
                $result['Ds_Merchant_EMV3DS'] = json_encode($emv3ds);
            }
        }

        return base64_encode(json_encode($result));
    }
}
