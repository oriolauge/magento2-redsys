<?php
namespace OAG\Redsys\Model;
use OAG\Redsys\Model\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Signature
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Generate signature to send to Redsys platform
     *
     * @param string $incrementId
     * @param string $merchantParameters
     * @return string
     */
    public function getSignature(string $incrementId, string $merchantParameters): string
    {
        $keySha256 = $this->scopeConfig->getValue(ConfigInterface::XML_PATH_SECRET_KEY, ScopeInterface::SCOPE_STORE);
        $key = base64_decode($keySha256);
        $key = $this->encrypt3DES($incrementId, $key);
        $signature = hash_hmac('sha256', $merchantParameters, $key, true);
        return base64_encode($signature);
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