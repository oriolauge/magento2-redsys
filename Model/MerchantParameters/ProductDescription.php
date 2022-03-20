<?php
namespace OAG\Redsys\Model\MerchantParameters;
use Magento\Quote\Model\Quote;

/**
 * Class ProductDescription
 * @package OAG\Redsys\Model\MerchantParameters
 */
class ProductDescription
{
    /**
     * Undocumented function
     *
     * @param CartInterface $quote
     * @return string
     */
    public function execute(Quote $quote): string
    {
        $descriptionQuote = [];
        $items = $quote->getAllVisibleItems();

        foreach ($items as $item) {
            $descriptionQuote[] = trim($item->getName()) . " x " . $item->getQty();
        }

        $result = implode(', ', $descriptionQuote);
        return strlen($result) <= 125 ? $result : '';
    }

}