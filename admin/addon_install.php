<?php
/**
 * WoWRoster.net WoWRoster
 *
 * AddOn installer
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

$roster->output['title'] .= $roster->locale->act['pagebar_addoninst'];


include(ROSTER_ADMIN . 'roster_config_functions.php');

include(ROSTER_LIB . 'install.lib.php');
$installer = new Install;


$op = ( isset($_POST['op']) ? $_POST['op'] : '' );

$id = ( isset($_POST['id']) ? $_POST['id'] : '' );

switch( $op )
{
	case 'deactivate':
		processActive($id,0);
		break;

	case 'activate':
		processActive($id,1);
		break;

	case 'process':
		$processed = processAddon();
		break;

	case 'default_page':
		processPage();
		break;

	case 'access':
		processAccess();
		break;

	default:
		break;
}
// This is here to refresh the addon list
$roster->get_addon_data();

$l_default_page = explode('|',$roster->locale->act['admin']['default_page']);

$roster->tpl->assign_vars(array(
	'S_ADDON_LIST' => false,

	'L_DEFAULT_PAGE' => $l_default_page[0],
	'L_DEFAULT_PAGE_HELP' => makeOverlib($l_default_page[1],$l_default_page[0],'',0,'',',WRAP'),

	'S_DEFAULT_SELECT' => pageNames(array('name'=>'default_page')),
	)
);

$addons = getAddonList();
$install = $uninstall = $active = $deactive = $upgrade = $purge = 0;
if( !empty($addons) )
{
	$roster->tpl->assign_vars(array(
		'S_ADDON_LIST' => true,

		'L_TIP_STATUS_ACTIVE' => makeOverlib($roster->locale->act['installer_turn_off'],$roster->locale->act['installer_activated']),
		'L_TIP_STATUS_INACTIVE' => makeOverlib($roster->locale->act['installer_turn_on'],$roster->locale->act['installer_deactivated']),
		'L_TIP_INSTALL_OLD' => makeOverlib($roster->locale->act['installer_replace_files'],$roster->locale->act['installer_overwrite']),
		'L_TIP_INSTALL' => makeOverlib($roster->locale->act['installer_click_uninstall'],$roster->locale->act['installer_installed']),
		'L_TIP_UNINSTALL' => makeOverlib($roster->locale->act['installer_click_install'],$roster->locale->act['installer_not_installed']),
		)
	);

	$clist = array(
		'3' => 'install',
		'0' => 'active',
		'2' => 'deactive',
		'1' => 'upgrade',
		'-1' => 'purge'
		);
	foreach ($clist as $t => $ail)
	{
		$roster->tpl->assign_block_vars('ai_types', array(
					'TYPE'       => 'ai_'.$t
				)
			);
		foreach( $addons as $addon )
		{
			if ($addon['install'] == $t)
			{
				if( !empty($addon['icon']) )
				{
					if( strpos($addon['icon'],'.') !== false )
					{
						$addon['icon'] = ROSTER_PATH . 'addons/' . $addon['basename'] . '/images/' . $addon['icon'];
					}
					else
					{
						$addon['icon'] = $roster->config['interface_url'] . 'Interface/Icons/' . $addon['icon'] . '.' . $roster->config['img_suffix'];
					}
				}
				else
				{
					$addon['icon'] = $roster->config['interface_url'] . 'Interface/Icons/inv_misc_questionmark.' . $roster->config['img_suffix'];
					}

				$roster->tpl->assign_block_vars('ai_types.addon_list', array(
					'ROW_CLASS'   => $roster->switch_row_class(),
					'ID'          => ( isset($addon['id']) ? $addon['id'] : '' ),
					'ICON'        => $addon['icon'],
					'FULLNAME'    => $addon['fullname'],
					'BASENAME'    => $addon['basename'],
					'VERSION'     => $addon['version'],
					'OLD_VERSION' => ( isset($addon['oldversion']) ? $addon['oldversion'] : '' ),
					'DESCRIPTION' => $addon['description'],
					'DEPENDENCY'  => $addon['requires'],
					'AUTHOR'      => $addon['author'],
					'ACTIVE'      => ( isset($addon['active']) ? $addon['active'] : '' ),
					'INSTALL'     => $addon['install'],
					'L_TIP_UPGRADE' => ( isset($addon['active']) ? makeOverlib(sprintf($roster->locale->act['installer_click_upgrade'],$addon['oldversion'],$addon['version']),$roster->locale->act['installer_upgrade_avail']) : '' ),
					'ACCESS'      => false //( isset($addon['access']) ? $roster->auth->rosterAccess(array('name' => 'access', 'value' => $addon['access'])) : false )
					)
				);

				
				if ($addon['install'] == '3')
				{
					$install++;
				}
				if ($addon['install'] == '0')
				{
					$active++;
				}
				if ($addon['install'] == '2')
				{
					$deactive++;
				}
				if ($addon['install'] == '1')
				{
					$upgrade++;
				}
				if ($addon['install'] == '-1')
				{
					$purge++;
				}
			}
			
		}
		
	}
	$roster->tpl->assign_vars(array(
		'AL_C_PURGE' => $purge,			// -1
		'AL_C_UPGRADE' => $upgrade,		// 1
		'AL_C_DEACTIVE' => $deactive,	// 2
		'AL_C_ACTIVE' => $active,		// 0
		'AL_C_INSTALL' => $install,		// 3
	));
}
else
{
	$installer->setmessages('No addons available!');
}
/*
echo '<!-- <pre>';
print_r($addons);
echo '</pre> -->';
*/
$errorstringout = $installer->geterrors();
$messagestringout = $installer->getmessages();
$sqlstringout = $installer->getsql();

