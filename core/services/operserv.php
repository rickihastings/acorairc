<?php

/*
* Acora IRC Services
* core/services/operserv.php: OperServ initiation class
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
	
	const SERV_VERSION = '0.1.0';
	const SERV_AUTHOR = 'Acora';
	// service info
	
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
			ircd::introduce_client( core::$config->operserv->nick, core::$config->operserv->user, core::$config->operserv->host, core::$config->operserv->real );
		else
			return;
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
			self::get_command( $nick, $command );
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
		commands::add_help_fix( 'operserv', $module, $what, $command, $help );
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
		commands::add_help( 'operserv', $module, $command, $help, $reorder, $privs );
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
		commands::get_help( 'operserv', $nick, $command );
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
		commands::add_command( 'operserv', $command, $class, $function );
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
		commands::get_command( 'operserv', $nick, $command );
	}
}

// EOF;