<?php

namespace System\Helpers;

/**
 * Help to work with texts
 *
 * @author mathieu
 */
class Text {

    private static $foreign_characters = [
        '#ä|æ|ǽ#'                         => 'ae',
        '#ö|œ#'                           => 'oe',
        '#ü#'                             => 'ue',
        '#Ä#'                             => 'Ae',
        '#Ü#'                             => 'Ue',
        '#Ö#'                             => 'Oe',
        '#À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ#'         => 'A',
        '#à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª#'         => 'a',
        '#Ç|Ć|Ĉ|Ċ|Č#'                     => 'C',
        '#ç|ć|ĉ|ċ|č#'                     => 'c',
        '#Ð|Ď|Đ#'                         => 'D',
        '#ð|ď|đ#'                         => 'd',
        '#È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě#'             => 'E',
        '#è|é|ê|ë|ē|ĕ|ė|ę|ě#'             => 'e',
        '#Ĝ|Ğ|Ġ|Ģ#'                       => 'G',
        '#ĝ|ğ|ġ|ģ#'                       => 'g',
        '#Ĥ|Ħ#'                           => 'H',
        '#ĥ|ħ#'                           => 'h',
        '#Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ#'           => 'I',
        '#ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı#'           => 'i',
        '#Ĵ#'                             => 'J',
        '#ĵ#'                             => 'j',
        '#Ķ#'                             => 'K',
        '#ķ#'                             => 'k',
        '#Ĺ|Ļ|Ľ|Ŀ|Ł#'                     => 'L',
        '#ĺ|ļ|ľ|ŀ|ł#'                     => 'l',
        '#Ñ|Ń|Ņ|Ň#'                       => 'N',
        '#ñ|ń|ņ|ň|ŉ#'                     => 'n',
        '#Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ#'         => 'O',
        '#ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º#'       => 'o',
        '#Ŕ|Ŗ|Ř#'                         => 'R',
        '#ŕ|ŗ|ř#'                         => 'r',
        '#Ś|Ŝ|Ş|Š#'                       => 'S',
        '#ś|ŝ|ş|š|ſ#'                     => 's',
        '#Ţ|Ť|Ŧ#'                         => 'T',
        '#ţ|ť|ŧ#'                         => 't',
        '#Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ#' => 'U',
        '#ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ#' => 'u',
        '#Ý|Ÿ|Ŷ#'                         => 'Y',
        '#ý|ÿ|ŷ#'                         => 'y',
        '#Ŵ#'                             => 'W',
        '#ŵ#'                             => 'w',
        '#Ź|Ż|Ž#'                         => 'Z',
        '#ź|ż|ž#'                         => 'z',
        '#Æ|Ǽ#'                           => 'AE',
        '#ß#'                             => 'ss',
        '#Ĳ#'                             => 'IJ',
        '#ĳ#'                             => 'ij',
        '#Œ#'                             => 'OE',
        '#ƒ#'                             => 'f'
    ];
    private static $search             = NULL;
    private static $replace            = NULL;

    // -------------------------------------------------------------------------

    /**
     * Convert Accented Characters to ASCII
     *
     * @param string $str
     * @return string
     */
    public static function convertAccents($str) {
        if (self::$search === NULL || self::$replace === NULL) {
            self::$search  = array_keys(self::$foreign_characters);
            self::$replace = array_values(self::$foreign_characters);
        }
        return preg_replace(self::$search, self::$replace, $str);
    }

    // -------------------------------------------------------------------------

    /**
     * Explode a string and trim all elements
     * 
     * @param string $char
     * @param string $str
     * @return array
     */
    public static function split($char, $str) {

        if (strpos('#!^$()[]{}|?+*.\\', $char) !== FALSE) {
            $char = '\\' . $char;
        }

        return preg_split("#\s*{$char}\s*#", $str, NULL, PREG_SPLIT_NO_EMPTY);
    }

    // -------------------------------------------------------------------------

    /**
     * Code Highlighter : Colorizes code strings
     *
     * @param	string	the text string
     * @return	string
     */
    public static function highlightCode($str) {
        // The highlight string function encodes and highlights
        // brackets so we need them to start raw
        $str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $str);

        // Replace any existing PHP tags to temporary markers so they don't accidentally
        // break the string out of PHP, and thus, thwart the highlighting.

        $str = str_replace(array('<?', '?>', '<%', '%>', '\\', '</script>'), array('phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'), $str);

        // The highlight_string function requires that the text be surrounded
        // by PHP tags, which we will remove later
        $str = '<?php ' . $str . ' ?>'; // <?
        // All the magic happens here, baby!
        $str = highlight_string($str, TRUE);

        // Remove our artificially added PHP, and the syntax highlighting that came with it
        $str = preg_replace('/<span style="color: #([A-Z0-9]+)">&lt;\?php(&nbsp;| )/i', '<span style="color: #$1">', $str);
        $str = preg_replace('/(<span style="color: #[A-Z0-9]+">.*?)\?&gt;<\/span>\n<\/span>\n<\/code>/is', "$1</span>\n</span>\n</code>", $str);
        $str = preg_replace('/<span style="color: #[A-Z0-9]+"\><\/span>/i', '', $str);

        // Replace our markers back to PHP tags.
        $str = str_replace(array('phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'), array('&lt;?', '?&gt;', '&lt;%', '%&gt;', '\\', '&lt;/script&gt;'), $str);