// print the error messages
if( !empty($errorstringout) )
{
	$roster->set_message($errorstringout, $roster->locale->act['installer_error'], 'error');
}

// Print the update messages
if( !empty($messagestringout) )
{
	$roster->set_message($messagestringout, $roster->locale->act['installer_log']);
}

$roster->tpl->set_filenames(array('body' => 'admin/addon_install.html'));
$body = $roster->tpl->fetch('body');

/**
som new js
**/
	$js = '
jQuery(document).ready( function($){

	// this is the id of the ul to use
	var menu;
jQuery(".tab-navigation ul li").click(function(e)
{
	e.preventDefault();
	menu = jQuery(this).parent().attr("id");
	//alert(menu);
	//jQuery("."+menu+"").css("display","none");
	jQuery(".tab-navigation ul#"+menu+" li").removeClass("selected");

	var tab_class = jQuery(this).attr("id");
	jQuery(".tab-navigation ul#"+menu+" li").each(function() {
		var v = jQuery(this).attr("id");
		console.log( "hiding - "+v );
		jQuery("div#"+v+"").hide();
	});
	//jQuery("."+menu+"#" + tab_class).siblings().hide();
	console.log( "show - "+tab_class );
	jQuery("."+menu+"#" + tab_class).show();
	jQuery(".tab-navigation ul#"+menu+" li#" + tab_class).addClass("selected");
});
function first()
{
	var tab_class = jQuery(".tab-navigation ul li").first().attr("id");
	console.log( "first - "+tab_class );
	menu = jQuery(".tab-navigation ul").attr("id");
	jQuery(".tab-navigation ul#"+menu+" li").each(function() {
		var v = jQuery(this).attr("id");
		console.log( "hiding - "+v );
		jQuery("div#"+v+"").hide();
	});
	jQuery("."+menu+"#" + tab_class).show();
	jQuery(".tab-navigation ul#"+menu+" li#" + tab_class).addClass("selected");
	
}
var init = first();

});';
roster_add_js($js, 'inline', 'header', false, false);
/**
 * Gets the list of currently installed roster addons
 *
 * @return array
 */



/**
 * Sets addon active/inactive
 *
 * @param int $id
 * @param int $mode
 */
