<?php
/**
 * WoWRoster.net WoWRoster
 *
 * Special configuration functions for RosterCP
 *
 *
 * @copyright  2002-2011 WoWRoster.net
 * @license    http://www.gnu.org/licenses/gpl.html   Licensed under the GNU General Public License v3.
 * @package    WoWRoster
 * @subpackage RosterCP
*/

if( !defined('IN_ROSTER') || !defined('IN_ROSTER_ADMIN') )
{
	exit('Detected invalid access to this file!');
}

/**
 * Value function for the language picker
 */
function rosterLangValue( $values )
{
	global $roster;

	$input_field = '<select name="config_' . $values['name'] . '">' . "\n";
	$not_selected = TRUE;
	foreach( $roster->multilanguages as $value )
	{
		if( $value == $values['value'] && $not_selected )
		{
			$input_field .= '  <option value="' . $value . '" selected="selected">' . $value . '</option>' . "\n";
			$not_selected = FALSE;
		}
		else
		{
			$input_field .= '  <option value="' . $value . '">' . $value . '</option>' . "\n";
		}
	}
	$input_field .= '</select>';

	return $input_field;
}

/**
 * Value function to select starting page
 */
function pageNames( $values )
{
	global $roster;

	$input_field = '<select name="config_' . $values['name'] . '">' . "\n";
	$not_selected = TRUE;

	// --[ Fetch button list from DB ]--
	$query = "SELECT `mb`.*, `a`.`basename`
		FROM `" . $roster->db->table('menu_button') . "` AS mb
		LEFT JOIN `" . $roster->db->table('addon') . "` AS a
		ON `mb`.`addon_id` = `a`.`addon_id`
		WHERE `scope` IN ('util','realm','guild')
		ORDER BY `mb`.`scope` , `mb`.`title` ASC;";

	$result = $roster->db->query($query);
	if( !$result )
	{
		die_quietly('Could not fetch menu buttons from table');
	}
	while( $row = $roster->db->fetch($result) )
	{
		if( $row['addon_id'] != '0' && !isset($roster->locale->act[$row['title']]) )
		{
			// Include addon's locale files if they exist
			foreach( $roster->multilanguages as $lang )
			{
				$roster->locale->add_locale_file(ROSTER_ADDONS . $row['basename'] . DIR_SEP . 'locale' . DIR_SEP . $lang . '.php',$lang);
			}
		}

		list($title) = explode('|',isset($roster->locale->act[$row['title']]) ? $roster->locale->act[$row['title']] : $row['title']);

		$title = $roster->locale->act[$row['scope']] . ' - ' . $title;

		if( $row['addon_id'] != 0 )
		{
			$row['url'] = $row['scope'] . '-' . $row['basename'] . (empty($row['url']) ? '' : '-' . $row['url']);
		}

		if( $row['url'] == $roster->config['default_page'] && $not_selected )
		{
			$input_field .= '  <option value="' . $row['url'] . '" selected="selected">' . $title . '</option>' . "\n";
			$not_selected = FALSE;
		}
		else
		{
			$input_field .= '  <option value="' . $row['url'] . '">' . $title . '</option>' . "\n";
		}
	}

	$input_field .= '</select>';

	$roster->db->free_result($result);

	return $input_field;
}

/**
 * Get a list of font from the font directory
 *
 * @return array | $file => $name
 */
function fontFiles( $values )
{
	static $arrFiles = array();

	if( count($arrFiles) == 0 )
	{
		// Open the directory
		$tmp_dir = @opendir( ROSTER_BASE . 'fonts' );

		if( !empty($tmp_dir) )
		{
			// Read the files
			while( $file = readdir($tmp_dir) )
			{
				$pfad_info = pathinfo($file);

				if( strtolower($pfad_info['extension']) == strtolower('ttf') )
				{
					$name = str_replace('.' . $pfad_info['extension'],'',$file);
					$arrFiles += array($file => $name);
				}
			}
			// close the directory
			closedir($tmp_dir);

			//sort the list
			asort($arrFiles);
		}
	}

	$input_field = '<select name="config_' . $values['name'] . '">' . "\n";
	$not_selected = TRUE;
	foreach( $arrFiles as $file=>$name )
	{
		if( $file == $values['value'] && $not_selected )
		{
			$input_field .= '  <option value="' . $file . '" selected="selected">' . $name . '</option>' . "\n";
			$not_selected = FALSE;
		}
		else
		{
			$input_field .= '  <option value="' . $file . '">' . $name . '</option>' . "\n";
		}
	}
	$input_field .= '</select>';

	return $input_field;
}

/**
 * Get a list of themes from the templates directory
 *
 * @return array | $file => $name
 */