        return $str;
    }

    // -------------------------------------------------------------------------

    /**
     * Phrase Highlighter : Highlights a phrase within a text string
     *
     * @param	string	the text string
     * @param	string	the phrase you'd like to highlight
     * @param	string	the openging tag to precede the phrase with
     * @param	string	the closing tag to end the phrase with
     * @return	string
     */
    public static function highlightPhrase($str, $phrase, $tag_open = '<strong>', $tag_close = '</strong>') {
        if ($str == '') {
            return '';
        }

        if ($phrase != '') {
            return preg_replace('/(' . preg_quote($phrase, '/') . ')/i', $tag_open . "\\1" . $tag_close, $str);
        }

        return $str;
    }

    // -------------------------------------------------------------------------

    /**
     * Format a string for urls
     *
     * @param string $str A string not formatted
     * @param boolean $lowercase True to returns a lowercase string
     * @return string Returns a string formatted for urls
     */
    public static function urlTitle($str, $lowercase = TRUE) {

        if ($str == '') {
            return '';
        }

        $separator = '-';

        $stopwords1 = ' d\'| l\'| de | une | un | le | la | les | des |'
                . ' à | a | en |-d\'| est | nous | vous | ils | par |'
                . ' sur | dans | vos | nos | qui | que | pour | votre |'
                . ' notre | mais | pas | tes | mes | dans | avec | dès | aux ';

        $stopwords2 = '^un |^une |^le |^les |^du |^des |^de la ';

        $trans = [
            $stopwords1          => ' ',
            $stopwords2          => '',
            ' |\'|\/'            => $separator,
            '[^s0-9=@A-Z\-a-z]*' => '',
            '-+|-d-|-l-'         => $separator
        ];

        $str = Text::convertAccents($str);
        $str = strip_tags($str);

        foreach ($trans as $key => $val) {
            $str = preg_replace("#" . $key . "#i", $val, $str);
        }

        if ($lowercase) {
            $str = mb_strtolower($str);
        }

        return trim($str, $separator);
    }

    // -------------------------------------------------------------------------

    /**
     * Convert strings with underscores into CamelCase
     *
     * @param string $str The string to convert
     * @param bool $firstCharCaps camelCase or CamelCase
     * @return string The converted string
     */
    public static function underscoreToCamel($str, $firstCharCaps = FALSE) {

        if ($str == '') {
            return '';
        }

        if ($firstCharCaps) {
            $str[0] = strtoupper($str[0]);
        }

        return preg_replace_callback('/_([a-z])/', function($c) {
            return strtoupper($c[1]);
        }, $str);
    }

    // -------------------------------------------------------------------------

    /**
     * Convert a camel case string to underscores
     *
     * @param string $str The string to convert
     * @return string The converted string
     */
    public static function camelToUnderscore($str) {
        $str[0] = strtolower($str[0]);

        return preg_replace_callback('/([A-Z])/', function($c) {
            return "_" . strtolower($c[1]);
        }, $str);
    }

    // -------------------------------------------------------------------------

    /**
     * SQL Like operator in PHP.
     * Returns TRUE if match else FALSE.
     *
     * @param   string $pattern
     * @param   string $subject
     * @return  bool
     */
    public static function like($pattern, $subject) {
        $pattern = str_replace('%', '.*', preg_quote($pattern));
        return (bool) preg_match("/^{$pattern}$/i", $subject);
    }

    // -------------------------------------------------------------------------

    public static function de($str) {
        $retour = 'de ' . $str;
        $car    = mb_strtolower(substr(trim($str), 0, 1));

        if ($car != '') {
            if ($car[0] == 'a' || $car[0] == 'e' || $car[0] == 'i' || $car[0] == 'o' || $car[0] == 'u' || $car[0] == 'y' || $car[0] == 'h') {
                $retour = 'd\'' . $str;
            }
        }
        return $retour;
    }

    // -------------------------------------------------------------------------

    public static function le($str) {
        $retour = 'le ' . $str;
        $car    = mb_strtolower(substr(trim($str), 0, 1));

        if ($car != '') {
            if ($car[0] == 'a' || $car[0] == 'e' || $car[0] == 'i' || $car[0] == 'o' || $car[0] == 'u' || $car[0] == 'y') {
                $retour = 'l\'' . $str;
            }
        }
        return $retour;
    }

    // -------------------------------------------------------------------------

    public static function la($str) {
        $retour = 'la ' . $str;
        $car    = mb_strtolower(substr(trim($str), 0, 1));

        if ($car != '') {
            if ($car[0] == 'a' || $car[0] == 'e' || $car[0] == 'i' || $car[0] == 'o' || $car[0] == 'u' || $car[0] == 'y') {
                $retour = 'l\'' . $str;
            }
        }
        return $retour;
    }

    // -------------------------------------------------------------------------

    public static function plural($amount, $singular = '', $plural = 's') {
        if ($amount == 1) {
            return $singular;
        }
        else {
            return $plural;
        }
    }

    // -------------------------------------------------------------------------

    public static function hashtagify($str, $search) {
        $replace = [];
        foreach ($search as $value) {
            $replace[] = '#'.$value;
        }

        return str_replace($search, $replace, $str);
    }
}

/* End of file */