function processActive( $id , $mode )
{
	global $roster, $installer;

	$query = "SELECT `basename` FROM `" . $roster->db->table('addon') . "` WHERE `addon_id` = " . $id . ";";
	$basename = $roster->db->query_first($query);

	$query = "UPDATE `" . $roster->db->table('addon') . "` SET `active` = '$mode' WHERE `addon_id` = '$id' LIMIT 1;";
	$result = $roster->db->query($query);
	if( !$result )
	{
		$installer->seterrors('Database Error: ' . $roster->db->error() . '<br />SQL: ' . $query);
	}
	else
	{
		$installer->setmessages(sprintf($roster->locale->act['installer_activate_' . $mode] ,$basename));
	}
}


/**
 * Addon installer/upgrader/uninstaller
 *
 */
function processAddon()
{
	global $roster, $installer;

	$addon_name = $_POST['addon'];

	if( preg_match('/[^a-zA-Z0-9_]/', $addon_name) )
	{
		$installer->seterrors($roster->locale->act['invalid_char_module'],$roster->locale->act['installer_error']);
		return;
	}

	// Check for temp tables
	//$old_error_die = $roster->db->error_die(false);
	if( false === $roster->db->query("CREATE TEMPORARY TABLE `test` (id int);") )
	{
		$installer->temp_tables = false;
		$roster->db->query("UPDATE `" . $roster->db->table('config') . "` SET `config_value` = '0' WHERE `id` = 1180;");
	}
	else
	{
		$installer->temp_tables = true;
	}
	//$roster->db->error_die($old_error_die);

	// Include addon install definitions
	$addonDir = ROSTER_ADDONS . $addon_name . DIR_SEP;
	$addon_install_file = $addonDir . 'inc' . DIR_SEP . 'install.def.php';
	$install_class = $addon_name . 'Install';

	if( !file_exists($addon_install_file) )
	{
		$installer->seterrors(sprintf($roster->locale->act['installer_no_installdef'],$addon_name),$roster->locale->act['installer_error']);
		return;
	}

	require_once($addon_install_file);

	$addon = new $install_class();
	$addata = escape_array((array)$addon);
	$addata['basename'] = $addon_name;

	if( $addata['basename'] == '' )
	{
		$installer->seterrors($roster->locale->act['installer_no_empty'],$roster->locale->act['installer_error']);
		return;
	}
	
	// Get existing addon record if available
	$query = 'SELECT * FROM `' . $roster->db->table('addon') . '` WHERE `basename` = "' . $addata['basename'] . '";';
	$result = $roster->db->query($query);
	if( !$result )
	{
		$installer->seterrors(sprintf($roster->locale->act['installer_fetch_failed'],$addata['basename']) . '.<br />MySQL said: ' . $roster->db->error(),$roster->locale->act['installer_error']);
		return;
	}
	$previous = $roster->db->fetch($result);
	$roster->db->free_result($result);

	// Give the installer the addon data
	$installer->addata = $addata;

	$success = false;


	// Save current locale array
	// Since we add all locales for localization, we save the current locale array
	// This is in case one addon has the same locale strings as another, and keeps them from overwritting one another
	$localetemp = $roster->locale->wordings;

	foreach( $roster->multilanguages as $lang )
	{
		$roster->locale->add_locale_file(ROSTER_ADDONS . $addata['basename'] . DIR_SEP . 'locale' . DIR_SEP . $lang . '.php',$lang);
	}

	// Collect data for this install type
	switch( $_POST['type'] )
	{
		case 'install':
			if( $previous )
			{
				$installer->seterrors(sprintf($roster->locale->act['installer_addon_exist'],$installer->addata['basename'],$previous['fullname']));
				break;
			}
			// check to see if any requred addons if so and not enabled disable addon after install and give a message
			if (isset($installer->addata['requires']))
			{	
				if (!active_addon($installer->addata['requires']))
				{
					$installer->addata['active'] = false;
					$installer->setmessages('Addon Dependency "'.$installer->addata['requires'].'" not active or installed, "'.$installer->addata['fullname'].'" has been disabled');
					break;
				}
			}
	
			$query = 'INSERT INTO `' . $roster->db->table('addon') . '` VALUES (NULL,"' . $installer->addata['basename'] . '","' . $installer->addata['version'] . '","' . (int)$installer->addata['active'] . '",0,"' . $installer->addata['fullname'] . '","' . $installer->addata['description'] . '","' . $roster->db->escape(serialize($installer->addata['credits'])) . '","' . $installer->addata['icon'] . '","' . $installer->addata['wrnet_id'] . '",NULL);';
			$result = $roster->db->query($query);
			if( !$result )
			{
				$installer->seterrors('DB error while creating new addon record. <br /> MySQL said:' . $roster->db->error(),$roster->locale->act['installer_error']);
				break;
			}
			$installer->addata['addon_id'] = $roster->db->insert_id();

			// We backup the addon config table to prevent damage
			$installer->add_backup($roster->db->table('addon_config'));

			$success = $addon->install();

			// Delete the addon record if there is an error
			if( !$success )
			{
				$query = 'DELETE FROM `' . $roster->db->table('addon') . "` WHERE `addon_id` = '" . $installer->addata['addon_id'] . "';";
				$result = $roster->db->query($query);
			}
			else
			{
				$installer->sql[] = 'UPDATE `' . $roster->db->table('addon') . '` SET `active` = ' . (int)$installer->addata['active'] . " WHERE `addon_id` = '" . $installer->addata['addon_id'] . "';";
				$installer->sql[] = "INSERT INTO `" . $roster->db->table('permissions') . "` VALUES ('', 'roster', '" . $installer->addata['addon_id'] . "', 'addon', '".$installer->addata['fullname']."', 'addon_access_desc' , '".$installer->addata['basename']."_access');";
			}
			break;

		case 'upgrade':
			if( !$previous )
			{
				$installer->seterrors(sprintf($roster->locale->act['installer_no_upgrade'],$installer->addata['basename']));
				break;
			}
			/* Carry Over from AP branch
			if( !in_array($previous['basename'],$addon->upgrades) )
			{
				$installer->seterrors(sprintf($roster->locale->act['installer_not_upgradable'],$addon->fullname,$previous['fullname'],$previous['basename']));
				break;
			}
			*/

			$query = "UPDATE `" . $roster->db->table('addon') . "` SET `basename`='" . $installer->addata['basename'] . "', `version`='" . $installer->addata['version'] . "', `active`=" . (int)$installer->addata['active'] . ", `fullname`='" . $installer->addata['fullname'] . "', `description`='" . $installer->addata['description'] . "', `credits`='" . serialize($installer->addata['credits']) . "', `icon`='" . $installer->addata['icon'] . "', `wrnet_id`='" . $installer->addata['wrnet_id'] . "' WHERE `addon_id`=" . $previous['addon_id'] . ';';
			$result = $roster->db->query($query);
			if( !$result )
			{
				$installer->seterrors('DB error while updating the addon record. <br /> MySQL said:' . $roster->db->error(),$roster->locale->act['installer_error']);
				break;
			}
			$installer->addata['addon_id'] = $previous['addon_id'];

			// We backup the addon config table to prevent damage
			$installer->add_backup($roster->db->table('addon_config'));

			$success = $addon->upgrade($previous['version']);
			break;

		case 'uninstall':
			if( !$previous )
			{
				$installer->seterrors(sprintf($roster->locale->act['installer_no_uninstall'],$installer->addata['basename']));
				break;
			}
			if( $previous['basename'] != $installer->addata['basename'] )
			{
				$installer->seterrors(sprintf($roster->locale->act['installer_not_uninstallable'],$installer->addata['basename'],$previous['fullname']));
				break;
			}
			$query = 'DELETE FROM `' . $roster->db->table('addon') . '` WHERE `addon_id`=' . $previous['addon_id'] . ';';
			$result = $roster->db->query($query);
			if( !$result )
			{
				$installer->seterrors('DB error while deleting the addon record. <br /> MySQL said:' . $roster->db->error(),$roster->locale->act['installer_error']);
				break;
			}
			$installer->addata['addon_id'] = $previous['addon_id'];

			// We backup the addon config table to prevent damage
			$installer->add_backup($roster->db->table('addon_config'));

			$success = $addon->uninstall();
			if ($success)
			{
				$installer->remove_permissions($previous['addon_id']);
			}
			break;

		case 'purge':
			$success = purge($installer->addata['basename']);
			break;

		default:
			$installer->seterrors($roster->locale->act['installer_invalid_type']);
			$success = false;
			break;
	}

	if( !$success )
	{
		$installer->seterrors($roster->locale->act['installer_no_success_sql']);
		return false;
	}
	else
	{
		$success = $installer->install();
		$installer->setmessages(sprintf($roster->locale->act['installer_' . $_POST['type'] . '_' . $success],$installer->addata['basename']));
	}

	// Restore our locale array
	$roster->locale->wordings = $localetemp;
	unset($localetemp);

	return true;
}


