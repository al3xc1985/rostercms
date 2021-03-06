<?php
/**
 * WoWRoster.net WoWRoster
 *
 * Roster upload rule config
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

if (isset($_POST['process']) && $_POST['process'] == 'process')
{
	$count=1;
	if (isset($_POST['class_id']))
	{
		//	aprint($_POST);
		$classid = (isset($_POST['class_id']) ? $_POST['class_id'] : $_GET['class']);
		echo '<br>--[ '.$classid.' ]--<br>';
		$talents = $roster->api->Talents->getTalentInfo($classid);
		
		$querystr = "DELETE FROM `" . $roster->db->table('talents_data') . "` WHERE `class_id` = '" . $classid . "';";
		if (!$roster->db->query($querystr))
		{
			$roster->set_message('Talent Data Table could not be emptied.', '', 'error');
			$roster->set_message('<pre>' . $roster->db->error() . '</pre>', 'MySQL Said', 'error');
			return;
		}

		//talent_mastery
		/*
		$querystr = "DELETE FROM `" . $roster->db->table('talent_mastery') . "` WHERE `class_id` = '" . $classid . "';";
		if (!$roster->db->query($querystr))
		{
			$roster->set_message('Talent Tree Data Table could not be emptied.', '', 'error');
			$roster->set_message('<pre>' . $roster->db->error() . '</pre>', 'MySQL Said', 'error');
			return;
		}
		*/
		$treenum = 1;
		$t=1;
		foreach ($talents['talentData']['talentTrees'] as $a => $treedata)
		{

			$lvl = 15;
			foreach ($treedata as $t => $talent)
			{

				$tooltip = '';
				$tooltip .= (isset($talent['spell']['cost']) ? $talent['spell']['cost'] : '');
				$tooltip .=	(isset($talent['spell']['range']) ? '<span style="float:right;">'.$talent['spell']['range'].'</span>' : '');
				$tooltip .=	(isset($talent['spell']['castTime']) ? '<br>'.$talent['spell']['castTime'] : '');
				$tooltip .=	(isset($talent['spell']['cooldown']) ? '<span style="float:right;">'.$talent['spell']['cooldown'].'</span>' : '');
				$tooltip .= '<br>'.$talent['spell']['htmlDescription'];
				
				$values = array(
					'talent_id'  => $talent['spell']['spellId'],
					'talent_num' => $t,
					'tree_order' => '0',
					'class_id'   => $talent['classKey'],
					'name'       => $talent['spell']['name'],
					'tree'       => '',//$treedata['name'],
					'tooltip'    => $tooltip,
					'texture'    => $talent['spell']['icon'],
					'isspell'	 => ( !$talent['spell']['keyAbility'] ? false : true ),
					'row'        => ($talent['tier'] + 1),
					'column'     => ($talent['column'] + 1),
					'rank'       => $lvl
				);

				
				$querystr = "INSERT INTO `" . $roster->db->table('talents_data') . "` "
					. $roster->db->build_query('INSERT', $values) . ";";
				$result = $roster->db->query($querystr);
				$count++;
			$t++;	
			}
			$lvl = ($lvl+15);
			
			$count++;
			$treenum++;
		}


		$roster->set_message(sprintf($roster->locale->act['adata_update_class'], $roster->locale->act['id_to_class'][$classid]));
		$roster->set_message(sprintf($roster->locale->act['adata_update_row'], $count));
	}
	
	if (isset($_POST['clear']))
	{
		//TRUNCATE TABLE  `roster_api_gems`
		$usage = "TRUNCATE TABLE `" . $roster->db->table('api_usage') . "`;";
		$resultusage = $roster->db->query($usage);
		
		$roster->set_message(sprintf($roster->locale->act['installer_purge_0'],'Api Usage'));
	}
	
	if (isset($_POST['truncate']))
	{
		//TRUNCATE TABLE  `roster_api_gems`
		$qgem = "TRUNCATE TABLE `" . $roster->db->table('api_gems') . "`;";
		$resultgem = $roster->db->query($qgem);

		$qitem = "TRUNCATE TABLE `" . $roster->db->table('api_items') . "`;";
		$resultitem = $roster->db->query($qitem);
		
		$roster->set_message(sprintf($roster->locale->act['installer_purge_0'],'Item/gem cache'));
	}


	if (isset($_POST['parse']) && $_POST['parse'] == 'ALL')
	{

		$classes = array('1','2','3','4','5','6','7','8','9','10','11','12','0');
		$talent = $roster->api2->fetch('talents');
		echo '<prE>';
		//print_r($talent);
		echo '</pre>';
		$messages = '';
		$t=0;
		foreach ($talent as $class_id => $info)
		{
			$tid = $class_id;
			$i = $tid;
			
			$querystr = "DELETE FROM `" . $roster->db->table('talents_data') . "` WHERE `class_id` = '" . $tid . "';";
			if (!$roster->db->query($querystr))
			{
				$roster->set_message('Talent Data Table could not be emptied.', '', 'error');
				$roster->set_message('<pre>' . $roster->db->error() . '</pre>', 'MySQL Said', 'error');
				return;
			}

			$querystr = "DELETE FROM `" . $roster->db->table('talenttree_data') . "` WHERE `class_id` = '" . $tid . "';";
			if (!$roster->db->query($querystr))
			{
				$roster->set_message('Talent Tree Data Table could not be emptied.', '', 'error');
				$roster->set_message('<pre>' . $roster->db->error() . '</pre>', 'MySQL Said', 'error');
				return;
			}

			$count = 1;
			$treenum = 1;
		//$i=$tid;
		
			//old method we leave this just incase
			foreach ($info['talents'] as $tier => $colum)
			{
				$lvl = 15;
				foreach ($colum as $s => $tal)
				{
					foreach ($tal as $spec => $talent)
					{
					
						//echo '<pre>';print_r($talent);echo '</pre><br>';
						$tooltip = '<div><span class="float-right">'.(isset($talent['spell']['range']) ? $talent['spell']['range'] : '').'</span>'.(isset($talent['spell']['powerCost']) ? $talent['spell']['powerCost'] : '').'<span class="clear"><!-- --></span></div><div><span class="float-right">'.(isset($talent['spell']['cooldown']) ? $talent['spell']['cooldown'] : '').'</span>'.(isset($talent['spell']['castTime']) ? $talent['spell']['castTime'] : '').'<span class="clear"><!-- --></span></div><div class="color-tooltip-yellow">'.$talent['spell']['description'].'</div>';
	
						$values = array(
							'talent_id'  => $talent['spell']['id'],
							'talent_num' => $t,
							'tree_order' => $spec,
							'class_id'   => $class_id,
							'name'       => $talent['spell']['name'],
							'tree'       => '',//$treedata['name'],
							'tooltip'    => tooltip($tooltip),
							'texture'    => $talent['spell']['icon'],
							//'isspell'	 => ( !$talent['spell']['keyAbility'] ? false : true ),
							'row'        => ($talent['tier'] + 1),
							'column'     => ($talent['column'] + 1),
							'rank'       => $lvl
						);

						
						$querystr = "INSERT INTO `" . $roster->db->table('talents_data') . "` "
							. $roster->db->build_query('INSERT', $values) . ";";
						$result = $roster->db->query($querystr);
						$count++;
					}
					$t++;
				}
				$lvl = ($lvl+15);
			}
		
			foreach ($info['specs'] as $a => $treedata)
			{
			
				$values = array(
					'tree'       => $treedata['name'],
					'order'      => $a,
					'class_id'   => $class_id,
					'background' => strtolower($treedata['backgroundImage']),
					'icon'       => $treedata['icon'],
					'roles'		 => $treedata['role'],
					'desc'		 => $treedata['description'],
					'tree_num'   => $treedata['order']
				);

					
				$querystr = "INSERT INTO `" . $roster->db->table('talenttree_data') . "` "
					. $roster->db->build_query('INSERT', $values) . "
					;";
				$result = $roster->db->query($querystr);
			}


			$messages .= sprintf($roster->locale->act['adata_update_class'], $roster->locale->act['id_to_class'][$class_id]).' - ';
			$messages .= sprintf($roster->locale->act['adata_update_row'], $count).'<br>';
		}
		$roster->set_message($messages);
	}
}
//echo 'will have update information for talents';

