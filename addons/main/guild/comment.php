<?php
/**
 * WoWRoster.net WoWRoster
 *
 * @copyright  2002-2011 WoWRoster.net
 * @license    http://www.gnu.org/licenses/gpl.html   Licensed under the GNU General Public License v3. * @package    News
 */

if( !defined('IN_ROSTER') )
{
	exit('Detected invalid access to this file!');
}

$roster->auth->setAction('&amp;id=' . $_GET['id']);

// Add the comment if one was POSTed
if( isset($_POST['process']) && $_POST['process'] == 'process' )
{
	if( !$roster->auth->getAuthorized('news_can_post_comment') && !isset($_POST['comment_id']) )
	{
		echo $roster->auth->getLoginForm();
		return; //To the addon framework
	}
	if( !$roster->auth->getAuthorized('news_can_edit_comment') && isset($_POST['comment_id']) )
	{
		echo $roster->auth->getLoginForm();
		return; //To the addon framework
	}

	if( isset($_POST['author']) && !empty($_POST['author'])
		&& isset($_POST['comment']) && !empty($_POST['comment'])
		&& isset($_GET['id']) && is_numeric($_GET['id']) )
	{
		if( isset($_POST['html']) && $_POST['html'] == 1 && $addon['config']['comm_html'] >= 0)
		{
			$html = 1;
		}
		else
		{
			$html = 0;
		}

		if( isset($_POST['comment_id']) )
		{
			$query = "UPDATE `" . $roster->db->table('comments',$addon['basename']) . "` SET "
				. "`author` = '" . $roster->auth->user['user_display'] . "', "
				. "`author_id` = '" . $roster->auth->user['id'] . "', "
				. "`content` = '" . $_POST['comment'] . "', "
				. "`html` = '" . $html . "' "
				. "WHERE `comment_id` = '" . $_POST['comment_id'] . "';";

			if( $roster->db->query($query) )
			{
				$roster->set_message($roster->locale->act['comment_edit_success']);
			}
			else
			{
				$roster->set_message('There was a DB error while editing your comment.', '', 'error');
				$roster->set_message('<pre>' . $roster->db->error() . '</pre>', 'MySQL Said', 'error');
			}
		}
		else
		{
			$query = "INSERT INTO `" . $roster->db->table('comments',$addon['basename']) . "` SET "
				. "`news_id` = '" . $_GET['id'] . "', "
				. "`author` = '" . $roster->auth->user['user_display'] . "', "
				. "`author_id` = '" . $roster->auth->user['id'] . "', "
				. "`content` = '" . $_POST['comment'] . "', "
				. "`html` = '" . $html . "', "
				. "`date` = '". $roster->db->escape(gmdate('Y-m-d H:i:s')). "';";

			if( $roster->db->query($query) )
			{
				$roster->set_message($roster->locale->act['comment_add_success']);
			}
			else
			{
				$roster->set_message('There was a DB error while adding your comment.', '', 'error');
				$roster->set_message('<pre>' . $roster->db->error() . '</pre>', 'MySQL Said', 'error');
			}
		}
	}
	else
	{
		$roster->set_message($roster->locale->act['comment_error_process'], '', 'error');
	}
}

// Get the article to display at the head of the page
$query = "SELECT `news`.*, "
	. "DATE_FORMAT(  DATE_ADD(`news`.`date`, INTERVAL " . $roster->config['localtimeoffset'] . " HOUR ), '" . $roster->locale->act['timeformat'] . "' ) AS 'date_format', "
	. "COUNT(`comments`.`comment_id`) comm_count "
	. "FROM `" . $roster->db->table('news',$addon['basename']) . "` news "
	. "LEFT JOIN `" . $roster->db->table('comments',$addon['basename']) . "` comments USING (`news_id`) "
	. "WHERE `news`.`news_id` = '" . $_GET['id'] . "' "
	. "GROUP BY `news`.`news_id`";

$result = $roster->db->query($query);

if( $roster->db->num_rows($result) == 0 )
{
	echo messagebox($roster->locale->act['bad_news_id'], '', 'sred');
	return;
}

$news = $roster->db->fetch($result);

