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

defined('MOODLE_INTERNAL') || die;

$settings = new admin_settingpage('local_chat', get_string('settings', 'local_chat'));

if (!$ADMIN->locate('chat')){
	$ADMIN->add('localplugins', new admin_category('chat', get_string('pluginname', 'local_chat')));
	$ADMIN->add('chat', $settings);
}
$settings->add(new admin_setting_heading('local_chat/chat_title', get_string('chat_title', 'local_chat'), ''));

$name = 'local_chat/enabled';
$title = get_string('enabled', 'local_chat');
$description = get_string('enabled_desc', 'local_chat');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
$settings->add($setting);

$name = 'local_chat/oncourse';
$title = get_string('oncourse', 'local_chat');
$description = get_string('oncourse_desc', 'local_chat');
$default = false;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$settings->add($setting);

$name = 'local_chat/inactivity';
$title = get_string('inactivity', 'local_chat');
$description = get_string('inactivity_desc', 'local_chat');
$default = '5';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$settings->add($setting);

$name = 'local_chat/savehistory';
$title = get_string('savehistory', 'local_chat');
$description = get_string('savehistory_desc', 'local_chat');
$default = '7';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$settings->add($setting);

$name = 'local_chat/showroles';
$title = get_string('showroles', 'local_chat');
$description = get_string('showroles_desc', 'local_chat');
$default = true;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$settings->add($setting);

