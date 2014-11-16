<?php
namespace eaglehorn\core\worker;

/**
 * EagleHorn
 *
 * An open source application development framework for PHP 5.3 or newer
 *
 * @package        EagleHorn
 * @author        Abhishek Saha <abhisheksaha11 AT gmail DOT com>
 * @license        Available under MIT licence
 * @link        http://eaglehorn.org
 * @since        Version 1.0
 * @filesource
 *
 *
 * @desc  Responsible for handling text decorations
 *
 */
class Text
{


    /**
     * @param $str
     * @return mixed|string
     */
    function codeHighlight($str)
    {
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

        // Prior to PHP 5, the highligh function used icky <font> tags
        // so we'll replace them with <span> tags.

        if (abs(PHP_VERSION) < 5) {
            $str = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $str);
            $str = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $str);
        }

        // Remove our artificially added PHP, and the syntax highlighting that came with it
        $str = preg_replace('/<span style="color: #([A-Z0-9]+)">&lt;\?php(&nbsp;| )/i', '<span style="color: #$1">', $str);
        $str = preg_replace('/(<span style="color: #[A-Z0-9]+">.*?)\?&gt;<\/span>\n<\/span>\n<\/code>/is', "$1</span>\n</span>\n</code>", $str);
        $str = preg_replace('/<span style="color: #[A-Z0-9]+"\><\/span>/i', '', $str);

        // Replace our markers back to PHP tags.
        $str = str_replace(array('phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'), array('&lt;?', '?&gt;', '&lt;%', '%&gt;', '\\', '&lt;/script&gt;'), $str);

        return $str;
    }

    /**
     * @param $str
     * @param $phrase
     * @param string $tag_open
     * @param string $tag_close
     * @return mixed|string
     */
    function highlightPhrase($str, $phrase, $tag_open = '<strong>', $tag_close = '</strong>')
    {
        if ($str == '') {
            return '';
        }

        if ($phrase != '') {
            return preg_replace('/(' . preg_quote($phrase, '/') . ')/i', $tag_open . "\\1" . $tag_close, $str);
        }

        return $str;
    }

}