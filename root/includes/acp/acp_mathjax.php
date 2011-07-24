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
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* @package acp
*/
class acp_mathjax
{
	var $u_action;

	function main($id, $mode)
	{
		global $db, $user, $auth, $template;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;

		// Set up general vars
		$action	= request_var('action', '');
        $submit = isset($_POST['submit']) ? true : false;
		
		$user->add_lang('mods/info_acp_mathjax');
		$this->tpl_name = 'acp_mathjax';
		
		$form_key = 'acp_mathjax';
		add_form_key($form_key);

		switch ($mode) {
			case 'settings':
				$display_vars = array(
					'title'	=> 'MATHJAX_SETTINGS',
					'vars'	=> array(
						'legend1'				=> 'GENERAL_SETTINGS',
						'mathjax_enable'		=> array('lang' => 'MATHJAX_ENABLE',		'validate' => 'bool',	'type' => 'radio:enabled_disabled',	'explain' => false),
						'mathjax_dynamic_load'	=> array('lang' => 'MATHJAX_DYNAMIC_LOAD',	'validate' => 'bool',	'type' => 'radio:yes_no',			'explain' => true),
						'mathjax_use_cdn'		=> array('lang' => 'MATHJAX_USE_CDN',		'validate' => 'bool',	'type' => 'radio:yes_no',			'explain' => true),
						'mathjax_cdn_force_ssl' => array('lang' => 'MATHJAX_CDN_FORCE_SSL',	'validate' => 'bool',	'type' => 'radio:yes_no',			'explain' => true),
						'mathjax_uri'			=> array('lang' => 'MATHJAX_URI',			'validate' => 'path',	'type' => 'text:20:255',			'explain' => true),
						'mathjax_config'		=> array('lang' => 'MATHJAX_CONFIG',		'validate' => 'string',	'type' => 'text:20:255',			'explain' => true),	
												
						'legend2'				=> 'ACP_SUBMIT_CHANGES',
					),
				);
			break;

			case 'bbcode':
				$this->hard_coded_bbcodes = array('code', 'quote', 'attachment', 'b', 'i', 'url', 'img', 'size', 'color', 'u', 'list', 'email', 'flash');
				$this->math_types = array(
					'math/tex' => 'MATH_TYPE_TEX', 
					'math/mml' => 'MATH_TYPE_MML',
				);
				
				$display_vars = array(
					'title'	=> 'MATHJAX_BBCODE',
					'vars'	=> array(),
				);

				switch($action)
				{
					case 'add':	// Dummy case for the submit button on list
						$action = 'create';
						$submit = false;
					// No break
					
					case 'modify':
					case 'create':
						$display_vars['vars'] = array_merge($display_vars['vars'], array(
							'legend1'				=> 'BBCODE_EDITOR',
							'bbcode_tag'			=> array('lang' => 'MATHJAX_BBCODE_TAG',		'validate' => 'string:1:16',	'type' => 'text:20:16',			'explain' => false),
							'math_type'				=> array('lang' => 'MATHJAX_BBCODE_TYPE', 		'type' => 'custom',				'method' => 'build_math_type', 	'explain' => false),
							'display_on_posting'	=> array('lang' => 'MATHJAX_BBCODE_DISPLAY',	'validate' => 'bool',			'type' => 'radio:yes_no',		'explain' => true),
							'bbcode_helpline'		=> array('lang' => 'MATHJAX_BBCODE_HELPLINE',	'validate' => 'string',			'type' => 'text:20:255', 		'explain' => true),
							'mathjax_preview'		=> array('lang' => 'MATHJAX_PREVIEW',			'validate' => 'string', 		'type' => 'text:20:255',		'explain' => true),
	
							'legend2'				=> 'ACP_SUBMIT_CHANGES',
						));
					break;

					case 'delete':
					break;

					case 'list':
					default: // List bbcodes
						$action = 'list';
						$template->assign_var('S_LIST_BBCODE', true);
						
						$sql = 'SELECT bbcode_id, bbcode_tag
							FROM ' . BBCODES_TABLE . ' 
							WHERE is_math = 1';
						$result = $db->sql_query($sql);

						while($row = $db->sql_fetchrow($result))
						{
							$template->assign_block_vars('bbcodes', array(
								'BBCODE_TAG'	=> $row['bbcode_tag'],
								'U_EDIT' 		=> $this->u_action . '&amp;action=modify&amp;bbcode_id=' . $row['bbcode_id'],
								'U_DELETE' 		=> $this->u_action . '&amp;action=delete&amp;bbcode_id=' . $row['bbcode_id'],
							));
						}
						$db->sql_freeresult($result);
					break;
				}
			break;

			default:
				trigger_error('NO_MODE', E_USER_ERROR);
			break;
		}

		if (isset($display_vars['lang']))
		{
			$user->add_lang($display_vars['lang']);
		}
		
		// Overrride $this->new_config if the bbcode exists
		if ($mode == 'bbcode' && ($action == 'modify' || $action =='delete'))
		{
			if (($bbcode_id = (int) request_var('bbcode_id', -1)) == -1)
			{
				trigger_error('NO_BBCODE_ID', E_USER_WARNING);
			}
			
			$sql = 'SELECT *
				FROM ' . BBCODES_TABLE . "
				WHERE bbcode_id = $bbcode_id";
			$result = $db->sql_query($sql);
			
			$this->new_config = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			
			// But before lets test if bbcode_id exists and its ours
			if (empty($this->new_config['is_math']))
			{
				trigger_error('BBCODE_DOESNT_EXIST', E_USER_WARNING);
			}

			// Lets infer the BBCode type and math preview from the tpl (but only on modify - theres no need in delete)
			if ($action == 'modify')
			{
				if (!preg_match('/(?:\<span class="MathJax_Preview"\>(.*?)\<\/span\>)?\<span class="(math\/[a-z]+)"(?:.+?(visibility: hidden))?/', $this->new_config['bbcode_tpl'], $result))
				{
					trigger_error('BBCODE_NOT_MATH_TPL', E_USER_WARNING);
				}
				// $result[0] = Whole matched text	$result[1] = Preview text?      (might be empty)   
				// $result[2] = math type			$result[3] = visibility hidden? (might not be set)
				$this->new_config['math_type'] = $result[2];

				// Check if {NONE} was used
				$this->new_config['mathjax_preview'] = (empty($result[1]) && !empty($result[3])) ? '{NONE}' : $result[1];
			}
		}
		else if ($mode == 'bbcode')
		{
			$this->new_config = array();
		}
		else
		{
			$this->new_config = $config;
		}
		
		$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
		$error = array();

		// We validate the complete config if whished
		validate_config_vars($display_vars['vars'], $cfg_array, $error);
		
		if (isset($cfg_array['math_type']))
		{
			$this->validate_math_type($cfg_array['math_type']);
		}
		
		if ($submit && !check_form_key($form_key))
		{
			$error[] = $user->lang['FORM_INVALID'];
		}
		
		// Do not write values if there is an error
		if (sizeof($error))
		{
			$submit = false;
		}
		
		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
		foreach ($display_vars['vars'] as $config_name => $null)
		{
			if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
			{
				continue;
			}
			$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];

			if ($submit && $mode != 'bbcode')
			{
				set_config($config_name, $config_value);
			}
		}

