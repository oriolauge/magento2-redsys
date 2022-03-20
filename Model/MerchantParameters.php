<?php
namespace OAG\Redsys\Model;
use OAG\Redsys\Model\MerchantParameters\Currency;
use OAG\Redsys\Model\MerchantParameters\Language;
use OAG\Redsys\Model\MerchantParameters\ProductDescription;
use OAG\Redsys\Model\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\Quote;

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
        ProductDescription $productDescription
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->currency = $currency;
        $this->language = $language;
        $this->productDescription = $productDescription;
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
         * Ds_Merchant_MerchantData
         * Ds_Merchant_MerchantName
         * DS_MERCHANT_EMV3DS
         */
        $result = [
            'DS_MERCHANT_AMOUNT' => $this->convertAmountToRedsysFormat($quote->getGrandTotal()),
            'DS_MERCHANT_ORDER' => $quote->getReservedOrderId(),
            'DS_MERCHANT_MERCHANTCODE' => $merchantCode,
            'DS_MERCHANT_CURRENCY' => $this->currency->getCurrency($quote->getQuoteCurrencyCode()),
            'DS_MERCHANT_TRANSACTIONTYPE' => $transaction,
            'DS_MERCHANT_TERMINAL' => $terminal,
            'DS_MERCHANT_MERCHANTURL' => 'callback_url',
            'DS_MERCHANT_URLOK' => 'callback_ok',
            'DS_MERCHANT_URLKO' => 'callback_ko',
            'DS_MERCHANT_CONSUMERLANGUAGE' => $this->language->getLanguageByCode($codeLanguage),
            'DS_MERCHANT_PRODUCTDESCRIPTION' => $this->productDescription->execute($quote),
            'DS_MERCHANT_PAYMETHODS' => 'C'
        ];
        return base64_encode(json_encode($result));
    }

    /**
     * Convert amount to Redsys format
     *
     * @param float $amount
     * @return float
     */
    protected function convertAmountToRedsysFormat($amount): float
    {
        return floatval($amount) * 100;
    }
}