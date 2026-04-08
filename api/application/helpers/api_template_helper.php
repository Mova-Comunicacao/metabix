<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Html purify the given HTML
 *
 * @param  string $content
 *
 * @return string
 */
function html_purify($content)
{
    if (empty($content) || hooks()->apply_filters('html_purify_content', true) === false) {
        return $content;
    }

    $CI = &get_instance();
    $CI->load->config('migration');

    $config = HTMLPurifier_HTML5Config::create(
        HTMLPurifier_HTML5Config::createDefault()
    );

    $config->set('HTML.DefinitionID', 'CustomHTML5');
    $config->set('HTML.DefinitionRev', $CI->config->item('migration_version'));

    // Disables cache
    if (ENVIRONMENT !== 'production') {
        $config->set('Cache.DefinitionImpl', null);
    }

    //allow YouTube and Vimeo
    // $regex = hooks()->apply_filters('html_purify_safe_iframe_regexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');

    // $config->set('URI.SafeIframeRegexp', $regex);
    // $config->set('HTML.SafeIframe', true);
    $config->set('Attr.AllowedFrameTargets', ['_blank']);
    $config->set('Core.EscapeNonASCIICharacters', true);
    $config->set('CSS.AllowTricky', true);

    // These config option disables the pixel checks and allows
    // specifiy e.q. widht="auto" or height="auto" for example on images
    $config->set('HTML.MaxImgLength', null);
    $config->set('CSS.MaxImgLength', null);

    hooks()->apply_filters('html_purifier_config', $config);

    $def = $config->maybeGetRawHTMLDefinition();

    if ($def) {
        $def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top');
        $def->addAttribute('p', 'pagebreak', 'Text');
        $def->addAttribute('div', 'align', 'Enum#left,right,center');
        $def->addAttribute('span', 'data-mention-id', 'Number');
        $def->addElement(
            'iframe',
            'Inline',
            'Flow',
            'Common',
            [
                'src'                   => 'URI#embedded',
                'width'                 => 'Length',
                'height'                => 'Length',
                'name'                  => 'ID',
                'scrolling'             => 'Enum#yes,no,auto',
                'frameborder'           => 'Enum#0,1',
                'allow'                 => 'Text',
                'allowfullscreen'       => 'Bool',
                'webkitallowfullscreen' => 'Bool',
                'mozallowfullscreen'    => 'Bool',
                'longdesc'              => 'URI',
                'marginheight'          => 'Pixels',
                'marginwidth'           => 'Pixels',
            ]
        );
    }

    $purifier = new HTMLPurifier($config);

    return $purifier->purify($content);
}