		// Do some more work here for bbcodes
		if ($mode == 'bbcode')
		{
			if ($action == 'delete')
			{
				if (confirm_box(true))
				{
					$this->remove_bbcode($bbcode_id);
					add_log('admin', 'LOG_BBCODE_DELETE', $this->new_config['bbcode_tag']);
					trigger_error($user->lang['BBCODE_DELETED'] . adm_back_link($this->u_action));
				}
				else
				{
					$msg = sprintf($user->lang['BBCODE_DELETE_CONFIRM'], $this->new_config['bbcode_tag']);
					confirm_box(false, $msg, build_hidden_fields(array(
						'bbcode_id'	=> $bbcode_id,
						'mode'		=> $mode,
						'action'	=> $action,
					)));
				}
			}
			else if ($submit)
			{
				if ($action == 'modify')
				{
					$this->modify_bbcode($error);
					$log_action = 'LOG_BBCODE_EDIT';
					$notice_msg = 'BBCODE_MODIFIED';
				} 
				else if ($action == 'create')
				{
					$this->create_bbcode($error);
					$log_action = 'LOG_BBCODE_ADD';
					$notice_msg = 'BBCODE_CREATED';
				}
			}
		}
		
		if (sizeof($error))
		{
			$submit = false;
		}

		if ($submit && !($mode == 'bbcode' && $action == 'list'))
		{
			if ($mode == 'bbcode')
			{
				add_log('admin', $log_action, $this->new_config['bbcode_tag']);
				trigger_error($user->lang[$notice_msg] . adm_back_link($this->u_action));
			}
			else
			{
				add_log('admin', 'LOG_CONFIG_MATHJAX');
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}
		}