$classes = $roster->locale->act['class_to_id'];


foreach ($classes as $class => $num)
{
	$querystra = $classr = $resulta = 0;
	$querystra = "SELECT * FROM `" . $roster->db->table('talents_data') . "` WHERE `class_id` = '" . $num . "';";
	$resulta = $roster->db->query($querystra);
	$classr = $roster->db->num_rows($resulta);
	$i = 0;

	$roster->tpl->assign_block_vars('classes', array(
		'NAME'       => $class,
		'ID'         => $num,
		'UPDATELINK' => '',//makelink('&amp;class=' . $num),
		'ROWS'       => $classr,
		'ROW'        => (($i % 2) + 1)
		)
	);
}
	
	
	$qgem = "SELECT * FROM `" . $roster->db->table('api_gems') . "`;";
	$resultgem = $roster->db->query($qgem);
	$gem = $roster->db->num_rows($resultgem);
	$roster->tpl->assign_block_vars('cache', array(
		'NAME'       => 'Gems',
		'ROWS'       => $gem,
		'ROW'        => (($i % 2) + 1)
		)
	);
	$qitem = "SELECT * FROM `" . $roster->db->table('api_items') . "`;";
	$resultitem = $roster->db->query($qitem);
	$item = $roster->db->num_rows($resultitem);
	$roster->tpl->assign_block_vars('cache', array(
		'NAME'       => 'Items',
		'ROWS'       => $item,
		'ROW'        => (($i % 2) + 1)
		)
	);

	
	
	$queryx = "SELECT * FROM `" . $roster->db->table('api_usage') . "` ORDER BY `date` DESC LIMIT 0,150;";
	$resultx = $roster->db->query($queryx);
	$usage = array();
	while ($row = $roster->db->fetch($resultx))
	{
		$usage[$row['date']][$row['type']]['total']=$row['total'];
	}

	
	foreach($usage as $date => $x)
	{
		$roster->tpl->assign_block_vars('apiusage', array(
				'DATE'	=> $date
			)
		);
		foreach($x as $type => $d)
		{
			$roster->tpl->assign_block_vars('apiusage.type', array(
					'TYPE'       => $type,
					'REQ'         => $d['total'],
					'PERCENT'       => ($d['total']/3000*100).'% (Based on daily limit of 3000 with no API key)',
					'ROW_CLASS'        => (($i % 2) + 1)
				)
			);
		}
	}
	
$roster->tpl->set_filenames(array('body' => 'admin/armory_data.html'));
$body = $roster->tpl->fetch('body');



/**
 * Format tooltips for insertion to the db
 *
 * @param mixed $tipdata
 * @return string
 */
function tooltip( $tipdata )
{
	$tooltip = '';

	if( is_array($tipdata) )
	{
		$tooltip = implode("\n",$tipdata);
	}
	else
	{
		$tooltip = str_replace('<br>',"\n",$tipdata);
	}
	return $tooltip;
}