// Assign template vars
$roster->tpl->assign_vars(array(
	'S_COMMENTS'		=> false,
	'S_USER'			=> $roster->auth->user['user_display'],
	'POST_TITLE'		=> $news['title'],
	'S_HTML_ENABLE'		=> false,
	'S_COMMENT_HTML'	=> $addon['config']['comm_html'],
	'S_ADD_NEWS'		=> $roster->auth->getAuthorized('news_can_post'),
	'S_EDIT_NEWS'		=> $roster->auth->getAuthorized('news_can_edit_post'),
	'S_ADD_COMMENT'		=> $roster->auth->getAuthorized('news_can_post_comment'),
	'S_EDIT_COMMENT'	=> $roster->auth->getAuthorized('news_can_edit_comment'),

	'U_ADD_FORMACTION'	=> makelink('guild-main-comment&amp;id=' . $_GET['id']),
	'U_BACK'			=> makelink('guild-main'),
	'U_NEWS_ID'			=> $news['news_id'],

	)
);

require_once (ROSTER_LIB . 'bbcode.php' );
$bbcode = new bbcode();

	$message = $news['text'];
	$message = $bbcode->bbcodeParser($message);
	//$message = bbcode_nl2br($message);
	//echo $news['title'].'-'.$news['poster'].'-'.$news['text'].'<br />';
	$roster->tpl->assign_block_vars('news', array(
		'POSTER'    => $news['poster'],
		'NUM'       => $numn,
		'TEXT'      => $message,
		'TITLE'     => $news['title'],
		'DATE'      => $news['date_format'],
		'U_EDIT'    => makelink('guild-'. $addon['basename'] .'-edit&amp;id='. $news['news_id']),
		'U_COMMENT' => makelink('guild-'. $addon['basename'] .'-comment&amp;id='. $news['news_id']),
		'U_EDIT'    => makelink('guild-'. $addon['basename'] .'-edit&amp;id='. $news['news_id']),
		'L_COMMENT' => ($news['comm_count'] != 1 ? sprintf($roster->locale->act['n_comments'], $news['comm_count']) : sprintf($roster->locale->act['n_comment'], $news['comm_count'])),
		'NEWS_TYPE' => $news['news_type']
	));


// Get the comments
$query = "SELECT `comments`.*, "
		. "DATE_FORMAT(  DATE_ADD(`comments`.`date`, INTERVAL " . $roster->config['localtimeoffset'] . " HOUR ), '" . $roster->locale->act['timeformat'] . "' ) AS 'date_format' "
		. "FROM `" . $roster->db->table('comments',$addon['basename']) . "` comments "
		. "WHERE `comments`.`news_id` = '" . $_GET['id'] . "' "
		. "ORDER BY `comments`.`date` ASC;";

$result = $roster->db->query($query);

if( $roster->db->num_rows() > 0 )
{
	$roster->tpl->assign_var('S_COMMENTS', true);

	while( $comment = $roster->db->fetch($result) )
	{
		if( isset($news['html']) && $news['html'] == 1 && $addon['config']['news_html'] >= 0 )
		{
			$comment['content'] = nl2br($comment['content']);
		}
		else
		{
			$comment['content'] = nl2br(htmlentities($comment['content']));
		}
		$roster->tpl->assign_block_vars('comment_row', array(
			'CONTENT'       => $comment['content'],
			'AUTHOR'        => $comment['author'],
			'DATE'          => $comment['date_format'],
			'U_COMMENT_ID'  => $comment['comment_id'],

			'U_EDIT'     => makelink('guild-main-comment_edit&amp;id=' . $comment['comment_id']),
			)
		);
	}
}

$roster->tpl->set_filenames(array(
	'head'      => $addon['basename'] . '/comment_head.html',
	'news_head' => $addon['basename'] . '/comment_news.html',
	'body'      => $addon['basename'] . '/comment.html',
	'foot'      => $addon['basename'] . '/comment_foot.html'
	)
);

$roster->tpl->display('head');
$roster->tpl->display('news_head');
$roster->tpl->display('body');

if( !$roster->auth->getAuthorized('news_can_post_comment') )
{
	echo $roster->auth->getLoginForm();
}
else
{
	$roster->tpl->set_filenames(array('news_foot' => $addon['basename'] . '/comment_add.html'));
	$roster->tpl->display('news_foot');

}
	$roster->tpl->display('foot');

