<?php
/**
 * The Blink HTMLTokenizer ported to PHP.
 *
 * @license MIT
 */

namespace zz\Html;

class HTMLMinify {
    const ENCODING = 'UTF-8';

    const REMOVE_WHITE_SPACE = 1;
    const REMOVE_SPACE_ONLY = 2;

    const DOCTYPE_HTML4 = 'html4';
    const DOCTYPE_HTML5 = 'html5';

    const OPTIMIZATION_SIMPLE = 0;
    const OPTIMIZATION_ADVANCED = 1;

    /**
     * @var null|string
     */
    protected $html = null;
    /**
     * @var array
     */
    protected $options = array();
    /**
     * @var HtmlToken[] $tokens
     */
    protected $tokens;

    protected $tagDisplay = array(
        'a' => 'inline',
        'abbr' => 'inline',
        'acronym' => 'inline',
        'address' => 'block',
        'applet' => 'inline',
        'area' => 'none',
        'article' => 'block',
        'aside' => 'block',
        'audio' => 'inline',
        'b' => 'inline',
        'base' => 'inline',
        'basefont' => 'inline',
        'bdo' => 'inline',
        'bgsound' => 'inline',
        'big' => 'inline',
        'blockquote' => 'block',
        'body' => 'block',
        'br' => 'inline',
        'button' => 'inline-block',
        'canvas' => 'inline',
        'caption' => 'table-caption',
        'center' => 'block',
        'cite' => 'inline',
        'code' => 'inline',
        'col' => 'table-column',
        'colgroup' => 'table-column-group',
        'command' => 'inline',
        'datalist' => 'none',
        'dd' => 'block',
        'del' => 'inline',
        'details' => 'block',
        'dfn' => 'inline',
        'dir' => 'block',
        'div' => 'block',
        'dl' => 'block',
        'dt' => 'block',
        'em' => 'inline',
        'embed' => 'inline',
        'fieldset' => 'block',
        'figcaption' => 'block',
        'figure' => 'block',
        'font' => 'inline',
        'footer' => 'block',
        'form' => 'block',
        'frame' => 'block',
        'frameset' => 'block',
        'h1' => 'block',
        'h2' => 'block',
        'h3' => 'block',
        'h4' => 'block',
        'h5' => 'block',
        'h6' => 'block',
        'head' => 'none',
        'header' => 'block',
        'hgroup' => 'block',
        'hr' => 'block',
        'html' => 'block',
        'i' => 'inline',
        'iframe' => 'inline',
        'image' => 'inline',
        'img' => 'inline',
        'input' => 'inline-block',
        'ins' => 'inline',
        'isindex' => 'inline-block',
        'kbd' => 'inline',
        'keygen' => 'inline-block',
        'label' => 'inline',
        'layer' => 'block',
        'legend' => 'block',
        'li' => 'list-item',
        'link' => 'none',
        'listing' => 'block',
        'map' => 'inline',
        'mark' => 'inline',
        'marquee' => 'inline-block',
        'menu' => 'block',
        'meta' => 'none',
        'meter' => 'inline-block',
        'nav' => 'block',
        'nobr' => 'inline',
        'noembed' => 'inline',
        'noframes' => 'none',
        'nolayer' => 'inline',
        'noscript' => 'inline',
        'object' => 'inline',
        'ol' => 'block',
        'optgroup' => 'inline',
        'option' => 'inline',
        'output' => 'inline',
        'p' => 'block',
        'param' => 'none',
        'plaintext' => 'block',
        'pre' => 'block',
        'progress' => 'inline-block',
        'q' => 'inline',
        'rp' => 'inline',
        'rt' => 'inline',
        'ruby' => 'inline',
        's' => 'inline',
        'samp' => 'inline',
        'script' => 'none',
        'section' => 'block',
        'select' => 'inline-block',
        'small' => 'inline',
        'source' => 'inline',
        'span' => 'inline',
        'strike' => 'inline',
        'strong' => 'inline',
        'style' => 'none',
        'sub' => 'inline',
        'summary' => 'block',
        'sup' => 'inline',
        'table' => 'table',
        'tbody' => 'table-row-group',
        'td' => 'table-cell',
        'textarea' => 'inline-block',
        'tfoot' => 'table-footer-group',
        'th' => 'table-cell',
        'thead' => 'table-header-group',
        'title' => 'none',
        'tr' => 'table-row',
        'track' => 'inline',
        'tt' => 'inline',
        'u' => 'inline',
        'ul' => 'inline-block',
        'var' => 'inline',
        'video' => 'inline',
        'wbr' => 'inline',
        'xmp' => 'block',
    );

