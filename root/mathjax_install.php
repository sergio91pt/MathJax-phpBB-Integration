<?php
/**
 *
 * @author sergio91pt (Sérgio Faria) sergio91pt@gmail.com
 * @version $Id$
 * @copyright (c) 2011 S??rgio Faria
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * @ignore
 */
define('UMIL_AUTO', true);
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

include($phpbb_root_path . 'common.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup();


if (!file_exists($phpbb_root_path . 'umil/umil_auto.' . $phpEx))
{
	trigger_error('Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>', E_USER_ERROR);
}

// The name of the mod to be displayed during installation.
$mod_name = 'MATHJAX';

/*
* The name of the config variable which will hold the currently installed version
* UMIL will handle checking, setting, and updating the version itself.
*/
$version_config_name = 'mathjax_mod_version';


// The language file which will be included when installing
$language_file = 'mods/info_acp_mathjax';


/*
* Optionally we may specify our own logo image to show in the upper corner instead of the default logo.
* $phpbb_root_path will get prepended to the path specified
* Image height should be 50px to prevent cut-off or stretching.
*/
//$logo_img = 'styles/prosilver/imageset/site_logo.gif';

/*
* The array of versions and actions within each.
* You do not need to order it a specific way (it will be sorted automatically), however, you must enter every version, even if no actions are done for it.
*
* You must use correct version numbering.  Unless you know exactly what you can use, only use X.X.X (replacing X with an integer).
* The version numbering must otherwise be compatible with the version_compare function - http://php.net/manual/en/function.version-compare.php
*/
$versions = array(
	'0.2.0' => array(

		'table_column_add' => array(
			array(BBCODES_TABLE, 'is_math', array('BOOL', '0')),
		),

		'config_add' => array(
			array('mathjax_cdn', 'http://cdn.mathjax.org/mathjax/latest', false),
			array('mathjax_cdn_ssl', 'https://d3eoax9i5htok0.cloudfront.net/mathjax/latest', false),
			array('mathjax_config', 'TeX-AMS-MML_HTMLorMML', false),
			array('mathjax_dynamic_load', true, false),
			array('mathjax_enable', false, false), // Because it must be configured first (for now)
			array('mathjax_cdn_force_ssl', false, false),
			array('mathjax_uri', '', false),
			array('mathjax_use_cdn', false, false),
		),

		'module_add' => array(
			array('acp', 'ACP_CAT_DOT_MODS', 'ACP_MATHJAX_CAT'),
			
			array('acp', 'ACP_MATHJAX_CAT',
				array('module_basename'	=> 'mathjax'),
			),
		),

	),
);

// Include the UMIL Auto file, it handles the rest
include($phpbb_root_path . 'umil/umil_auto.' 

. $phpEx);