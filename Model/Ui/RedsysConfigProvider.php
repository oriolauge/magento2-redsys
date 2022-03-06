<?php

namespace OAG\Redsys\Model\Ui;
use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class RedsysConfigProvider
 * @package Magestio\Redsys\Model\Ui
 */
final class RedsysConfigProvider implements ConfigProviderInterface
{
    const CODE = 'oag_redsys';

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
                    'isActive' => true,
                    'redirectUrl' => 'https://www.google.com'
                ]
            ]
        ];
    }
}
