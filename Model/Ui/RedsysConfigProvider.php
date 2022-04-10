<?php

namespace OAG\Redsys\Model\Ui;
use Magento\Checkout\Model\ConfigProviderInterface;
use OAG\Redsys\Gateway\Config\Redsys as Config;
use Magento\Framework\View\Asset\Repository as AssetRepository;

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
     * @var AssetRepository
     */
    private $assetRepository;

    /**
     * @inheritDoc
     *
     * @param Config $config
     */
    public function __construct(
        Config $config,
        AssetRepository $assetRepository
    ) {
        $this->config = $config;
        $this->assetRepository = $assetRepository;
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
                    'postUrl' => $this->config->getEnvironmentUrl(),
                    'icon' => $this->assetRepository->createAsset('OAG_Redsys::images/icon_cards.png')->getUrl()
                ]
            ]
        ];
    }
}
