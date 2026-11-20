<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);


namespace Invertus\dpdBaltics\Util;

if (!defined('_PS_VERSION_')) {
    exit;
}

class StringUtility
{
    const REMARK_LIMIT = 44;

    public static function toLowerCase($string)
    {
        return \Tools::strtolower($string);
    }

    public static function trimString($message) {
        return mb_strlen($message) > self::REMARK_LIMIT ? mb_substr($message, 0, self::REMARK_LIMIT) . "..." : $message;
    }

    public static function removeSpecialCharacters($string) {
        $table = array(
            'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Ă'=>'A', 'Ā'=>'A', 'Ą'=>'A', 'Æ'=>'A', 'Ǽ'=>'A',
            'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'ă'=>'a', 'ā'=>'a', 'ą'=>'a', 'æ'=>'a', 'ǽ'=>'a',

            'Þ'=>'B', 'þ'=>'b', 'ß'=>'Ss',

            'Ç'=>'C', 'Č'=>'C', 'Ć'=>'C', 'Ĉ'=>'C', 'Ċ'=>'C',
            'ç'=>'c', 'č'=>'c', 'ć'=>'c', 'ĉ'=>'c', 'ċ'=>'c',

            'Đ'=>'Dj', 'Ď'=>'D',
            'đ'=>'dj', 'ď'=>'d',

            'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ĕ'=>'E', 'Ē'=>'E', 'Ę'=>'E', 'Ė'=>'E',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ĕ'=>'e', 'ē'=>'e', 'ę'=>'e', 'ė'=>'e',

            'Ĝ'=>'G', 'Ğ'=>'G', 'Ġ'=>'G', 'Ģ'=>'G',
            'ĝ'=>'g', 'ğ'=>'g', 'ġ'=>'g', 'ģ'=>'g',

            'Ĥ'=>'H', 'Ħ'=>'H',
            'ĥ'=>'h', 'ħ'=>'h',

            'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'İ'=>'I', 'Ĩ'=>'I', 'Ī'=>'I', 'Ĭ'=>'I', 'Į'=>'I',
            'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'į'=>'i', 'ĩ'=>'i', 'ī'=>'i', 'ĭ'=>'i', 'ı'=>'i',

            'Ĵ'=>'J',
            'ĵ'=>'j',

            'Ķ'=>'K',
            'ķ'=>'k', 'ĸ'=>'k',

            'Ĺ'=>'L', 'Ļ'=>'L', 'Ľ'=>'L', 'Ŀ'=>'L', 'Ł'=>'L',
            'ĺ'=>'l', 'ļ'=>'l', 'ľ'=>'l', 'ŀ'=>'l', 'ł'=>'l',

            'Ñ'=>'N', 'Ń'=>'N', 'Ň'=>'N', 'Ņ'=>'N', 'Ŋ'=>'N',
            'ñ'=>'n', 'ń'=>'n', 'ň'=>'n', 'ņ'=>'n', 'ŋ'=>'n', 'ŉ'=>'n',

            'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ō'=>'O', 'Ŏ'=>'O', 'Ő'=>'O', 'Œ'=>'O',
            'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ō'=>'o', 'ŏ'=>'o', 'ő'=>'o', 'œ'=>'o', 'ð'=>'o',

            'Ŕ'=>'R', 'Ř'=>'R',
            'ŕ'=>'r', 'ř'=>'r', 'ŗ'=>'r',

            'Š'=>'S', 'Ŝ'=>'S', 'Ś'=>'S', 'Ş'=>'S',
            'š'=>'s', 'ŝ'=>'s', 'ś'=>'s', 'ş'=>'s',

            'Ŧ'=>'T', 'Ţ'=>'T', 'Ť'=>'T',
            'ŧ'=>'t', 'ţ'=>'t', 'ť'=>'t',

            'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ũ'=>'U', 'Ū'=>'U', 'Ŭ'=>'U', 'Ů'=>'U', 'Ű'=>'U', 'Ų'=>'U',
            'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ũ'=>'u', 'ū'=>'u', 'ŭ'=>'u', 'ů'=>'u', 'ű'=>'u', 'ų'=>'u',

            'Ŵ'=>'W', 'Ẁ'=>'W', 'Ẃ'=>'W', 'Ẅ'=>'W',
            'ŵ'=>'w', 'ẁ'=>'w', 'ẃ'=>'w', 'ẅ'=>'w',

            'Ý'=>'Y', 'Ÿ'=>'Y', 'Ŷ'=>'Y',
            'ý'=>'y', 'ÿ'=>'y', 'ŷ'=>'y',

            'Ž'=>'Z', 'Ź'=>'Z', 'Ż'=>'Z',
            'ž'=>'z', 'ź'=>'z', 'ż'=>'z',

            '“'=>'"', '”'=>'"', '‘'=>"'", '’'=>"'", '•'=>'-', '…'=>'...', '—'=>'-', '–'=>'-', '¿'=>'?', '¡'=>'!', '°'=>' degrees ',
            '¼'=>' 1/4 ', '½'=>' 1/2 ', '¾'=>' 3/4 ', '⅓'=>' 1/3 ', '⅔'=>' 2/3 ', '⅛'=>' 1/8 ', '⅜'=>' 3/8 ', '⅝'=>' 5/8 ', '⅞'=>' 7/8 ',
            '÷'=>' divided by ', '×'=>' times ', '±'=>' plus-minus ', '√'=>' square root ', '∞'=>' infinity ',
            '≈'=>' almost equal to ', '≠'=>' not equal to ', '≡'=>' identical to ', '≤'=>' less than or equal to ', '≥'=>' greater than or equal to ',
            '←'=>' left ', '→'=>' right ', '↑'=>' up ', '↓'=>' down ', '↔'=>' left and right ', '↕'=>' up and down ',
            '℅'=>' care of ', '℮' => ' estimated ',
            'Ω'=>' ohm ',
            '♀'=>' female ', '♂'=>' male ',
            '©'=>' Copyright ', '®'=>' Registered ', '™' =>' Trademark ',
        );
        $string = strtr($string, $table);
        // Currency symbols: £¤¥€  - we dont bother with them for now
        $string = preg_replace("/[^\x9\xA\xD\x20-\x7F]/u", "", $string);
        $string = preg_replace("/[\r\n]*/","",$string);
        return $string;
    }

    /**
     * Makes a message safe to print as HTML. Decodes first, so a message that
     * Module::l() already escaped is not escaped twice.
     */
    public static function escapeHtml($message)
    {
        $message = html_entity_decode((string) $message, ENT_QUOTES, 'UTF-8');

        return htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}