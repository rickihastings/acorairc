<?php

/*
* Acora IRC Services
* src/services/nickserv.php: NickServ initiation class
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

class nickserv extends service
{
	
	const SERV_VERSION = '0.1.1';
	const SERV_AUTHOR = 'Acora';
	// service info
	
	static public $nick;
	static public $user;
	static public $real;
	static public $host;
	// user vars
	
	static public $help;
	static public $flags;
	// help

	static public $nick_q = array();
	// store the last queries in an internal array, cause i've
	// noticed the same query is being called like 5 times cause the data
	// is used in 5 different places.
	
	/*
	* __construct
	* 
	* @params
	* void
	*/
	public function __construct()
	{
		modules::init_service( 'nickserv', self::SERV_VERSION, self::SERV_AUTHOR );
		// these are standard in service constructors
	
		require( BASEPATH.'/lang/'.core::$config->server->lang.'/nickserv.php' );
		self::$help = $help;
		
		if ( isset( core::$config->nickserv ) )
		{
			self::$nick = core::$config->nickserv->nick = ( core::$config->nickserv->nick != '' ) ? core::$config->nickserv->nick : 'NickServ';
			self::$user = core::$config->nickserv->user = ( core::$config->nickserv->user != '' ) ? core::$config->nickserv->user : 'nickserv';
			self::$real = core::$config->nickserv->real = ( core::$config->nickserv->real != '' ) ? core::$config->nickserv->real : 'Nickname Services';
			self::$host = core::$config->nickserv->host = ( core::$config->nickserv->host != '' ) ? core::$config->nickserv->host : core::$config->conn->server;
			// check if nickname and stuff is specified, if not use defaults
		}
		// check if nickserv is enabled
		
		ircd::introduce_client( core::$config->nickserv->nick, core::$config->nickserv->user, core::$config->nickserv->host, core::$config->nickserv->real );
		// connect the bot
		
		foreach ( core::$config->nickserv_modules as $id => $module )
			modules::load_module( 'ns_'.$module, $module.'.ns.php' );
		// load the nickserv modules
		
		timer::add( array( 'nickserv', 'check_expire', array() ), 300, 0 );
		// set a timer!
	}
	
	/*
	* on_rehash (event)
	* 
	* @params
	* void
	*/
	static public function on_rehash()
	{
		if ( isset( core::$config->nickserv ) )
		{
			core::$config->nickserv->nick = ( core::$config->nickserv->nick != '' ) ? core::$config->nickserv->nick : 'Nickserv';
			core::$config->nickserv->user = ( core::$config->nickserv->user != '' ) ? core::$config->nickserv->user : 'nickserv';
			core::$config->nickserv->real = ( core::$config->nickserv->real != '' ) ? core::$config->nickserv->real : 'Nickname Services';
			core::$config->nickserv->host = ( core::$config->nickserv->host != '' ) ? core::$config->nickserv->host : core::$config->conn->server;
			// check if nickname and stuff is specified, if not use defaults
			
			if ( self::$nick != core::$config->nickserv->nick || self::$user != core::$config->nickserv->user || self::$real != core::$config->nickserv->real || self::$host != core::$config->nickserv->host )
			{
				ircd::remove_client( self::$nick, 'Rehashing' );
				ircd::introduce_client( core::$config->nickserv->nick, core::$config->nickserv->user, core::$config->nickserv->host, core::$config->nickserv->real );
			}
			// check for changes and reintroduce the client
			
			self::$nick = core::$config->nickserv->nick;
			self::$user = core::$config->nickserv->user;
			self::$real = core::$config->nickserv->real;
			self::$host = core::$config->nickserv->host;
		}
		// check if nickserv is enabled
	}
	
	/*
	* on_burst_connect (event hook)
	*/
	static public function on_burst_connect( $connect_data )
	{
		self::on_connect( $connect_data );
	}
	
	/*
	* on_connect (event hook)
	*/
	static public function on_connect( $connect_data )
	{
		$user = services::user_exists( $connect_data['nick'], false, array( 'id', 'display', 'pass', 'salt', 'timestamp', 'last_timestamp', 'last_hostmask', 'vhost', 'identified', 'validated', 'real_user', 'suspended', 'suspend_reason' ) );
		self::$nick_q[$connect_data['nick']] = $user;
	}
	
	/*
	* on_msg (event_hook)
	*/
	static public function on_msg( $nick, $target, $msg )
	{
		if ( $target != core::$config->nickserv->nick )
			return false;
		
		$command = substr( $msg, 1 );
		// convert to lower case because all the tingy wags are in lowercase
		
		self::get_command( $nick, $command );
	}
	
	/*
	* check_expire (private)
	* 
	* @params
	* void
	*/
	static public function check_expire()
	{
		if ( core::$config->nickserv->expire == 0 )
			return false;
		// skip nicknames if config is set to no expire.
		
		$expiry_time = core::$config->nickserv->expire * 86400;
		$check_time = core::$network_time - $expiry_time;
		// set up our times.
		
		$nick_q = database::select( 'users', array( 'id', 'display', 'last_timestamp' ), array( 'last_timestamp', '!=', '0', 'AND', 'last_timestamp', '<', $check_time ) );
		
		if ( database::num_rows( $nick_q ) == 0 )
			return false;
		// no expiring nicknames
		
		while ( $nick = database::fetch( $nick_q ) )
		{
			// Mikeh gets most of the credit for helping
			// me code this function
			
			database::delete( 'users', array( 'display', '=', $nick->display ) );
			database::delete( 'users_flags', array( 'nickname', '=', $user->display ) );
			// delete the users record
				
			database::delete( 'chans_levels', array( 'target', '=', $nick->display ) );
			// also delete this users channel access.
				
			core::alog( core::$config->nickserv->nick.': '.$nick->display.' has expired. Last used on '.date( 'F j, Y, g:i a', $nick->last_timestamp ) );
			// logchan it
				
			if ( isset( core::$nicks[$nick->display] ) )
				ircd::on_user_logout( $nick->display );
			// if the nick is being used unregister it, even though it shouldn't be, just to be safe.
		}
		// loop through all expiring nicks.
	}
	
	/*
	* check_flags (private)
	* 
	* @params
	* $nickname - The nickname to check.
	* $flags - an array of flags to check for.
	*/
	static public function check_flags( $nick, $flags )
	{
		$nick_flags_q = database::select( 'users_flags', array( 'id', 'nickname', 'flags' ), array( 'nickname', '=', $nick ) );
		$nick_flags = database::fetch( $nick_flags_q );
		// get our flags records
		
		foreach ( $flags as $flag )
		{
			if ( strpos( $nick_flags->flags, $flag ) !== false )
				return true;
			// hurrah, we've found a match!
		}
		// loop through the flags, if we find a match, return true
		
		return false;
	}
	
	/*
	* get_flags (private)
	* 
	* @params
	* $nickname - The nickname to check.
	* $flag - a flag value to grab, eg. modelock (m)
	*/
	static public function get_flags( $nick, $flag )
	{
		if ( $flag == 'e' )
			$param_field = 'email';
		elseif ( $flag == 'u' )
			$param_field = 'url';
		elseif ( $flag == 's' )
			$param_field = 'secured_time';
		else
			return false;
		// translate. some craq.
		
		$nick_flags_q = database::select( 'users_flags', array( 'id', 'nickname', 'flags', $param_field ), array( 'nickname', '=', $nick ) );
		$nick_flags = database::fetch( $nick_flags_q );
		// get our flags records
		
		return $nick_flags->$param_field;
	}
	
	/*
	* add_help_prefix
	* 
	* @params
	* $command - The command to add a prefix for.
	* $module - The name of the module.
	* $help - The prefix to add.
	*/
	static public function add_help_fix( $module, $what, $command, $help )
	{
		commands::add_help_fix( 'nickserv', $module, $what, $command, $help );
	}
	
	/*
	* add_help
	* 
	* @params
	* $command - The command to hook the array to.
	* $module - The name of the module.
	* $help - The array to hook.
	*/
	static public function add_help( $module, $command, $help, $reorder = false, $privs = '' )
	{
		commands::add_help( 'nickserv', $module, $command, $help, $reorder, $privs );
	}
	
	/*
	* get_help
	* 
	* @params
	* $nick - Who to send the help too?
	* $command - The command to get the help for.
	*/
	static public function get_help( $nick, $command )
	{
		commands::get_help( 'nickserv', $nick, $command );
	}
	
	/*
	* add_command
	* 
	* @params
	* $command - The command to hook to
	* $class - The class the callback is in
	* $function - The function name of the callback
	*/
	static public function add_command( $command, $class, $function )
	{
		commands::add_command( 'nickserv', $command, $class, $function );
	}
	
	/*
	* get_command
	* 
	* @params
	* $nick - The nick requesting the command
	* $command - The command to hook to
	*/
	static public function get_command( $nick, $command )
	{
		commands::get_command( 'nickserv', $nick, $command );
	}
}

// EOF;
