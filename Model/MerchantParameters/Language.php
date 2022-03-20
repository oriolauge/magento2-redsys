<?php
namespace OAG\Redsys\Model\MerchantParameters;

/**
 * Class Language
 * @package OAG\Redsys\Model\MerchantParameters
 */
class Language
{
    /**
     * Default Redsys language (Spanish)
     */
    const DEFAULT_LANGUAGE = '000';

    /**
     * Get Language Code.
     *
     * From Redsys manual:
     * 
     * 001- Español
     * 002- Inglés
     * 003- Catalán
     * 004- Francés
     * 005- Alemán
     * 006- Holandés
     * 007- Italiano
     * 008- Sueco
     * 009- Portugués
     * 010- Valenciano
     * 011- Polaco
     * 012- Gallego
     * 013- Euskera
     *
     * By default: Español
     * 
     * @param  string $language Magento Language Code
     * @return string
     */
    public function getLanguageByCode(string $language): string
    {
        $result = self::DEFAULT_LANGUAGE;
        switch($language) {
            case "ca_ES":
                $result = '003';
                break;
            case "eu_ES":
                $result = '013';
                break;
            case "gl_ES":
                $result = '012';
                break;
            case "en_AU":
            case "en_CA":
            case "en_US":
            case 'en_IE':
            case 'en_NZ':
            case 'en_GB':
                $result = '002';
                break;
            case "fr_FR":
            case 'fr_CA':
                $result = '004';
                break;
            case "de_CH":
            case 'de_AT':
            case "de_DE":
                $result = '005';
                break;
            case 'pt_BR':
            case 'pt_PT':
                $result = '009';
                break;
            case 'it_IT':
            case 'it_CH':
                $result = '007';
                break;
        }
        return $result;
    }
}
