<?php
/**
 *
 * @author sergio91pt (Sérgio Faria) sergio91pt@gmail.com
 * @version $Id$
 * @copyright (c) 2011 Sérgio Faria
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

$filename = 'mathjax_install';

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
	
	'add_latex_bbcode'	=> array('lang' => 'ADD_LATEX_BBCODE',	'type' => 'radio:yes_no',	'explain' => true, 'default' => true),
	'add_mml_bbcode'	=> array('lang' => 'ADD_MML_BBCODE',	'type' => 'radio:yes_no',	'explain' => true, 'default' => true),

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
	'0.2.0' => array(
		'custom'	=> array(
			'math_bbcodes',
			'configure_mathjax',
		),

		'config_add' => array(
			array('mathjax_enable', true),
			array('mathjax_dynamic_load', true),
			array('mathjax_cdn', 'http://cdn.mathjax.org/mathjax/latest'),
			array('mathjax_cdn_ssl', 'https://d3eoax9i5htok0.cloudfront.net/mathjax/latest'),
			array('mathjax_cdn_force_ssl', false),
			array('mathjax_config', 'TeX-AMS-MML_HTMLorMML'),
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
		$has_path = validate_mathjax_path($mathjax_uri);

		if (!$mathjax_use_cdn && !$has_path)
		{
			trigger_error($user->lang['MUST_CONFIGURE_MATHJAX'] . gen_back_link(), E_USER_WARNING);
		}
		else if (!$has_path)
		{
			$mathjax_uri = '';
		} 

		$new_config = array(
			array('mathjax_uri', $mathjax_uri),
			array('mathjax_use_cdn', $mathjax_use_cdn),
		);

		($action == 'install') ? $umil->config_add($new_config) : $umil->config_update($new_config);
		
		// Add the bbcodes if requested
		if (request_var('add_latex_bbcode', false))
		{
			$template = generate_bbcode_template(array(
				'bbcode_tag'			=> 'latex',
				'math_type'				=> 'math/tex',
				'display_on_posting'	=> true,
				'bbcode_helpline'		=> '[latex]\\sqrt{4} = 2[/latex]',
				'mathjax_preview'		=> '[math]',
			));
			
			add_bbcode($template);
		}
		
		if (request_var('add_mml_bbcode', false))
		{
			$template = generate_bbcode_template(array(
				'bbcode_tag'			=> 'math',
				'math_type'				=> 'math/mml',
				'display_on_posting'	=> false,
				'bbcode_helpline'		=> '',
				'mathjax_preview'		=> '[math]',
			));
			
			add_bbcode($template);
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
 * Tests if MathJax.js is present at a given relative path that can escape the phpbb root dir.
 * This function triggers errors if the given path isn't a directory.
 * Based on validate_config_vars (adm/index.php)
 * 
 * @param path string Parameter by reference: the path to check.
 * @returns boolean True if MathJax.js exists in path, otherwise false.
 */
function validate_mathjax_path(&$path)
{
	global $phpbb_root_path, $user;
	
	$path = trim($path);

	// Make sure no NUL byte is present...
	if (strpos($path, "\0") !== false || strpos($path, '%00') !== false)
	{
		$path = '';
	}
	
	if (!file_exists($phpbb_root_path . $path))
	{
		trigger_error(sprintf($user->lang['DIRECTORY_DOES_NOT_EXIST'], $path) . gen_back_link(), E_USER_WARNING);
	}

	if (file_exists($phpbb_root_path . $path) && !is_dir($phpbb_root_path . $path))
	{
		trigger_error(sprintf($user->lang['DIRECTORY_NOT_DIR'], $path) . gen_back_link(), E_USER_WARNING);
	}

	return file_exists($phpbb_root_path . $path . '/MathJax.js');
		
}

/**
* Generate back link for acp pages
*/
function gen_back_link()
{
	global $user, $phpbb_root_path, $phpEx, $filename;
	
	$path = "$phpbb_root_path{$filename}.$phpEx";
	return '<br /><br /><a href="' . $path . '">&laquo; ' . $user->lang['BACK_TO_PREV'] . '</a>';
}

