<?php
namespace OAG\Redsys\Model\Adminhtml\Source;
use \OAG\Redsys\Gateway\Config\Redsys;
use \Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Redsys::ENVIRONMENT_SANDBOX,
                'label' => 'Sandbox',
            ],
            [
                'value' => Redsys::ENVIRONMENT_PRODUCTION,
                'label' => 'Production'
            ]
        ];
    }
}
