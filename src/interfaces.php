<?php

/*
* Acora IRC Services
* src/interfaces.php: Interfaces file, keeps everything in one place.
* 
* Copyright (c) 2009 Acora (http://cia.vc/stats/project/acorairc)
* Written by Ricki, Henry, Shaun and help from others: irc.ircnode.org #acora
*
* This project is licensed under the GNU Public License
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

/*
* constants
*
* all defined constants are below
*/
define( 'CMD_SUCCESS', 'SUCCESS' );
define( 'CMD_FAILCODE', 'FAILCODE' );
define( 'CMD_RESPONSE', 'RESPONSE' );
define( 'CMD_DATA', 'DATA' );
define( 'FLAG_LETTER', 'letter' );
define( 'FLAG_HELP', 'help' );
define( 'FLAG_HAS_PARAM', 'has_param' );
define( 'FLAG_SET_METHOD', 'set_method' );
define( 'FLAG_UNSET_METHOD', 'unset_method' );

/*
* protocol (interface)
*
* this provides a standard for protocol modules.
*/
interface protocol
{
	
	static public function send_burst( $server );
	static public function send_squit( $server );
	static public function init_server( $name, $pass, $desc, $numeric );
	static public function send_version( $ircdata );
	static public function introduce_client( $nick, $ident, $hostname, $gecos, $enforcer = false );
	static public function remove_client( $nick, $message );
	static public function wallops( $nick, $message );
	static public function global_notice( $nick, $mask, $message );
	static public function notice( $nick, $what, $message );
	static public function msg( $nick, $what, $message );
	static public function mode( $nick, $chan, $mode );
	static public function umode( $nick, $user, $mode );
	static public function join_chan( $nick, $chan );
	static public function part_chan( $nick, $chan );
	static public function topic( $nick, $chan, $topic );
	static public function kick( $nick, $user, $chan, $reason = '' );
	static public function invite( $nick, $user, $chan );
	static public function sethost( $from, $nick, $host );
	static public function setident( $from, $nick, $ident );
	static public function svsnick( $old_nick, $new_nick, $timestamp );
	static public function kill( $nick, $user, $message );
	static public function global_ban( $nick, $user, $duration, $message );
	static public function shutdown( $message, $terminate = false );
	static public function push( $from, $numeric, $nick, $message );
	static public function set_registered_mode( $nick, $channel );
	// ircd functions
	
	static public function on_capab( $ircdata );
	static public function on_start_burst( $ircdata );
	static public function on_server( $ircdata );
	static public function on_squit( $ircdata );
	static public function on_ping( $ircdata );
	static public function on_connect( $ircdata );
	static public function on_quit( $ircdata );
	static public function on_chan_create( $ircdata );
	static public function on_join( $ircdata );
	static public function on_part( $ircdata );
	static public function on_mode( $ircdata );
	static public function on_kick( $ircdata );
	static public function on_topic( $ircdata );
	static public function on_umode( $ircdata );
	static public function on_msg( $ircdata );
	static public function on_notice( $ircdata );
	static public function on_nick_change( $ircdata );
	static public function on_error( $ircdata );
	// core events
}

/*
* service (interface)
*
* this provides a standard for service classes.
*/
abstract class service
{
	
	static public $flag_data = array(
		FLAG_NAME		=> '',
		FLAG_HELP		=> '',
		FLAG_HAS_PARAM		=> false,
		FLAG_SET_METHOD		=> null,
		FLAG_UNSET_METHOD	=> null,
	);
	// setup an array for flag data

	abstract static public function add_help_fix( $module, $what, $command, $help );
	abstract static public function add_help( $module, $command, $help, $privs = '' );
	abstract static public function get_help( $nick, $command );
	abstract static public function add_command( $command, $class, $function );
	abstract static public function get_command( $nick, $command );
	// main functions
}

/*
* module (abstract class)
*
* this provides a standard for modules.
*/
abstract class module
{
	
	static public $return_data = array(
		CMD_SUCCESS		=> false,
		CMD_FAILCODE	=> null,
		CMD_RESPONSE	=> array(),
		CMD_DATA		=> array(),
	);
	// setup our return data
	
	abstract static public function modload();
	// main functions
}

/*
* drive (interface)
*
* this provides a standard for database driver classes.
*/
interface driver
{
	
	public function ping();
	public function num_rows( &$resource );
	public function row( &$resource );
	public function fetch( &$resource );
	public function quote( $string );
	public function select( $table, $what, $where = '', $order = '', $limit = '' );
	public function update( $table, $what, $where = '' );
	public function insert( $table, $what );
	public function delete( $table, $where = '' );
	// main functions
}

// EOF;