    /**
     * @param string $html
     * @param array $options
     */
    public function __construct($html, $options = array()) {
        $html = ltrim($html);
        $this->html = $html;
        $this->options = $this->options($options);

        $SegmentedString = new SegmentedString($html);
        $HTMLTokenizer = new HTMLTokenizer($SegmentedString, $options);
        $this->tokens = $HTMLTokenizer->tokenizer();
    }

    /**
     * 'startTagBeforeSlash'
     *     HTML4.01  no slash  : <img src=""><br>
     *     XHTML1.0  add slash : <img src="" /><br />
     *     HTML5     mixed OK  : <img src=""><br />
     *
     *     example : <img src="" /><br >
     *     REMOVE_WHITE_SPACE(default) : <img src=""/><br>
     *
     * 'comment'
     *     example : <!-- HTML --><!--[if expression]> HTML <![endif]--><![if expression]> HTML <![endif]>
     *     true(default) => <!--[if expression]> HTML <![endif]--><![if expression]> HTML <![endif]>
     *     false         => do nothing
     *
     * 'deleteDuplicateAttribute'
     *     example : <img src="first.png" src="second.png">
     *     true(default) => <img src="first.png">
     *     false         => do nothing
     *
     * 'excludeComment'
     *     example : <!--nocache-->content</--nocache-->
     *     array()(default)             => content
     *     array('/<!--\/?nocache-->/') => <!--nocache-->content</--nocache-->
     *
     * 'optimizationLevel'
     *     OPTIMIZATION_SIMPLE(default)
     *         : replace many whitespace to a single whitespace
     *           this option leave a new line of one
     *     OPTIMIZATION_ADVANCED
     *         : remove the white space of all as much as possible
     *
     * @param array $options
     * @return array
     */
    protected function options(Array $options) {
        $_options = array(
            'startTagBeforeSlash' => static::REMOVE_WHITE_SPACE,
            'comment' => true,
            'deleteDuplicateAttribute' => true,
            'excludeComment' => array(),
            'optimizationLevel' => static::OPTIMIZATION_SIMPLE,
        );
        return $options + $_options;
    }

    /**
     * @param $html
     * @param array $options
     * @return string
     */
    public static function minify($html, $options = array()) {
        $instance = new self($html, $options);
        return $instance->process();
    }

    /**
     * @return HtmlToken[]
     */
    public function getTokens() {
        return $this->tokens;
    }

    /**
     * @return string
     */
    public function process() {
        $this->beforeFilter();
        $html = $this->_buildHtml($this->tokens);
        return $html;
    }

    /**
     * @param array $tokens
     * @return string
     */
    protected function _buildHtml(Array $tokens) {
        $html = '';
        foreach ($tokens as $token) {
            $html .= $this->_buildElement($token);
        }
        return $html;
    }

    protected function _buildElement(HTMLToken $token) {
        switch ($token->getType()) {
            case HTMLToken::DOCTYPE:
                $html = $token->getHtmlOrigin();
                break;
            case HTMLToken::StartTag:
                $selfClosing = $token->hasSelfClosing() ? '/' : '';
                if ($selfClosing) {
                    $selfClosing = ($this->options['startTagBeforeSlash'] === static::REMOVE_WHITE_SPACE ? '' : ' ') . $selfClosing;
                }
                $attributes = $this->_buildAttributes($token);
                $beforeAttributeSpace = '';
                if ($attributes) {
                    $beforeAttributeSpace = ' ';
                }
                $html = sprintf('<%s%s%s%s>', $token->getTagName(), $beforeAttributeSpace, $attributes, $selfClosing);
                break;
            case HTMLToken::EndTag:
                $html = sprintf('</%s>', $token->getTagName());
                break;
            default :
                $html = $token->getData();
                break;
        }
        return $html;
    }

