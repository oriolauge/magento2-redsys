<?php

namespace OAG\Redsys\Model\Ui;
use Magento\Checkout\Model\ConfigProviderInterface;
use OAG\Redsys\Gateway\Config\Redsys as Config;

/**
 * Class RedsysConfigProvider
 * @package OAG\Redsys\Model\Ui
 */
final class RedsysConfigProvider implements ConfigProviderInterface
{
    const CODE = 'oag_redsys';

    /**
     * @var Config
     */
    private $config;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     * @param SessionManagerInterface $session
     * @param RequestInterface $request
     * @param AssetRepository $assetRepository
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive(),
                    'postUrl' => $this->config->getEnvironmentUrl()
                ]
            ]
        ];
    }
}
