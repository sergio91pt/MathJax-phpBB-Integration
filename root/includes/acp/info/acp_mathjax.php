<?php
/** 
*
* @package acp
* @version $Id $
* @copyright (c) 2011 Sérgio Faria 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License v2 
*
*/

/**
* @package module_install
*/
class acp_mathjax_info
{
    function module()
    {
    return array(
        'filename'    => 'acp_mathjax',
        'title'        => 'ACP_MATHJAX',
        'version'    => '1.0.0',
        'modes'        => array(
            'settings'		=> array('title' => 'ACP_MATHJAX_SETTINGS', 'auth' => 'acl_a_server', 'cat' => array('ACP_MATHJAX_CAT')),
            'bbcode'		=> array('title' => 'ACP_MATHJAX_BBCODES', 'auth' => 'acl_a_bbcode', 'cat' => array('ACP_MATHJAX_CAT')),
            ),
        );
        
    }

    function install()
    {
    }

    function uninstall()
    {
    }

}
?>