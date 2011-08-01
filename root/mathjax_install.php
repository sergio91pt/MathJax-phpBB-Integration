<?php
/**
 *
 * @author sergio91pt (Sérgio Faria) sergio91pt@gmail.com
 * @version $Id$
 * @copyright (c) 2011 Sérgio Faria
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License v2
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
else if (!file_exists($phpbb_root_path . 'includes/functions_mathjax.' . $phpEx))
{
	trigger_error('Please follow the instalation instructions in install_mod.xml. <br /> A critical file wasn\'t found on your forum.', E_USER_WARNING);
}

include($phpbb_root_path . 'includes/functions_mathjax.' . $phpEx);

// The name of the mod to be displayed during installation.
$mod_name = 'MATHJAX';

/*
* The name of the config variable which will hold the currently installed version
* UMIL will handle checking, setting, and updating the version itself.
*/
$version_config_name = 'mathjax_mod_version';

// Lets fix the bad config name in v0.1.0 and v0.1.1 before continuing
if (isset($config['mathjax_mod_version_version']))
{
	$sql = 'UPDATE ' . CONFIG_TABLE . " 
		SET config_name = '$version_config_name'" . ' 
		WHERE config_name = "mathjax_mod_version_version"';
	$db->sql_query($sql);
	$cache->destroy('config');
} 

/*
* The language file which will be included when installing
* Language entries that should exist in the language file for UMIL (replace $mod_name with the mod's name you set to $mod_name above)
* $mod_name
* 'INSTALL_' . $mod_name
* 'INSTALL_' . $mod_name . '_CONFIRM'
* 'UPDATE_' . $mod_name
* 'UPDATE_' . $mod_name . '_CONFIRM'
* 'UNINSTALL_' . $mod_name
* 'UNINSTALL_' . $mod_name . '_CONFIRM'
*/
$language_file = 'mods/info_acp_mathjax';

/*
* Options to display to the user (this is purely optional, if you do not need the options you do not have to set up this variable at all)
* Uses the acp_board style of outputting information, with some extras (such as the 'default' and 'select_user' options)
*/
$opt_default_cdn = (!empty($config['mathjax_use_cdn'])) ? true : false;
$opt_default_uri = (!empty($config['mathjax_uri'])) ? $config['mathjax_uri'] : ''; 

$options = array(
	'legend2'			=> 'UMIL_CONFIG',
	'mathjax_use_cdn'	=> array('lang' => 'MATHJAX_USE_CDN',	'type' => 'radio:yes_no',	'explain' => true, 'default' => $opt_default_cdn),
	'mathjax_uri'		=> array('lang' => 'MATHJAX_URI',		'type' => 'text:20:255',	'explain' => true, 'default' => $opt_default_uri),
	
	'add_latex_bbcode'	=> array('lang' => 'ADD_LATEX_BBCODE',	'type' => 'radio:yes_no',	'explain' => true, 'default' => !bbcode_exists('latex')),
	'add_mml_bbcode'	=> array('lang' => 'ADD_MML_BBCODE',	'type' => 'radio:yes_no',	'explain' => true, 'default' => !bbcode_exists('math')),

	'legend3'			=> 'ACP_SUBMIT_CHANGES',
);

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
	'0.2.1' => array(
		// No Changes
	),
	
	'0.2.0' => array(

		'custom'	=> array(
			'math_bbcodes',
			'configure_mathjax',
			'add_latex_bbcode',
			'add_mml_bbcode',
		),
	
		'config_add' => array(
			array('mathjax_enable', true),
			array('mathjax_dynamic_load', true),
			array('mathjax_cdn_force_ssl', false),
		),
		
		'config_remove' => array(
			array('mathjax_enable_post'),
			array('mathjax_enable_pm'),
		),
		
		'module_add' => array(
			array('acp', 'ACP_CAT_DOT_MODS', 'ACP_MATHJAX_CAT'),
			
			array('acp', 'ACP_MATHJAX_CAT',
				array('module_basename'	=> 'mathjax'),
			),
		),

	),
	'0.1.1' => array(
		
		'config_add' => array(		
			array('mathjax_enable_post', '1', 0),
			array('mathjax_enable_pm', '1', 0),
		),
		
		'config_remove' => array(
			array('mathjax_enabled_post'),
			array('mathjax_enabled_pm'),
		),
			
	),
	'0.1.0' => array(

		'config_add' => array(
			array('mathjax_enabled_post', '1'),
			array('mathjax_enabled_pm', '1'),
			array('mathjax_cdn', 'http://cdn.mathjax.org/mathjax/latest'),
			array('mathjax_cdn_ssl', 'https://d3eoax9i5htok0.cloudfront.net/mathjax/latest'),
			array('mathjax_config', 'TeX-AMS-MML_HTMLorMML'),
		),

	),
);

// Include the UMIL Auto file, it handles the rest
include($phpbb_root_path . 'umil/umil_auto.' . $phpEx);

