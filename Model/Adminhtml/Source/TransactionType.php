<?php
namespace OAG\Redsys\Model\Adminhtml\Source;
use \Magento\Framework\Data\OptionSourceInterface;

class TransactionType implements OptionSourceInterface
{
    /**
     * Holds Authorization Redsys value.
     * Check Redsys documentation for more information
     */
    const AUTHORIZATION = 0;

    /**
     * Possible Transaction types
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::AUTHORIZATION,
                'label' => 'Authorization',
            ]
        ];
    }
}
