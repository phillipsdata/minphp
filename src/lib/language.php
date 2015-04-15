<?php
use minphp\Language\Language as FrameworkLanguage;

/**
 * {@inheritdoc}
 */
class Language extends FrameworkLanguage
{
    /**
     * {@inheritdoc}
     */
    public static function getText($lang_key, $return = false)
    {
        $allow_passthrough = self::$allow_passthrough;
        $default_language = self::$default_language;

        self::allowPassthrough(Configure::get('Language.allow_pass_through'));
        self::setDefaultLanguage(Configure::get('Language.default'));

        $args = func_get_args();
        $result = call_user_func_array(array(parent, "getText"), $args);

        self::allowPassthrough($allow_passthrough);
        self::setDefaultLanguage($default_language);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function loadLang($lang_file, $language = null, $lang_dir = LANGDIR)
    {
        $default_language = self::$default_language;

        self::setDefaultLanguage(Configure::get('Language.default'));
        parent::loadLang($lang_file, $language, $lang_dir);

        self::setDefaultLanguage($default_language);
    }
}
