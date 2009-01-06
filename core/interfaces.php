<?php

/*
* Acora IRC Services
* core/interfaces.php: Interfaces file, keeps everything in one place.
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

/*
* protocol (interface)
*
* this provides a standard for protocol modules.
*/
interface protocol
{
	
	static public function handle_on_server( &$ircdata );
	static public function handle_on_squit( &$ircdata );
	static public function handle_on_connect( &$ircdata, $startup = false );
	static public function handle_nick_change( &$ircdata, $startup = false );
	static public function handle_quit( &$ircdata, $startup = false );
	static public function handle_host_change( &$ircdata );
	static public function handle_mode( &$ircdata );
	static public function handle_ftopic( &$ircdata );
	static public function handle_topic( &$ircdata );
	static public function handle_channel_create( &$ircdata );
	static public function handle_join( &$ircdata );
	static public function handle_part( &$ircdata );
	static public function handle_kick( &$ircdata );
	static public function handle_oper_up( &$ircdata );
	// handle events
	
	static public function ping( &$ircdata );
	static public function get_information( &$ircdata );
	static public function init_server( $name, $pass, $desc, $numeric );
	static public function introduce_client( $nick, $ident, $hostname, $gecos, $enforcer = false );
	static public function remove_client( $nick, $message );
	static public function globops( $nick, $message );
	static public function global_notice( $nick, $mask, $message );
	static public function notice( $nick, $what, $message );
	static public function msg( $nick, $what, $message );
	static public function mode( $nick, $chan, $mode );
	static public function umode( $nick, $user, $mode );
	static public function join_chan( $nick, $chan );
	static public function part_chan( $nick, $chan );
	static public function topic( $nick, $chan, $topic );
	static public function kick( $nick, $user, $chan, $reason = '' );
	static public function sethost( $from, $nick, $host );
	static public function setident( $from, $nick, $ident );
	static public function svsnick( $old_nick, $new_nick, $timestamp );
	static public function kill( $nick, $user, $message );
	static public function gline( $nick, $mask, $duration, $message );
	static public function shutdown( $message, $terminate = false );
	static public function push( $from, $numeric, $nick, $message );
	static public function end_burst();
	static public function send( $command );
	// ircd functions
	
	static public function on_capab_start( &$ircdata );
	static public function on_capab_end( &$ircdata );
	static public function on_timeset( &$ircdata );
	static public function on_start_burst( &$ircdata );
	static public function on_end_burst( &$ircdata );
	static public function on_server( &$ircdata );
	static public function on_squit( &$ircdata );
	static public function on_ping( &$ircdata );
	static public function on_connect( &$ircdata );
	static public function on_quit( &$ircdata );
	static public function on_fhost( &$ircdata );
	static public function on_chan_create( &$ircdata );
	static public function on_join( &$ircdata );
	static public function on_part( &$ircdata );
	static public function on_mode( &$ircdata );
	static public function on_kick( &$ircdata );
	static public function on_topic( &$ircdata );
	static public function on_ftopic( &$ircdata );
	static public function on_oper_up( &$ircdata );
	static public function on_msg( &$ircdata, $where = '' );
	static public function on_notice( &$ircdata, $where = '' );
	static public function on_nick_change( &$ircdata );
	static public function get_nick( &$ircdata, $number );
	static public function parse_users( $chan, &$ircdata, $number );
	// core events
}

/*
* service (interface)
*
* this provides a standard for service classes.
*/
interface service
{
	
	public function main( &$ircdata, $startup = false );
	static public function add_help_fix( $module, $what, $command, &$help );
	static public function add_help( $module, $command, &$help, $oper_help = false );
	static public function get_help( &$nick, &$command );
	static public function add_command( $command, $class, $function );
	static public function get_command( &$nick, &$command );
	// main functions
}

/*
* module (interface)
*
* this provides a standard for modules.
*/
interface module
{
	
	public function modload();
	public function main( &$ircdata, $startup = false );
	// main functions
}

/*
* drive (interface)
*
* this provides a standard for database driver classes.
*/
interface driver
{
	
	static public function ping();
	static public function num_rows( $resource );
	static public function row( $resource );
	static public function fetch( $resource );
	static public function quote( $string );
	static public function optimize();
	static public function select( $table, $what, $where = '', $order = '', $limit = '' );
	static public function update( $table, $what, $where = '' );
	static public function insert( $table, $what );
	static public function delete( $table, $where = '' );
	// main functions
}

// EOF;