/**
 * Adds the is_math column to the bbcodes tables if installing or updating.
 * If uninstalling removes all math bbcodes and removes the column.
 * 
 * @param string $action The action (install|update|uninstall) will be sent through this.
 * @param string $version The version this is being run for will be sent through this.
 */
function math_bbcodes($action, $version)
{
	global $db, $umil;
	
	if ($action == 'install' || $action == 'update')
	{
		if (!$umil->table_column_exists(BBCODES_TABLE, 'is_math'))
		{
			$umil->table_column_add(BBCODES_TABLE, 'is_math', array('BOOL', '0'));
		}
		return 'UMIL_ADD_BBCODE_TABLE';
	}
	else if ($action == 'uninstall')
	{
		$sql = 'DELETE 
			FROM ' . BBCODES_TABLE . ' 
			WHERE is_math = 1';
		$db->sql_query($sql);
		
		$umil->table_column_remove(BBCODES_TABLE, 'is_math');
		return 'UMIL_REMOVE_BBCODES';
	}
}

/**
 * Uses the entered configuration to update mathjax. 
 * This should be the first called before adding/updating the db because it throws errors.
 * 
 * @param string $action The action (install|update|uninstall) will be sent through this.
 * @param string $version The version this is being run for will be sent through this.
 */
function configure_mathjax($action, $version)
{
	global $umil, $user;
	
	if ($action == 'install' || $action == 'update')
	{
		$mathjax_use_cdn = request_var('mathjax_use_cdn', false);
		$mathjax_uri = request_var('mathjax_uri', '');

		if (!validate_mathjax_path($mathjax_uri))
		{
			// If the user left it blank but enabled the cdn we won't complain but...
			if (!empty($mathjax_uri))
			{
				$error = $user->lang['INVALID_MATHJAX_PATH'];
				$mathjax_uri = '';
			}
			else if ($mathjax_use_cdn == false)
			{
				$error = $user->lang['MUST_CONFIGURE_MATHJAX'];
			}
		}

		$new_config = array(
			array('mathjax_uri', $mathjax_uri),
			array('mathjax_use_cdn', $mathjax_use_cdn),
		);

		($action == 'install') ? $umil->config_add($new_config) : $umil->config_update($new_config);
		
		if(isset($error))
		{
			return array('command' => 'UMIL_CONFIG_ADD', 'result' => "ERROR: $error");
		}
		
		return 'UMIL_CONFIG_ADD';
	}
	else if ($action = 'uninstall')
	{
		$umil->config_remove(array(
			'mathjax_uri',
			'mathjax_use_cdn',
		));

		return 'UMIL_CONFIG_REMOVE';
	}
}

/**
 * Adds the latex bbcode if the user choose so and is not uninstalling
 *
 * @param string $action The action (install|update|uninstall) will be sent through this.
 * @param string $version The version this is being run for will be sent through this.
 */
function add_latex_bbcode($action, $version)
{
	global $user;

	$bbcode = array(
		'bbcode_tag'			=> 'latex',
		'math_type'				=> 'math/tex',
		'display_on_posting'	=> true,
		'bbcode_helpline'		=> '[latex]\\sqrt{4} = 2[/latex]',
		'mathjax_preview'		=> '[math]',
	);

	if ($action != 'uninstall' && request_var('add_latex_bbcode', false))
	{
		$error  = array();
		$command = sprintf($user->lang['UMIL_BBCODE_ADD'], $bbcode['bbcode_tag']);

		create_bbcode($bbcode, $error);

		if(sizeof($error))
		{
			return array('command' => $command, 'result' => "ERROR: $error[0]");
		}
		return $command;
	}
	return sprintf($user->lang['UMIL_BBCODE_IGNORE'], $bbcode['bbcode_tag']);
}

/**
 * Adds the latex bbcode if the user choose so and is not uninstalling
 * 
 * @param string $action The action (install|update|uninstall) will be sent through this.
 * @param string $version The version this is being run for will be sent through this.
 */
function add_mml_bbcode($action, $version)
{
	global $user;
	
	$bbcode = array(
		'bbcode_tag'			=> 'math',
		'math_type'				=> 'math/mml',
		'display_on_posting'	=> false,
		'bbcode_helpline'		=> '',
		'mathjax_preview'		=> '[math]',
	);
		
	if ($action != 'uninstall' && request_var('add_latex_bbcode', false))
	{
		$error  = array();
		$command = sprintf($user->lang['UMIL_BBCODE_ADD'], $bbcode['bbcode_tag']);

		create_bbcode($bbcode, $error);

		if(sizeof($error))
		{
			return array('command' => $command, 'result' => "ERROR: $error[0]");
		}
		return $command;
	}
	return sprintf($user->lang['UMIL_BBCODE_IGNORE'], $bbcode['bbcode_tag']);
}

?>