function templateList( $values )
{
	static $arrFiles = array();

	if( count($arrFiles) == 0 )
	{
		// Open the directory
		$tmp_dir = @opendir( ROSTER_TPLDIR );

		if( !empty($tmp_dir) )
		{
			// Read the files
			while( $file = readdir($tmp_dir) )
			{
				if( is_dir(ROSTER_TPLDIR . $file) && $file != '.' && $file != '..' && $file != '.svn' && $file != 'install' )
				{
					$arrFiles[] = ($file);
				}
			}
			// close the directory
			closedir($tmp_dir);

			//sort the list
			asort($arrFiles);
		}
	}

	$input_field = '<select name="config_' . $values['name'] . '">' . "\n";
	$not_selected = TRUE;
	foreach( $arrFiles as $file )
	{
		if( $file == $values['value'] && $not_selected )
		{
			$input_field .= '  <option value="' . $file . '" selected="selected">' . ucfirst($file) . '</option>' . "\n";
			$not_selected = FALSE;
		}
		else
		{
			$input_field .= '  <option value="' . $file . '">' . ucfirst($file) . '</option>' . "\n";
		}
	}
	$input_field .= '</select>';

	return $input_field;
}

/**
 * Get a list of bootstrap themes from the current templates directory
 *
 * @return array | $file => $name
 */
function ThemeList( $values )
{
	global $roster;
	static $arrFiles = array();

	if( count($arrFiles) == 0 )
	{
		// Open the directory
		$bdir = ROSTER_TPLDIR . $roster->config['theme'] . DIR_SEP . 'style' . DIR_SEP . 'bootstrap' . DIR_SEP;
		$tmp_dir = @opendir( $bdir );
		if( !empty($tmp_dir) )
		{
			// Read the files
			while( $file = readdir($tmp_dir) )
			{
				if( is_dir($bdir . $file) && $file != '.' && $file != '..' && $file != '.svn' && $file != 'install' && $file != 'fonts')
				{
					$arrFiles[] = ($file);
				}
			}
			// close the directory
			closedir($tmp_dir);
			//sort the list
			asort($arrFiles);
		}
	}

	$input_field = '<select name="config_' . $values['name'] . '">' . "\n";
	$not_selected = TRUE;
	foreach( $arrFiles as $file )
	{
		if( $file == $values['value'] && $not_selected )
		{
			$input_field .= '  <option value="' . $file . '" selected="selected">' . ucfirst($file) . '</option>' . "\n";
			$not_selected = FALSE;
		}
		else
		{
			$input_field .= '  <option value="' . $file . '">' . ucfirst($file) . '</option>' . "\n";
		}
	}
	$input_field .= '</select>';

	return $input_field;
}


/**
 * Get a list of addon based auth files
 *
 * @return array | $file => $name
 */
function externalAuth( $values )
{
	global $roster;

	$input_field = '<select name="config_' . $values['name'] . '">' . "\n";

	if( 'roster' == $roster->config['external_auth'] )
	{
		$input_field .= '  <option value="roster" selected="selected">WoWRoster</option>' . "\n";
		$not_selected = FALSE;
	}
	else
	{
		$input_field .= '  <option value="roster">WoWRoster</option>' . "\n";
	}

	$not_selected = TRUE;
	foreach( $roster->addon_data as $addon_data )
	{
		if( file_exists(ROSTER_ADDONS . $addon_data['basename'] . DIR_SEP . 'inc' . DIR_SEP . 'login.php') )
		{
			// Include addon's locale files if they exist
			foreach( $roster->multilanguages as $lang )
			{
				$roster->locale->add_locale_file(ROSTER_ADDONS . $addon_data['basename'] . DIR_SEP . 'locale' . DIR_SEP . $lang . '.php',$lang);
			}

			list($title) = explode('|',isset($roster->locale->act[$addon_data['fullname']]) ? $roster->locale->act[$addon_data['fullname']] : $addon_data['fullname']);

			if( $addon_data['basename'] == $roster->config['external_auth'] && $not_selected )
			{
				$input_field .= '  <option value="' . $addon_data['basename'] . '" selected="selected">' . $title . '</option>' . "\n";
				$not_selected = FALSE;
			}
			else
			{
				$input_field .= '  <option value="' . $addon_data['basename'] . '">' . $title . '</option>' ."\n";
			}
		}
	}
	$input_field .= '</select>';

	return $input_field;
}

/**
 * Get a list of addon based auth files
 *
 * @return array | $file => $name
 */
function defaultgroup( $values )
{
	global $roster;

	$input_field = '<select name="config_' . $values['name'] . '">' . "\n";

	foreach ($roster->auth->groups as $id => $data)
	{
		$input_field .= '<option value="'. $id .'" '. ($values['value']==$id ? 'selected' : '') .'>'. $data['group_name'] ."</option>\n";
	}
	$input_field .= '</select>';

	return $input_field;
}

