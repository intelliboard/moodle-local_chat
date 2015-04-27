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

function xmldb_local_chat_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

	// Define table local_chat to be created.
	$table = new xmldb_table('local_chat');

	// Adding fields to table local_chat.
	$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
	$table->add_field('useridfrom', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
	$table->add_field('useridto', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
	$table->add_field('visiblefrom', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
	$table->add_field('visibleto', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
	$table->add_field('message', XMLDB_TYPE_TEXT, '', null, null, null, null);
	$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
	$table->add_field('timeread', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');

	// Adding keys to table local_chat.
	$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

	// Conditionally launch create table for local_chat.
	if (!$dbman->table_exists($table)) {
		$dbman->create_table($table);
	}

    return true;
}
