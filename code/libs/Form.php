<?php

class Form {

    const TYPE_TEXT = 'text';
    const TYPE_LONGTEXT = 'longtext';
    const TYPE_CHOICE = 'choice';
    const TYPE_IMAGEUPLOAD = 'imageupload';
    const TYPE_URL = 'url';

    const RULE_REQUIRED = 'required';
    const RULE_MINLEN = 'minlen';
    const RULE_MAXLEN = 'maxlen';
    const RULE_URL = 'url';
    const RULE_URL_IMAGE = 'image';
    const RULE_URL_AUDIO = 'audio';
    const RULE_URL_VIDEO = 'video';
    const RULE_IMAGE_UPLOAD = 'imageupload';
    const RULE_VALID_OPTION = 'validoption';

    const REGEX_URL       = '#^https?://#i';
    const REGEX_URL_IMAGE = '#^https?://.*(jpe?g|gif|png)$#i';
    const REGEX_URL_AUDIO = '#^https?://.*mp3$#i';
    const REGEX_URL_VIDEO = '#^https?://(www\.)?(vimeo|youtube)#i';

    const FORMAT_PLAIN = 'Plain';
    const FORMAT_HTML = 'HTML';
    const FORMAT_TEXTILE = 'Textile';
    const FORMAT_MARKDOWN = 'Markdown';

    static $formats = array(self::FORMAT_PLAIN, self::FORMAT_HTML, self::FORMAT_TEXTILE, self::FORMAT_MARKDOWN);

}