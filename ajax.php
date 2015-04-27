<?php
// moodle-local_chat
// ==================
// Local Moodle plugin
// 
// Plugin: Chat
// Version 1.0
// System: Moodle v. 2.6, 2.7, 2.8
// Description: Chat plugin allows users to communicate with each other. 
// Plugin is created as a local plugin for Moodle. 
// Plugin allows administrator user to configure user communication preferences by role.
// 
// @package    	local_chat
// @copyright  	2015 SEBALE LLC
// @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
// @created by		SEBALE LLC
// @website		www.sebale.net

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$id				= optional_param('id', 0, PARAM_INT);
$action			= optional_param('action', '', PARAM_RAW);
$courseid		= optional_param('courseid', 0, PARAM_INT);

if($action == 'chat_load_data') {
	$oncourse = get_config('local_chat', 'oncourse');
	$inactivity = get_config('local_chat', 'inactivity');
	$showroles = get_config('local_chat', 'showroles');
	
	$newmessages_count = 0; $online_users_list = array(); $messages = array(); $online_users = 0;
	$resent_users = array(); $resent_users_list = array(); $messages_list = array(); $new_messages_list = array();
	//------------ New messages -------------- //
	$new_messages = $DB->get_records_sql("SELECT * FROM {local_chat} 
			 WHERE useridto = $USER->id AND visibleto = 1 AND timeread = 0
				ORDER BY timecreated");
	if (count($new_messages)){
		$newmessages_count = count($new_messages);
		foreach ($new_messages as $message){
			$messages[$message->useridfrom][] = $message;
		}
	}
	
	//------------ Online Users list with new messages -------------- //
	$timetoshowusers = 300; //Seconds default
	if (isset($inactivity) and (int)$inactivity > 0) {
		$timetoshowusers = (int)$inactivity * 60;
	}
	
	$now = time();
	$timefrom = 100 * floor(($now - $timetoshowusers) / 100); // Round to nearest 100 seconds for better query cache
	
	if ($courseid > 0){
		$course = $DB->get_record('course', array('id'=>$courseid));
		$coursecontext = context_course::instance($courseid, MUST_EXIST);
		//Calculate if we are in separate groups
		$isseparategroups = ($course->groupmode == SEPARATEGROUPS
							 && $course->groupmodeforce
							 && !has_capability('moodle/site:accessallgroups', $coursecontext));

		//Get the user current group
		$currentgroup = $isseparategroups ? groups_get_course_group($course) : NULL;
	}
	
	$groupmembers = "";
	$groupselect  = "";
	$params = array();
	
	//Add this to the SQL to show only group users
	if ($currentgroup !== NULL) {
		$groupmembers = " LEFT JOIN {groups_members} gm ON gm.userid = u.id ";
		$groupselect = "AND gm.groupid = :currentgroup";
		$params['currentgroup'] = $currentgroup;
	}

	$userfields = user_picture::fields('u', array('username'));
	$params['now'] = $now;
	$params['timefrom'] = $timefrom;
	
	
	if ((int)$oncourse > 0 and $courseid != SITEID){
		list($esqljoin, $eparams) = get_enrolled_sql($coursecontext);
        $params = array_merge($params, $eparams);
		
		$sql = "SELECT $userfields, MAX(ul.timeaccess) AS lastaccess, GROUP_CONCAT( r.shortname ORDER BY r.shortname ASC SEPARATOR ';') AS roles
                      FROM {user_lastaccess} ul 
							LEFT JOIN {user} u ON u.id = ul.userid
							$groupmembers 
							LEFT JOIN (SELECT DISTINCT r.shortname, ra.userid FROM {role_assignments} ra LEFT JOIN {role} r ON r.id = ra.roleid) r ON r.userid = u.id
						JOIN ($esqljoin) euj ON euj.id = u.id
                     WHERE ul.timeaccess > :timefrom
                           AND ul.courseid = :courseid
                           AND ul.timeaccess <= :now
                           AND u.deleted = 0
						   AND u.id != $USER->id
                           $groupselect
                  GROUP BY $userfields
                  ORDER BY u.firstname, u.lastname ASC ";
		 $params['courseid'] = $courseid;
	} else {
		$sql = "SELECT $userfields, MAX(u.lastaccess) AS lastaccess, GROUP_CONCAT( r.shortname ORDER BY r.shortname ASC SEPARATOR ';') AS roles
			  FROM {user} u 
					$groupmembers
					LEFT JOIN (SELECT DISTINCT r.shortname, ra.userid FROM {role_assignments} ra LEFT JOIN {role} r ON r.id = ra.roleid) r ON r.userid = u.id
			 WHERE u.lastaccess > :timefrom
				   AND u.lastaccess <= :now
				   AND u.deleted = 0
				   AND u.id != $USER->id
				   $groupselect
		  GROUP BY $userfields
		  ORDER BY u.firstname, u.lastname ASC ";
	}
	
	$db_users = $DB->get_records_sql($sql, $params);
	if (count($db_users)){
		foreach ($db_users as $auser){
			$user_html = '';
			$user_html .= '<li class="contact-item clearfix'.((isset($messages[$auser->id]) and count($messages[$auser->id]) > 0) ? ' notread' : '').' userid_'.$auser->id.'" user-name="'.fullname($auser).'" user-id="'.$auser->id.'" onclick="chat_open_conversation('.$auser->id.', \''.fullname($auser).'\')">';
				$user_html .= '<div class="user-img-wrapper">'.$OUTPUT->user_picture($auser, array('size'=>30, 'link'=>false)).'</div>';
				$user_html .= '<div class="user-data">';
					$user_html .= '<span class="user-name">'.fullname($auser).'</span>';
					$user_roles_str = ''; $u_roles = array();
					if (is_siteadmin($auser->id)){
						$user_roles_str .= '<span title="Administrator">A</span>';
					}
					if ($auser->roles != ''){
						$u_roles = array_unique(explode(';', $auser->roles));
						foreach($u_roles as $role){
							$user_roles_str .= '<span title="'.ucfirst($role).'">'.substr($role, 0, 1).'</span>';
						}
					}
					$user_html .= '<span class="user-email clearfix">'.(($showroles and !empty($user_roles_str)) ? $user_roles_str : 'Last access: '.date('M d Y, h:i a', $auser->lastaccess)).'</span>';
				$user_html .= '</div>';
				if (isset($messages[$auser->id]) and count($messages[$auser->id]) > 0){
					$user_html .= '<span class="new-items-count">'.count($messages[$auser->id]).'</span>';
				}
			$user_html .= '</li>';
			$online_users[$auser->id]['userid'] = $auser->id;
			$online_users_list[$auser->id]['user_html'] = $user_html;
		}
	} else {
		$online_users = 0;
		$online_users_list = 0;
	}
	
	//------------ Resent Users list with new messages -------------- //
	$sql = "SELECT DISTINCT u.*, GROUP_CONCAT( r.shortname ORDER BY r.shortname ASC SEPARATOR ';') AS roles
			  FROM {local_chat} m
				LEFT JOIN {user} u ON u.id = m.useridfrom OR u.id = m.useridto
				LEFT JOIN (SELECT DISTINCT r.shortname, ra.userid FROM {role_assignments} ra LEFT JOIN {role} r ON r.id = ra.roleid) r ON r.userid = u.id
			 WHERE (m.useridto = $USER->id AND m.visibleto = 1)
				   AND u.id != $USER->id
			GROUP BY u.id
		  ORDER BY u.firstname, u.lastname ASC";
	$db_users = $DB->get_records_sql($sql);
	if (count($db_users)){
		foreach ($db_users as $auser){
			$user_html = '';
			$user_html .= '<li class="contact-item clearfix'.((isset($messages[$auser->id]) and count($messages[$auser->id]) > 0) ? ' notread' : '').' userid_'.$auser->id.'" user-name="'.fullname($auser).'" user-id="'.$auser->id.'" onclick="chat_open_conversation('.$auser->id.', \''.fullname($auser).'\')">';
				$user_html .= '<div class="user-img-wrapper">'.$OUTPUT->user_picture($auser, array('size'=>30, 'link'=>false)).'</div>';
				$user_html .= '<div class="user-data">';
					$user_html .= '<span class="user-name">'.fullname($auser).'</span>';
					$user_roles_str = ''; $u_roles = array();
					if (is_siteadmin($auser->id)){
						$user_roles_str .= '<span title="Administrator">A</span>';
					}
					if ($auser->roles != ''){
						$u_roles = array_unique(explode(';', $auser->roles));
						foreach($u_roles as $role){
							$user_roles_str .= '<span title="'.ucfirst($role).'">'.substr($role, 0, 1).'</span>';
						}
					}
					$user_html .= '<span class="user-email clearfix">'.(($showroles and !empty($user_roles_str)) ? $user_roles_str : 'Last access: '.date('M d Y, h:i a', $auser->lastaccess)).'</span>';
				$user_html .= '</div>';
				if (isset($messages[$auser->id]) and count($messages[$auser->id]) > 0){
					$user_html .= '<span class="new-items-count">'.count($messages[$auser->id]).'</span>';
				}
			$user_html .= '</li>';
			$resent_users[$auser->id]['userid'] = $auser->id;
			$resent_users_list[$auser->id]['user_html'] = $user_html;
		}
	} else {
		$resent_users = 0;
		$resent_users_list = 0;
	}
	//------------ Messages -------------- //
	if ($id > 0){
		$user = $DB->get_record('user', array('id'=>$id));
		if (count($messages[$user->id]) > 0){
			$messages_html = '';
			foreach ($messages[$user->id] as $message){
				$message->timeread = time();
				$DB->update_record('local_chat', $message);
				
				$messages_html .= '<div class="message clearfix">';
					$messages_html .= '<div class="profile-img-wrapper">'.$OUTPUT->user_picture($user, array('size'=>30, 'link'=>false)).'</div><div class="chat-bubble received">'.$message->message.'</div>';
				$messages_html .= '</div>';
			}
			$new_messages_list[$id] = $messages_html;
		} else {
			$new_messages_list[$id] = 0;
		}
	}
	
	//------------ Results -------------- //
	$result = array('newmessages_count'=>$newmessages_count, 'online_users'=>$online_users, 'online_users_list'=>$online_users_list, 'resent_users'=>$resent_users, 'resent_users_list'=>$resent_users_list, 'new_messages_list'=>$new_messages_list);
	echo json_encode($result);
	exit;
}

if($action == 'chat_load_conversation') {
	$limit = optional_param('limit', 1, PARAM_INT); $messages = array(); $output = '';
	$savehistory = get_config('local_chat', 'savehistory');
	$historyperiod = ((int)$savehistory > 0) ? (int)$savehistory*86400 : 7*86400;
	$DB->delete_records_select('local_chat', "useridto = $USER->id AND useridfrom = $id AND timeread > 0 AND timecreated < ".(time()-$historyperiod));
	$sql = "SELECT * FROM {local_chat}
			 WHERE (useridfrom = $USER->id AND useridto = $id AND visiblefrom = 1)
				OR (useridto = $USER->id AND useridfrom = $id AND visibleto = 1)
			ORDER BY timecreated DESC".(($limit > 0) ? ' LIMIT 15' : '');
	$db_messages = $DB->get_records_sql($sql);
	$user = $DB->get_record('user', array('id'=>$id));
	
	$timetoshowusers = 300; //Seconds default
	if (isset($CFG->block_online_users_timetosee)) {
		$timetoshowusers = $CFG->block_online_users_timetosee * 60;
	}
	$now = time(); $user_loggedin = 0;
	$timefrom = 100 * floor(($now - $timetoshowusers) / 100); // Round to nearest 100 seconds for better query cache
	if ($user->lastaccess > $timefrom and $user->lastaccess <= $now){
		$user_loggedin = 1;
	}
	
	$not_read = $DB->get_records_sql("SELECT * FROM {local_chat} WHERE useridto = $USER->id AND useridfrom = $id AND visibleto = 1 AND timeread = 0");
	if (count($not_read)){
		foreach ($not_read as $nr){
			$nr->timeread = time();
			$DB->update_record('local_chat', $nr);
		}
	}
	
	if (count($db_messages)){
		foreach ($db_messages as $message){
			$messages[$message->id] = $message;
		}
		ksort($messages);
		foreach ($messages as $item){
			$output .= '<div class="message clearfix">';
				if ($item->useridfrom == $USER->id){
					$output .= '<div class="chat-bubble sent delivered">'.$item->message.'</div>';
				} else {
					$output .= '<div class="profile-img-wrapper">'.$OUTPUT->user_picture($user, array('size'=>30, 'link'=>false)).'</div><div class="chat-bubble received">'.$item->message.'</div>';
				}
			$output .= '</div>';
		}
	}
	echo json_encode(array('output'=>$output, 'user_loggedin'=>$user_loggedin));
	exit;
}

if($action == 'chat_create_message') {
	$message = optional_param('message', '', PARAM_NOTAGS);
	
	$new_message = new stdClass();
	$new_message->useridfrom = $USER->id;
	$new_message->useridto = $id;
	$new_message->message = $message;
	$new_message->timecreated = time();
	$new_message->id = $DB->insert_record('local_chat', $new_message);
	echo $new_message->id;
	exit;
}


exit;