    /**
     * @param HTMLToken $token
     * @return string
     */
    protected function _buildAttributes(HTMLToken $token) {
        $attr = array();
        $format = '%s=%s%s%s';
        foreach ($token->getAttributes() as $attribute) {
            $name = $attribute['name'];
            $value = $attribute['value'];
            switch ($attribute['quoted']) {
                case HTMLToken::DoubleQuoted:
                    $quoted = '"';
                    break;
                case HTMLToken::SingleQuoted:
                    $quoted = '\'';
                    break;
                default:
                    $quoted = '';
                    break;
            }
            if ($quoted === '' && $value === '') {
                $attr[] = $name;
            } else {
                $attr[] = sprintf($format, $name, $quoted, $value, $quoted);
            }
        }
        return join(' ', $attr);
    }

    protected function beforeFilter() {
        $this->removeWhitespaceFromComment();
        $this->removeWhitespaceFromCharacter();

        if ($this->options['deleteDuplicateAttribute']) {
            $this->optimizeStartTagAttributes();
        }
    }

    protected function removeWhitespaceFromComment() {
        $tokens = $this->tokens;
        $regexps = $this->options['excludeComment'];

        for ($i = 0, $len = count($tokens); $i < $len; $i++) {
            $token = $tokens[$i];
            $type = $token->getType();
            if ($type === HTMLToken::StartTag) {
                $tagName = $token->getTagName();
                if ($tagName === HTMLNames::scriptTag || $tagName === HTMLNames::styleTag) {
                    $i++;
                    continue;
                }
            } else if ($this->_isConditionalComment($token)) {
                continue;
            }
            if ($type !== HTMLToken::Comment) {
                continue;
            }
            if ($regexps) {
                $comment = $token->getData();
                foreach ($regexps as $regexp) {
                    if (preg_match($regexp, $comment)) {
                        continue 2;
                    }
                }
            }

            unset($tokens[$i]);
            $tokens = array_merge($tokens, array());
            $len = count($tokens);
            $i--;
        }

        /**
         * @var HTMLToken[] $tokens
         */
        $tokens = array_merge($tokens, array());

        // combine chars
        for ($i = 1, $len = count($tokens); $i < $len; $i++) {
            $token = $tokens[$i];
            if ($token->getType() !== HTMLToken::Character) {
                continue;
            }
            $token_before = $tokens[$i - 1];
            if ($token_before->getType() !== HTMLToken::Character) {
                continue;
            }
            $tokens[$i]->setData($token_before . $token->getData());
            unset($tokens[$i - 1]);
            $len = count($tokens);
            $tokens = array_merge($tokens, array());
            $i--;
        }
        $tokens = array_merge($tokens, array());
        $this->tokens = $tokens;
    }

    protected function isInlineTag($tag) {
        $tags = $this->tagDisplay;
        if (!isset($tags[$tag])) {
            return true;
        }
        return $tags[$tag] === 'inline';
    }