/**
 * Addon purge
 * Removes an addon with a bad install/upgrade/un-install
 *
 * @param string $dbname
 * @return bool
 */
function purge( $dbname )
{
	global $roster, $installer;

	// Delete addon tables under dbname.
	$query = 'SHOW TABLES LIKE "' . $roster->db->prefix . 'addons_' . $dbname . '%"';
	$tables = $roster->db->query($query);
	if( !$tables )
	{
		$installer->seterrors('Error while getting table names for ' . $dbname . '. MySQL said: ' . $roster->db->error(),$roster->locale->act['installer_error'],__FILE__,__LINE__,$query);
		return false;
	}
	if( $roster->db->num_rows($tables) )
	{
		while ($row = $roster->db->fetch($tables))
		{
			$query = 'DROP TABLE `' . $row[0] . '`;';
			$dropped = $roster->db->query($query);
			if( !$dropped )
			{
				$installer->seterrors('Error while dropping ' . $row[0] . '.<br />MySQL said: ' . $roster->db->error(),$roster->locale->act['installer_error'],__FILE__,__LINE__,$query);
				return false;
			}
		}
	}

	// Get the addon id for this basename
	$query = "SELECT `addon_id` FROM `" . $roster->db->table('addon') . "` WHERE `basename` = '" . $dbname . "';";
	$addon_id = $roster->db->query_first($query);

	if( $addon_id !== false )
	{
		// Delete menu entries
		$query = 'DELETE FROM `' . $roster->db->table('menu_button') . '` WHERE `addon_id` = "' . $addon_id . '";';
		$roster->db->query($query) or $installer->seterrors('Error while deleting menu entries for ' . $dbname . '.<br />MySQL said: ' . $roster->db->error(),$roster->locale->act['installer_error'],__FILE__,__LINE__,$query);
		// Delete addon config entries
		$query = 'DELETE FROM `' . $roster->db->table('addon_config') . '` WHERE `addon_id` = "' . $addon_id . '";';
		$roster->db->query($query) or $installer->seterrors('Error while deleting menu entries for ' . $dbname . '.<br />MySQL said: ' . $roster->db->error(),$roster->locale->act['installer_error'],__FILE__,__LINE__,$query);
	}

	// Delete addon table entry
	$query = 'DELETE FROM `' . $roster->db->table('addon') . '` WHERE `basename` = "' . $dbname . '"';
	$roster->db->query($query) or $installer->seterrors('Error while deleting addon table entry for ' . $dbname . '.<br />MySQL said: ' . $roster->db->error(),$roster->locale->act['installer_error'],__FILE__,__LINE__,$query);

	return true;
}

function processPage()
{
	global $roster;

	$default = ( $_POST['config_default_page'] );
	$query = "UPDATE `" . $roster->db->table('config') . "` SET `config_value` = '$default' WHERE `id` = '1050';";

	if( !$roster->db->query($query) )
	{
		die_quietly($roster->db->error(),'Database Error',__FILE__,__LINE__,$query);
	}
	else
	{
		// Set this enforce_rules value to the right one since roster_config isn't refreshed here
		$roster->config['default_page'] = $default;
		$roster->set_message(sprintf($roster->locale->act['default_page_set'], $default));
	}
}


function processAccess()
{
	global $roster;

	$access = implode(":",$_POST['config_access']);
	$id = (int)$_POST['id'];
	$query = "UPDATE `" . $roster->db->table('addon') . "` SET `access` = '$access' WHERE `addon_id` = '$id';";

	if( !$roster->db->query($query) )
	{
		die_quietly($roster->db->error(),'Database Error',__FILE__,__LINE__,$query);
	}
}
