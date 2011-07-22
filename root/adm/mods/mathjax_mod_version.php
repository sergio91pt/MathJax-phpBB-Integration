<?php
/**
*
* @package acp
* @version $Id$
* @copyright (c) 2011 Sérgio Faria
* @license http://opensource.org/licenses/gpl-license.php GNU Public License v2
*
*/

/**
* @package mod_version_check
*/
class mathjax_mod_version
{
	function version()
	{
		return array(
			'author'	=> 'sergio91pt',
			'title'		=> 'MathJax phpBB Integration',
			'tag'		=> 'mathjax_mod',
			'version'	=> '0.2.0',
			'file'		=> array('raw.github.com', 'sergio91pt', 'MathJax-phpBB-Integration', 'master', 'contrib', 'version_check.xml'),
		);
	}
}

?>