    protected function removeWhitespaceFromCharacter() {
        $tokens = $this->tokens;
        $isEditable = true;
        $isBeforeInline = false;
        $uneditableTag = null;
        $type = null;
        $token = null;
        $isOptimize = $this->options['optimizationLevel'] === static::OPTIMIZATION_ADVANCED;

        for ($i = 0, $len = count($tokens); $i < $len; $i++) {
            /**
             * @var HTMLToken $tokenBefore
             */
            $tokenBefore = $token;
            $token = $tokens[$i];
            $type = $token->getType();
            if ($type === HTMLToken::StartTag) {
                $isBeforeInline = $this->isInlineTag($token->getTagName());
                switch ($token->getTagName()) {
                    case HTMLNames::scriptTag:
                    case HTMLNames::styleTag:
                    case HTMLNames::textareaTag:
                    case HTMLNames::preTag:
                        $isEditable = false;
                        $uneditableTag = $token->getTagName();
                        continue 2;
                        break;
                    default:
                        break;
                }
            } else if ($type === HTMLToken::EndTag) {
                $isBeforeInline = $this->isInlineTag($token->getTagName());
                if (!$isEditable && $token->getTagName() === $uneditableTag) {
                    $uneditableTag = null;
                    $isEditable = true;
                    continue;
                }
            }
            if ($type !== HTMLToken::Character) {
                continue;
            }

            $characters = $token->getData();

            if ($isEditable) {
                if ($isOptimize && $i < ($len - 1)) {
                    $afterToken = $tokens[$i + 1];
                    $afterType = $afterToken->getType();
                    if (!$tokenBefore) {
                        $tokenBefore = new HTMLToken();
                    }
                    $typeBefore = $tokenBefore->getType();
                    $isTagBefore = $typeBefore === HTMLToken::StartTag || $typeBefore === HTMLToken::EndTag;
                    $isAfterTag = $afterType === HTMLToken::StartTag || $afterType === HTMLToken::EndTag;
                    $isAfterInline = $isAfterTag ? $this->isInlineTag($afterToken->getTagName()) : false;

                    if (($i === 0 || $isTagBefore) && $isAfterTag && (!$isBeforeInline || !$isAfterInline)) {
                        $characters = trim($characters);
                    } else if (($i === 0 || !$isBeforeInline) && !$isAfterInline) {
                        $characters = trim($characters);
                    }
                }
                $characters = $this->_removeWhitespaceFromCharacter($characters);
                if ($i === ($len - 1)) {
                    $characters = rtrim($characters);
                }
            } else if ($isOptimize && ($uneditableTag === HTMLNames::scriptTag || $uneditableTag === HTMLNames::styleTag)) {
                $characters = trim($characters);
            }
            $tokens[$i]->setData($characters);
        }
        $this->tokens = $tokens;
    }

    /**
     * @param string $characters
     * @return string
     */
    protected function _removeWhitespaceFromCharacter($characters) {
        $compactCharacters = '';
        $hasWhiteSpace = false;

        for ($i = 0, $len = mb_strlen($characters, static::ENCODING); $i < $len; $i++) {
            $char = mb_substr($characters, $i, 1, static::ENCODING);
            if ($char === "\x0A") {
                // remove before whitespace char
                if ($hasWhiteSpace) {
                    $compactCharacters = mb_substr($compactCharacters, 0, -1, static::ENCODING);
                }
                $compactCharacters .= $char;
                $hasWhiteSpace = true;
            } else if ($char === ' ' || $char === "\x09" || $char === "\x0C") {
                if (!$hasWhiteSpace) {
                    $compactCharacters .= ' ';
                    $hasWhiteSpace = true;
                }
            } else {
                $hasWhiteSpace = false;
                $compactCharacters .= $char;
            }
        }

        return $compactCharacters;
    }

    protected function optimizeStartTagAttributes() {
        $tokens = $this->tokens;
        for ($i = 0, $len = count($tokens); $i < $len; $i++) {
            $token = $tokens[$i];
            if ($token->getType() !== HTMLToken::StartTag) {
                continue;
            }

            $attributes_old = $token->getAttributes();
            $attributes_new = array();
            $attributes_name = array();

            foreach ($attributes_old as $attribute) {
                if (!isset($attributes_name[$attribute['name']])) {
                    $attributes_name[$attribute['name']] = true;
                    $attributes_new[] = $attribute;
                }
            }
            if ($attributes_old !== $attributes_new) {
                $token->setAttributes($attributes_new);
            }
        }
        $this->tokens = $tokens;
    }

    /**
     * downlevel-hidden : <!--[if expression]> HTML <![endif]-->
     * downlevel-revealed : <![if expression]> HTML <![endif]>
     * @param HTMLToken $token
     * @return bool
     */
    protected function _isConditionalComment(HTMLToken $token) {
        if ($token->getType() !== HTMLToken::Comment) {
            return false;
        }

        $comment = $this->_buildElement($token);
        $pattern = '/\A<!(?:--)?\[if [^\]]+\]>/s';
        if (preg_match($pattern, $comment)) {
            return true;
        }
        $pattern = '/<!\[endif\](?:--)?>\Z/s';
        if (preg_match($pattern, $comment)) {
            return true;
        }
        return false;
    }

}