		$this->page_title = $display_vars['title'];
		$template->assign_vars(array(
			'L_TITLE'			=> $user->lang[$display_vars['title']],
			'L_TITLE_EXPLAIN'	=> $user->lang[$display_vars['title'] . '_EXPLAIN'],

			'S_ERROR'			=> (sizeof($error)) ? true : false,
			'ERROR_MSG'			=> implode('<br />', $error),
		));
		
		// Assign U_ACTION
		if($mode == 'bbcode')
		{
			switch ($action)
			{
				case 'list':
					$template->assign_var('U_ACTION', $this->u_action . '&amp;action=add');
				break;
				
				case 'create':
					$template->assign_var('U_ACTION', $this->u_action . '&amp;action=create');
				break;
				
				case 'modify':
					$template->assign_var('U_ACTION', $this->u_action . '&amp;action=modify&amp;bbcode_id=' . $bbcode_id);
				break;
				
				default:
					$template->assign_var('U_ACTION', $this->u_action);
				break;
			}
		}
		else
		{
			$template->assign_var('U_ACTION', $this->u_action);
		} 
		
		// Output relevant page set in $display_vars
		foreach ($display_vars['vars'] as $config_key => $vars)
		{
			if (!is_array($vars) && strpos($config_key, 'legend') === false)
			{
				continue;
			}

			if (strpos($config_key, 'legend') !== false)
			{
				$template->assign_block_vars('options', array(
					'S_LEGEND'		=> true,
					'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars
				));

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && isset($vars['lang_explain']))
			{
				$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
			}
			else if ($vars['explain'])
			{
				$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
			}

			$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);

			if (empty($content))
			{
				continue;
			}

			$template->assign_block_vars('options', array(
				'KEY'			=> $config_key,
				'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				'CONTENT'		=> $content,
				)
			);

