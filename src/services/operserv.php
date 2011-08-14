<?php

/*
* Acora IRC Services
* src/services/operserv.php: OperServ initiation class
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

class operserv extends service
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
	static public $session_rows = array();
	// help

	/*
	* __construct
	* 
	* @params
	* void
	*/
	public function __construct()
	{
		modules::init_service( 'operserv', self::SERV_VERSION, self::SERV_AUTHOR );
		// these are standard in service constructors
	
		require( BASEPATH.'/lang/'.core::$config->server->lang.'/operserv.php' );
		self::$help = $help;
		// load the help file
		
		if ( isset( core::$config->operserv ) )
		{
			self::$nick = core::$config->operserv->nick = ( core::$config->operserv->nick != '' ) ? core::$config->operserv->nick : 'OperServ';
			self::$user = core::$config->operserv->user = ( core::$config->operserv->user != '' ) ? core::$config->operserv->user : 'operserv';
			self::$real = core::$config->operserv->real = ( core::$config->operserv->real != '' ) ? core::$config->operserv->real : 'Operator Services';
			self::$host = core::$config->operserv->host = ( core::$config->operserv->host != '' ) ? core::$config->operserv->host : core::$config->conn->server;
			// check if nickname and stuff is specified, if not use defaults
		}
		// check if nickserv is enabled
		
		ircd::introduce_client( core::$config->operserv->nick, core::$config->operserv->user, core::$config->operserv->host, core::$config->operserv->real );
		// connect the bot
		
		foreach ( core::$config->operserv_modules as $id => $module )
			modules::load_module( 'os_'.$module, $module.'.os.php' );
		// load the operserv modules
		
		if ( core::$config->operserv->override )
		{
			self::add_help( 'operserv', 'help', self::$help->OS_HELP_OVERRIDE_1, 'root' );
			self::add_help( 'operserv', 'help override', self::$help->OS_HELP_OVERRIDE_ALL, 'root' );
			// add the help
			
			self::add_command( 'override', 'operserv', 'override_command' );
			// add the override command
		}
		// if override is set to true
		
		$query = database::select( 'sessions', array( 'nick', 'ip_address', 'hostmask', 'description', 'limit', 'time', 'expire', 'akill' ) );
		while ( $session = database::fetch( $query ) )
			self::$session_rows[] = $session;
	}
	
	/*
	* on_rehash (event)
	* 
	* @params
	* void
	*/
	static public function on_rehash()
	{
		if ( isset( core::$config->operserv ) )
		{
			core::$config->operserv->nick = ( core::$config->operserv->nick != '' ) ? core::$config->operserv->nick : 'OperServ';
			core::$config->operserv->user = ( core::$config->operserv->user != '' ) ? core::$config->operserv->user : 'operserv';
			core::$config->operserv->real = ( core::$config->operserv->real != '' ) ? core::$config->operserv->real : 'Operator Services';
			core::$config->operserv->host = ( core::$config->operserv->host != '' ) ? core::$config->operserv->host : core::$config->conn->server;
			// check if nickname and stuff is specified, if not use defaults
			
			if ( self::$nick != core::$config->operserv->nick || self::$user != core::$config->operserv->user || self::$real != core::$config->operserv->real || self::$host != core::$config->operserv->host )
			{
				ircd::remove_client( self::$nick, 'Rehashing' );
				ircd::introduce_client( core::$config->operserv->nick, core::$config->operserv->user, core::$config->operserv->host, core::$config->operserv->real );
			}
			// check for changes and reintroduce the client
			
			self::$nick = core::$config->operserv->nick;
			self::$user = core::$config->operserv->user;
			self::$real = core::$config->operserv->real;
			self::$host = core::$config->operserv->host;
		}
		// check if operserv is enabled
	}
	
	/*
	* override_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function override_command( $nick, $ircdata = array() )
	{
		$mode = strtolower( $ircdata[0] );
		
		if ( services::has_privs( $nick, 'root' ) )
		{
			if ( trim( $mode ) == '' || !in_array( $mode, array( 'on', 'off' ) ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'OVERRIDE' ) );
				return false;
			}
			// is the format correct?
			
			if ( $mode == 'on' )
			{
				if ( core::$nicks[$nick]['override'] )
				{
					services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_OVERRIDE_IS_ON );
					return false;
				}
				// override is already on..
				
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_OVERRIDE_ON );
				core::alog( 'override_command(): WARNING: '.$nick.' is now using override mode.', 'BASIC' );
				core::alog( 'WARNING: '.$nick.' has turned OVERRIDE mode ON' );
				ircd::wallops( core::$config->operserv->nick, 'WARNING: '.core::$config->operserv->nick, $nick.' has turned OVERRIDE mode ON' );
				// log and stuff
				
				core::$nicks[$nick]['override'] = true;
				return false;
			}
			// set override on
			
			if ( $mode == 'off' )
			{
				if ( !core::$nicks[$nick]['override'] )
				{
					services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_OVERRIDE_IS_OFF );
					return false;
				}
				// override isnt even on..
				
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_OVERRIDE_OFF );
				core::alog( 'override_command(): '.$nick.' has turned override mode off.', 'BASIC' );
				core::alog( $nick.' has turned OVERRIDE mode OFF' );
				ircd::wallops( core::$config->operserv->nick, $nick.' has turned OVERRIDE mode OFF' );
				// log and stuff
				
				core::$nicks[$nick]['override'] = false;
				return false;
			}
			// set override off
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );	
		}
		// are they root?
	}
	
	/*
	* on_msg (event_hook)
	*/
	static public function on_msg( $nick, $target, $msg )
	{
		if ( $target != core::$config->operserv->nick )
			return false;
		
		$command = substr( $msg, 1 );
		// convert to lower case because all the tingy wags are in lowercase
		
		core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).'): '.$command );
		// logchan it
		
		if ( core::$nicks[$nick]['ircop'] && core::$nicks[$nick]['identified'] )
			commands::get_command( 'operserv', $nick, $command );
		else
			services::communicate( core::$config->operserv->nick, $nick, self::$help->OS_DENIED_ACCESS );
		// theyre an oper.
	}
	
	/*
	* on_oper_up (event_hook)
	*/
	static public function on_oper_up( $nick )
	{
		core::alog( core::$config->operserv->nick.': OPER UP from ('.core::get_full_hostname( $nick ).')' );
		// log the oper up.
	}
}

// EOF;
