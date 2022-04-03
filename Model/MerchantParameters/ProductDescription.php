<?php
namespace OAG\Redsys\Model\MerchantParameters;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class ProductDescription
 * @package OAG\Redsys\Model\MerchantParameters
 */
class ProductDescription
{
    const MAX_LENGHT = 125;

    /**
     * Create Product Description
     *
     * @param CartInterface $quote
     * @return string
     */
    public function execute(CartInterface $quote): string
    {
        $descriptionQuote = [];
        $items = $quote->getAllVisibleItems();

        foreach ($items as $item) {
            $descriptionQuote[] = trim($item->getName()) . " x " . $item->getQty();
        }

        $result = implode(', ', $descriptionQuote);
        return strlen($result) <= self::MAX_LENGHT ? $result : '';
    }
}
