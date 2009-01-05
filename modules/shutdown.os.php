<?php

/*
* Acora IRC Services
* modules/shutdown.os.php: OperServ shutdown module
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class os_shutdown implements module
{
	
	const MOD_VERSION = '0.0.2';
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
		modules::init_module( 'os_shutdown', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_shutdown', 'help', &operserv::$help->OS_HELP_SHUTDOWN_1 );
		operserv::add_help( 'os_shutdown', 'help shutdown', &operserv::$help->OS_HELP_SHUTDOWN_ALL );
		// add the help
		
		operserv::add_command( 'shutdown', 'os_shutdown', 'shutdown_command' );
		// add the shutdown command
		
		if ( substr( php_uname(), 0, 7 ) != 'Windows' )
		{
			operserv::add_help( 'os_shutdown', 'help', &operserv::$help->OS_HELP_RESTART_1 );
			operserv::add_help( 'os_shutdown', 'help restart', &operserv::$help->OS_HELP_RESTART_ALL );
			// add the help
			
			operserv::add_command( 'restart', 'os_shutdown', 'restart_command' );
			// add the command
		}
		// if we're running anything BUT windows, add the restart command
		// might sound ludacris, but windows is just shit, and it simply
		// doesn't want to work.
	}
	
	/*
	* shutdown_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function shutdown_command( $nick, $ircdata = array() )
	{
		// we don't even need to listen for any
		// parameters, because its just a straight command
		
		if ( services::is_root( $nick ) )
		{
			if ( isset( core::$config->settings->shutdown_message ) || core::$config->settings->shutdown_message != null )
			{
				ircd::global_notice( core::$config->global->nick, core::$config->settings->shutdown_message );
			}
			// is there a shutdown message?
			
			core::save_logs();
			// save logs.
			
			ircd::shutdown( 'shutdown command from '.$nick, true );
			// exit the program
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_ACCESS_DENIED );
		}
	}
	
	/*
	* restart_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function restart_command( $nick, $ircdata = array() )
	{
		// we don't even need to listen for any
		// parameters, because its just a straight command
		
		if ( services::is_root( $nick ) )
		{
			if ( isset( core::$config->settings->shutdown_message ) || core::$config->settings->shutdown_message != null )
			{
				ircd::global_notice( core::$config->global->nick, core::$config->settings->shutdown_message );
			}
			// is there a shutdown message?
			
			core::save_logs();
			// save logs.
			
			ircd::shutdown( 'shutdown command from '.$nick, false );
			// exit the server
			
			if ( core::$debug )
				exec( 'php -q '.BASEPATH.'/services.php debug' );
			else
				exec( 'php -q '.BASEPATH.'/services.php > /dev/null &' );
			// reboot
			
			exit;
			// exit the program
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_ACCESS_DENIED );
		}
	}
	
	/*
	* main (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	public function main( &$ircdata, $startup = false )
	{
		return true;
		// we don't need to listen for anything in this module
		// so we just return true immediatly.
	}	
}

// EOF;