			unset($display_vars['vars'][$config_key]);
		}
	}

	// BBCode Functions
	// Note: Basic error checking is done by validate_config_vars() on $this->main()
	
	function build_math_type()
	{
		global $user;
		
		$selected = (!empty($this->new_config['math_type'])) ? $this->new_config['math_type'] : 'math/tex';
		$html = '<select id="math_type" name="config[math_type]">';

		foreach ($this->math_types as $type => $lang)
		{
			$select = ($selected == $type) ? ' selected="selected"' : '';
			$html .= '<option value="' . $type . '"' . $select . '>' . $user->lang[$lang] . '</option>';
		}
		
		return $html . '</select>';
	}
	
	function validate_math_type($type)
	{
		if (!array_key_exists($type, $this->math_types))
		{
			trigger_error('Invalid math type', E_USER_ERROR);
		}
	}

	function create_bbcode(&$error)
	{
		global $db, $cache, $user;
		
		$tag = $this->new_config['bbcode_tag'];

		// Lets be nice and allow [AwS-0Me_Tag2] and no_brackets_lazy_tag
		if(!preg_match('/^\[[a-zA-Z0-9_-]+\]$|^[a-zA-Z0-9_-]+$/', $tag)) 
		{
			$error[] = sprintf($user->lang['ERROR_BBCODE_INVALID'], $tag);
			return;
		}

		$tag = $this->new_config['bbcode_tag'] = strtolower(trim($tag, '[]'));

		if(in_array($tag, $this->hard_coded_bbcodes) || $this->bbcode_exists($tag))
		{
			$error[] = sprintf($user->lang['ERROR_BBCODE_EXISTS'], $tag);
		}
		else if(($bbcode_id = $this->get_free_bbcode_id()) !== false)
		{
			$sql_ary = array_merge(
				array('bbcode_id' => $bbcode_id),
				$this->generate_bbcode_template()
			);

			$db->sql_query('INSERT INTO ' . BBCODES_TABLE . $db->sql_build_array('INSERT', $sql_ary));
			$cache->destroy('sql', BBCODES_TABLE);
		}
	}

	function modify_bbcode(&$error)
	{
		global $db, $cache, $user;

		$bbcode_id = $this->new_config['bbcode_id'];
		$tag = $this->new_config['bbcode_tag'];
		
		// Lets be nice and allow [AwS-0Me_Tag2] and no_brackets_lazy_tag
		if(!preg_match('/^\[[a-zA-Z0-9_-]+\]$|^[a-zA-Z0-9_-]+$/', $tag)) 
		{
			$error[] = sprintf($user->lang['ERROR_BBCODE_INVALID'], $tag);
			return;
		}

		$tag = $this->new_config['bbcode_tag'] = strtolower(trim($tag, '[]'));

		if(in_array($tag, $this->hard_coded_bbcodes))
		{
			$error[] = sprintf($user->lang['ERROR_BBCODE_EXISTS'], $tag);
			return;
		}

		$template = $this->generate_bbcode_template();

		$sql = 'UPDATE ' . BBCODES_TABLE . ' 
			SET ' . $db->sql_build_array('UPDATE', $template) . " 
			WHERE bbcode_id = $bbcode_id";
		$db->sql_query($sql);
		$cache->destroy('sql', BBCODES_TABLE);
	}

	function remove_bbcode($bbcode_id)
	{
		global $db, $cache;

		$sql = 'DELETE 
			FROM ' . BBCODES_TABLE . " 
			WHERE bbcode_id = $bbcode_id";
		$db->sql_query($sql);
		$cache->destroy('sql', BBCODES_TABLE);
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
			trigger_error($user->lang['TOO_MANY_BBCODES'] . adm_back_link($this->u_action), E_USER_WARNING);
			return false;
		}
		return $bbcode_id;
	}

	function generate_bbcode_template()
	{
		$first_pass_replace = "str_replace(array(\"\\r\\n\", '\\\"', '\\'', '(', ')'), array(\"\\n\", '\"', '&#39;', '&#40;', '&#41;'), trim('\${1}'))";

		$tag = $this->new_config['bbcode_tag'];
		$type = $this->new_config['math_type'];
		$display_on_post = $this->new_config['display_on_posting'];
		$helpline = $this->new_config['bbcode_helpline'];

		if (!empty($this->new_config['mathjax_preview']))
		{
			$preview = $this->new_config['mathjax_preview'];
			$preview = ($preview == '{NONE}') ? '' : '<span class="MathJax_Preview">' . $preview . '</span>';  
			$style = ' style="visibility: hidden;"';
		}
		else
		{
			$preview = '';
			$style = '';
		}

		$template = array(
			'bbcode_tag'			=> $tag,
			'bbcode_helpline'		=> $helpline,
			'display_on_posting'	=> $display_on_post,
			'bbcode_match'			=> '[' . $tag . ']{TEXT}[/' . $tag . ']',
			'bbcode_tpl'			=> '<span class="MathJaxBB">' . $preview . '<span class="' . $type .'"' . $style . '>{TEXT}</span></span>',
			'first_pass_match' 		=> '!\[' . $tag . '\](.*?)\[/' . $tag . '\]!ies',
			'first_pass_replace' 	=> '\'[' . $tag . ':$uid]\'.' . $first_pass_replace . '.\'[/' . $tag . ':$uid]\'',
			'second_pass_match' 	=> '!\[' . $tag . ':$uid\](.*?)\[/' . $tag . ':$uid\]!s',
			'second_pass_replace' 	=> '<span class="MathJaxBB">' . $preview . '<span class="' . $type . '"' . $style . '>${1}</span></span>',
			'is_math'				=> true,
		);
		return $template;
	}

}

?>