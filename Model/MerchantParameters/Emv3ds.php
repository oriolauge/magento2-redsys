<?php
namespace OAG\Redsys\Model\MerchantParameters;
use Magento\Quote\Api\Data\CartInterface;
use OAG\Redsys\Model\MerchantParameters\CountryIsoNumeric;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Class Emv3ds
 * @package OAG\Redsys\Model\MerchantParameters
 */
class Emv3ds
{
    /**
     * Holds max length values copied from Redsys documentation
     */
    const CARD_HOLDER_NAME_MAX_LENGTH = 45;
    const CARD_HOLDER_NAME_MIN_LENGTH = 2;
    const EMAIL_MAX_LENGTH = 254;
    const ADDRESS_MAX_LENGTH = 50;
    const POSTCODE_MAX_LENGTH = 16;

    /**
     * Holds Redsys address prefix fields
     */
    const REDSYS_BILLING_ADDRESS_PREFIX = 'bill';
    const REDSYS_SHIPPING_ADDRESS_PREFIX = 'ship';

    /**
     * @var CountryIsoNumeric
     */
    protected $countryIsoNumeric;

    /**
     * contructor class
     *
     * @param CountryIsoNumeric $countryIsoNumeric
     */
    public function __construct(
        CountryIsoNumeric $countryIsoNumeric
    ) {
        $this->countryIsoNumeric = $countryIsoNumeric;
    }

    /**
     * Create EMV3DS array
     *
     * @param CartInterface $quote
     * @return array
     */
    public function execute(CartInterface $quote): array
    {
        $emv3dsData = [];
        $cardholderName = $quote->getCustomerFirstname() . ' ' . $quote->getCustomerLastname();
        if (strlen($cardholderName) >= self::CARD_HOLDER_NAME_MIN_LENGTH
            && strlen($cardholderName) <= self::CARD_HOLDER_NAME_MAX_LENGTH) {
            $emv3dsData['cardholderName'] = $cardholderName;
        }

        $email = $quote->getCustomerEmail();
        if (!empty($email) && strlen($email) <= self::EMAIL_MAX_LENGTH) {
            $emv3dsData['Email'] = $email;
        }

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        if (!$shippingAddress) {
            $shippingAddress = $billingAddress;
        }

        // Shipping
        $shippingAddressEm3dsData = $this->getAddressemv3dsData(
            $shippingAddress,
            self::REDSYS_SHIPPING_ADDRESS_PREFIX
        );
        if ($shippingAddressEm3dsData) {
            $emv3dsData = array_merge($emv3dsData, $shippingAddressEm3dsData);
        }

        // Billing
        $billingAddressEm3dsData = $this->getAddressemv3dsData(
            $billingAddress,
            self::REDSYS_BILLING_ADDRESS_PREFIX
        );
        if ($billingAddressEm3dsData) {
            $emv3dsData = array_merge($emv3dsData, $billingAddressEm3dsData);
        }

        return $emv3dsData;
    }

    /**
     * Return address params for EMV3DS
     *
     * @param AddressInterface $address
     * @param string $type (bill|ship)
     * @return array
     */
    protected function getAddressemv3dsData(AddressInterface $address, string $type): array
    {
        $addressEm3dsData = [];
        if (!in_array($type, [
            self::REDSYS_BILLING_ADDRESS_PREFIX,
            self::REDSYS_SHIPPING_ADDRESS_PREFIX]
            )) {
            return $addressEm3dsData;
        }

        /**
         * We can't controll if every street part has more that 50 characters,
         * so we make this process to join all parts and divide to all Redsys
         * values.
         */
        $street = $address->getStreet();
        $fullStreet = implode(' ', $street);
        if (strlen($fullStreet) <= (self::ADDRESS_MAX_LENGTH * 3)) {
            $streetParts = mb_str_split($fullStreet, self::ADDRESS_MAX_LENGTH);
            foreach ($streetParts as $key => $streetPart) {
                $addressEm3dsData[$type . 'AddrLine' . ++$key] = $streetPart;
            }
        }

        $city = $address->getCity();
        if (strlen($city) <= self::ADDRESS_MAX_LENGTH) {
            $addressEm3dsData[$type . 'AddrCity'] = $city;
        }

        $postcode = $address->getPostcode();
        if (strlen($postcode) <= self::POSTCODE_MAX_LENGTH) {
            $addressEm3dsData[$type . 'AddrPostCode'] = $postcode;
        }

        $countryIsoNumeric = $this->countryIsoNumeric->convertIsoAlpha2ToNumeric($address->getCountryId());
        if ($countryIsoNumeric) {
            $addressEm3dsData[$type . 'AddrCountry'] = $countryIsoNumeric;
        }

        return $addressEm3dsData;
    }
}