/**
 * Modified version of its acp_mathjax counterpart
 * Preview is mandatory and uses a config parameter.
 * @param config array Configuration array for the returned template
 * @return array A template to insert in the db.
 */
function generate_bbcode_template($bbcode)
{
	$first_pass_replace = "str_replace(array(\"\\r\\n\", '\\\"', '\\'', '(', ')'), array(\"\\n\", '\"', '&#39;', '&#40;', '&#41;'), trim('\${1}'))";

	$tag = $bbcode['bbcode_tag'];
	$type = $bbcode['math_type'];
	$display_on_post = $bbcode['display_on_posting'];
	$helpline = $bbcode['bbcode_helpline'];
	$preview = $bbcode['mathjax_preview'];

	$template = array(
		'bbcode_tag'			=> $tag,
		'bbcode_helpline'		=> $helpline,
		'display_on_posting'	=> $display_on_post,
		'bbcode_match'			=> '[' . $tag . ']{TEXT}[/' . $tag . ']',
		'bbcode_tpl'			=> '<span class="MathJaxBB"><span class="MathJax_Preview">' . $preview . '</span><span class="' . $type .'" style="visibility: hidden;">{TEXT}</span></span>',
		'first_pass_match' 		=> '!\[' . $tag . '\](.*?)\[/' . $tag . '\]!ies',
		'first_pass_replace' 	=> '\'[' . $tag . ':$uid]\'.' . $first_pass_replace . '.\'[/' . $tag . ':$uid]\'',
		'second_pass_match' 	=> '!\[' . $tag . ':$uid\](.*?)\[/' . $tag . ':$uid\]!s',
		'second_pass_replace' 	=> '<span class="MathJaxBB"><span class="MathJax_Preview">' . $preview . '</span><span class="' . $type . '" style="visibility: hidden;">${1}</span></span>',
		'is_math'				=> true,
	);
	return $template;
}

/**
 * Adds the bbcode with the given template if it doesn't yet exists.
 * @param array template The bbcode template generated by generate_bbcode_template()
 */
function add_bbcode($template)
{
	global $db, $cache;
	
	if (!bbcode_exists($template['bbcode_tag']))
	{
		if(($bbcode_id = get_free_bbcode_id()) !== false)
		{
			$sql_ary = array_merge(array('bbcode_id' => $bbcode_id), $template);

			$db->sql_query('INSERT INTO ' . BBCODES_TABLE . $db->sql_build_array('INSERT', $sql_ary));
			$cache->destroy('sql', BBCODES_TABLE);
		}
	}
	
}

/**
 * Checks if a valid lowercase bbcode tag exists in the BBCodes table
 * @return boolean True iff it exists, otherwise false.
 */
function bbcode_exists($tag)
{
	global $db;

	$sql = 'SELECT 1 as test 
		FROM ' . BBCODES_TABLE . " 
		WHERE LOWER(bbcode_tag) = '" . $db->sql_escape($tag) . "'";
	$result = $db->sql_query($sql);
	$info = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	return ($info['test'] === '1') ? true : false;
}

/**
 * Gets a free bbcode id in the BBCodes table
 * @return mixed A free id (int) in the BBCodes table or false if trigger_error() was called
 */
function get_free_bbcode_id()
{
	global $db, $user;
	
	$sql = 'SELECT MAX(bbcode_id) as max_bbcode_id
		FROM ' . BBCODES_TABLE;
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if ($row)
	{
		$bbcode_id = $row['max_bbcode_id'] + 1;

		// Make sure it is greater than the core bbcode ids...
		if ($bbcode_id <= NUM_CORE_BBCODES)
		{
			$bbcode_id = NUM_CORE_BBCODES + 1;
		}
	}
	else
	{
		$bbcode_id = NUM_CORE_BBCODES + 1;
	}

	if ($bbcode_id > BBCODE_LIMIT)
	{
		trigger_error($user->lang['TOO_MANY_BBCODES'] . gen_back_link(), E_USER_WARNING);
		return false;
	}
	return $bbcode_id;
}
?>