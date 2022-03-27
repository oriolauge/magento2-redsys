<?php
namespace OAG\Redsys\Model;

/**
 * This class is copied from Redsys examples because Redsys
 * Seems makes this changes in his server to send us some
 * values
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
     * @param string $string
     * @return float
     */
    public function decode(string $string): string
    {
        return base64_decode(strtr($string, '-_', '+/'));
    }
}