<?php

namespace OAG\Redsys\Gateway\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use OAG\Redsys\Model\ConfigInterface;

/**
 * Class Redsys
 * @package Magestio\Redsys\Gateway\Config
 */
class Redsys extends \Magento\Payment\Gateway\Config\Config
{
    /**
     * Holds configuration keys
     */
    const KEY_ACTIVE = 'active';
    const KEY_ENVIRONMENT = 'environment';

    /**
     * Holds Redsys sandbox URL
     */
    const SANDBOX_URL = 'https://sis-t.redsys.es:25443/sis/realizarPago/utf-8';

    /**
     * Holds Redsys URL
     */
    const PRODUCTION_URL = 'https://sis.redsys.es/sis/realizarPago/utf-8';

    /**
     * Holds environment values
     */
    const ENVIRONMENT_PRODUCTION = 'production';
    const ENVIRONMENT_SANDBOX = 'sandbox';

    /**
     * Holds Signature version
     */
    const SIGNATURE_VERSION = 'HMAC_SHA256_V1';

    /**
     * Gets Payment configuration status.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
    }

    /**
     * Gets Environment Url
     *
     * @param int|null $storeId
     * @return string
     */
    public function getEnvironmentUrl($storeId = null): string
    {
        $environmentValue = $this->getValue(self::KEY_ENVIRONMENT, $storeId);
        if ($environmentValue == self::ENVIRONMENT_PRODUCTION) {
            return self::PRODUCTION_URL;
        } else {
            return self::SANDBOX_URL;
        }
    }

}
