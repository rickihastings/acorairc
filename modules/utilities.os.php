<?php

/*
* Acora IRC Services
* modules/utilities.os.php: OperServ utilities module
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

class os_utilities extends module
{
	
	const MOD_VERSION = '0.0.5';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	public function __construct() {}
	// __construct, makes everyone happy.
	
	/*
	* modload (private)
	* 
	* @params
	* void
	*/
	public function modload()
	{
		modules::init_module( 'os_utilities', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_utilities', 'help', operserv::$help->OS_HELP_JUPE_1, 'local_op' );
		operserv::add_help( 'os_utilities', 'help jupe', operserv::$help->OS_HELP_JUPE_ALL, 'local_op' );
		operserv::add_help( 'os_utilities', 'help', operserv::$help->OS_HELP_MODE_1, 'local_op' );
		operserv::add_help( 'os_utilities', 'help mode', operserv::$help->OS_HELP_MODE_ALL, 'local_op' );
		operserv::add_help( 'os_utilities', 'help', operserv::$help->OS_HELP_KICK_1, 'local_op' );
		operserv::add_help( 'os_utilities', 'help kick', operserv::$help->OS_HELP_KICK_ALL, 'local_op' );
		// add the help
		
		operserv::add_command( 'jupe', 'os_utilities', 'jupe_command' );
		operserv::add_command( 'mode', 'os_utilities', 'mode_command' );
		operserv::add_command( 'kick', 'os_utilities', 'kick_command' );
		// add the commands
	}
	
	/*
	* jupe_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function jupe_command( $nick, $ircdata = array() )
	{
		$server = $ircdata[0];
		$numeric = $ircdata[1];
		// grab the ircdata, we only really need the server
		// from here, and numeric.
		
		if ( trim( $server ) == '' || trim( $numeric ) == '' )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'JUPE' ) );
			return false;	
		}
		// is the server value empty?
		// if it is we tell them that it's the invalid syntax
		
		if ( !services::oper_privs( $nick, 'local_op' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// access?
		
		if ( $server == core::$config->server->name || isset( core::$servers[$server] ) )
		{
			core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') tried to jupe ('.$server.')' );
			core::alog( 'jupe_command(): WARNING '.$nick.' tried to jupe '.$server, 'BASIC' );
			// log what we need to log.
		}
		// wtf, someone tried to jupe an existing server
		else
		{
			ircd::$jupes[$server] = $server;
			core::$servers[$server] = $server;
			// add it to the jupes & servers array
			
			ircd::init_server( $server, core::$config->conn->password, 'Juped by '.$nick, $numeric );
			core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') juped ('.$server.')' );
			ircd::wallops( core::$config->operserv->nick, $nick.' juped '.$server );
			
			core::alog( 'jupe_command(): '.$server.' juped', 'BASIC' );
			// log what we need to log.
		}
		// ok, so we're ready to go, jupe it
	}
	
	/*
	* mode_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function mode_command( $nick, $ircdata = array() )
	{
		$channel = core::get_chan( $ircdata, 0 );
		$modes = core::get_data_after( $ircdata, 1 );
		// grab the parameters: nick; channel; reason (optional)
		
		if ( trim( $channel ) == '' )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'MODE' ) );
			return false;	
		}
		// are we missing channel? invalid syntax if so.
		
		if ( !services::oper_privs( $nick, 'local_op' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// access?
		
		if ( !isset( core::$chans[$channel] ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_CHAN_INVALID, array( 'chan' => $channel ) );
			return false;
		}
		// does the channel exist?
		
		mode::set( core::$config->operserv->nick, $channel, $modes );
		ircd::wallops( core::$config->operserv->nick, $nick.' used MODE '.$modes.' on '.$channel );
		// set the mode, globops it.
	}
	
	/*
	* kick_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function kick_command( $nick, $ircdata = array() )
	{
		$unick = $ircdata[1];
		$channel = $ircdata[0];
		$reason = core::get_data_after( $ircdata, 2 );
		// grab the parameters: nick; channel; reason (optional)
		
		if ( trim( $unick ) == '' || trim( $channel ) == '' )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'KICK' ) );
			return false;	
		}
		// are we missing nick and channel? invalid syntax if so.
		
		if ( !services::oper_privs( $nick, 'local_op' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// access?
		
		if ( trim( $reason ) == '' ) $reason = 'Kick command issued by '.$nick;
		// if they haven't suplied a reason let's fill it in.
		
		$unicks = array_change_key_case( core::$nicks, CASE_LOWER );
		$cnicks = array_change_key_case( core::$chans[$channel]['users'], CASE_LOWER );
		if ( isset( $unicks[strtolower( $unick )] ) && isset( core::$chans[$channel] ) && isset( $cnicks[strtolower( $unick )] ) )
		{
			$unick = $unicks[strtolower( $unick )]['nick'];
			ircd::kick( core::$config->operserv->nick, $unick, $channel, $reason );
			core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') used KICK to remove ('.$unick.') from ('.$channel.')' );
		}
		// now we check 3 things, if the user exists, if the channel exists
		// and if the user is even in that channel, if they arn't we just leave it
	}	
}

// EOF;