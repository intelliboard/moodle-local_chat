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

function chatInit(){
    global $CFG, $PAGE, $SITE, $DB, $USER, $COURSE;

	$chat = optional_param('chat', 0, PARAM_INT);
	$enabled = get_config('local_chat', 'enabled');
	
	if ($enabled and isloggedin() and !isguestuser()){
		
		chatPrintChat(true);
		
		$params = new stdClass();
		$params->chatAjaxUrl = "$CFG->wwwroot/local/chat/ajax.php";
		$params->chatTimer = 3000;	
		$params->chatCourseid = $COURSE->id;	

		$PAGE->requires->jquery();
		$PAGE->requires->js('/local/chat/module.js', false);
		$PAGE->requires->js_function_call('chatInit', array($params), true);
	}
}

function chatPrintChat($display = false){
	$output = '<div class="chat-btn" onclick="chat_open();"><i class="fa fa-comments-o"></i></div>';
	
	$output .= '<div class="chat-mainbox" style="display:none;">';
		$output .= '<div class="chat-inner">';
			$output .= '<div class="chat-header"><ul><li>'.get_string('chat_title', 'local_chat').'</li></ul><i class="fa fa-times" onclick="chat_close();"></i></div>';
			$output .= '<div class="chat-content">';
				$output .= '<div class="chat-contacts open">';
					$output .= '<div class="nav-header-chat">';
						$output .= '<div class="user-name">'.get_string('chatlist', 'local_chat').'</div>';
						$output .= '<span class="nav-header-type header-contacts">'.get_string('online_contacts', 'local_chat').'</span>';
						$output .= '<i class="fa fa-ellipsis-h" onclick="chat_change_type();"></i>';
						$output .= '<div class="chat-types-box">';
							$output .= '<ul>';
								$output .= '<li onclick="chat_filter_users(\'online\');">'.get_string('online_contacts', 'local_chat').'</li>';
								$output .= '<li onclick="chat_filter_users(\'resent\');">'.get_string('recently_contacted', 'local_chat').'</li>';
							$output .= '</ul>';
						$output .= '</div>';
					$output .= '</div>';
					$output .= '<div class="chat-contacts-content">';
						$output .= '<ul class="chat-contacts-online active">';
							$output .= '<i class="fa fa-spin fa-spinner"></i>';
						$output .= '</ul>';
						$output .= '<ul class="chat-contacts-resent">';
							$output .= '<i class="fa fa-spin fa-spinner"></i>';
						$output .= '</ul>';
					$output .= '</div>';
				$output .= '</div>';
				$output .= '<div class="chat-messages">';
					$output .= '<div class="nav-header-chat">';
						$output .= '<i class="fa fa-angle-left" onclick="chat_close_conversation();" title="Back"></i>';
						$output .= '<div class="user-name"></div>';
						$output .= '<span class="nav-header-type header-conversation"></span>';
					$output .= '</div>';
					$output .= '<div class="chat-conversation" id="chat-conversation" user-id="0"></div>';
					$output .= '<div class="chat-form">';
						$output .= '<input type="text" name="message" value="" placeholder="'.get_string('say_something', 'local_chat').'" id="message" />';
						$output .= '<i class="fa fa-paper-plane" title="Send message" onclick="chat_save_message();"></i>';
					$output .= '</div>';
				$output .= '</div>';
			$output .= '</div>';
		$output .= '</div>';
	$output .= '</div>';
	
	if ($display){
		echo $output;
	} else {
		return $output;
	}
}

chatInit();
