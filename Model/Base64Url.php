<?php
namespace OAG\Redsys\Model;

/**
 * This class is copied from Redsys examples because Redsys
 * makes this changes in his server to send us some values
 */
class Base64Url
{
    /**
     * Encode string to 64 but change special characters to make compatible with urls
     *
     * @param string $string
     * @return string
     */
    public function encode(string $string): string
    {
        return strtr(base64_encode($string), '+/', '-_');
    }

    /**
     * Decode base 64 that the special characters was converted to make compatible with urls
     *
     * base64_decode() returns false for some invalid inputs. The declared return
     * type is string, so on PHP 8 that false becomes a TypeError and the payment
     * notification never reaches the signature check.
     *
     * @param string $string
     * @return string
     */
    public function decode(string $string): string
    {
        $decoded = base64_decode(strtr($string, '-_', '+/'));
        return is_string($decoded) ? $decoded : '';
    }
}