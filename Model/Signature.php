<?php
namespace OAG\Redsys\Model;
use OAG\Redsys\Model\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use OAG\Redsys\Model\Base64Url;

class Signature
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OAG\Redsys\Model\Base64Url
     */
    protected $base64Url;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Base64Url $base64Url
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->base64Url = $base64Url;
    }

    /**
     * Generate signature to send to Redsys platform
     *
     * @param string $incrementId
     * @param string $merchantParameters
     * @return string
     */
    public function generateRequestSignature(string $incrementId, string $merchantParameters): string
    {
        return base64_encode($this->generateSignature($incrementId, $merchantParameters));
    }

    /**
     * Generate signature that recieve from Redsys platform
     *
     * @param string $incrementId
     * @param string $merchantParameters
     * @return string
     */
    public function generateResponseSignature(string $incrementId, string $merchantParameters): string
    {
        return $this->base64Url->encode((string) $this->generateSignature($incrementId, $merchantParameters));
    }

    /**
     * Generate Redsys signature
     *
     * @param string $incrementId
     * @param string $merchantParameters
     * @return string|false
     */
    protected function generateSignature(string $incrementId, string $merchantParameters)
    {
        $keySha256 = $this->scopeConfig->getValue(ConfigInterface::XML_PATH_SECRET_KEY, ScopeInterface::SCOPE_STORE);
        $key = base64_decode($keySha256);
        $key = $this->encrypt3DES($incrementId, $key);
        return hash_hmac('sha256', $merchantParameters, $key, true);
    }

    /**
     * Generate 3DES encryption
     *
     * @param string $message
     * @param string $key
     * @return string
     */
    protected function encrypt3DES(string $message, string $key): string
    {
        $bytes = [0,0,0,0,0,0,0,0];
        $iv = implode(array_map("chr", $bytes));
        $long = ceil(strlen($message) / 8) * 8;
        $message = $message . str_repeat("\0", $long - strlen($message));
        return substr(openssl_encrypt(
            $message,
            'des-ede3-cbc',
            $key,
            OPENSSL_RAW_DATA,
            "\0\0\0\0\0\0\0\0"
        ), 0, $long);
    }
}