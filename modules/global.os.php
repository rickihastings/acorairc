<?php

/*
* Acora IRC Services
* modules/global.os.php: OperServ global module
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class os_global implements module
{
	
	const MOD_VERSION = '0.0.3';
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
		if ( isset( core::$config->global ) )
		{
			ircd::introduce_client( core::$config->global->nick, core::$config->global->user, core::$config->global->host, core::$config->global->real );
		}
		// introduce global.
		
		if ( isset( core::$config->settings->logchan ) || core::$config->settings->logchan != null )
		{
			timer::add( array( 'os_global', 'join_logchan', array() ), 1, 1 );
			// because it wont let us do it here, we do it in the next iteration >.>
		}
		// logchan?
		
		// i decided to change global from a core feature into a module based feature
		// seen as though global won't do anything really without this module it's going here
		
		modules::init_module( 'os_global', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_global', 'help', &operserv::$help->OS_HELP_GLOBAL_1 );
		operserv::add_help( 'os_global', 'help global', &operserv::$help->OS_HELP_GLOBAL_ALL );
		// add the help
		
		operserv::add_command( 'global', 'os_global', 'global_command' );
		// add the command
	}
	
	/*
	* join_logchan (timer)
	* 
	* @params
	* void
	*/
	static public function join_logchan()
	{
		ircd::join_chan( core::$config->global->nick, core::$config->settings->logchan );
		// join the logchan
		
		if ( ircd::$protect )
			ircd::mode( core::$config->global->nick, core::$config->settings->logchan, '+ao '.core::$config->global->nick.' '.core::$config->global->nick );
		// +ao its self.
		else
			ircd::mode( core::$config->global->nick, core::$config->settings->logchan, '+o '.core::$config->global->nick );
		// +o its self.
		
		core::alog( 'Now sending log messages to '.core::$config->settings->logchan );
		// tell the chan we're logging shit.
	}
	
	/*
	* modunload (private)
	* 
	* @params
	* void
	*/
	public function modunload()
	{
		if ( isset( core::$config->global->nick ) || core::$config->global->nick != null )
		{
			ircd::remove_client( core::$config->global->nick, 'module unloaded' );
		}
		// remove our global client.
	}
	
	/*
	* global_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function global_command( $nick, $ircdata = array() )
	{
		$message = core::get_data_after( &$ircdata, 0 );
		
		if ( trim( $message ) == '' )
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX );
			return false;
		}
		// are they sending a message?
		
		if ( core::$config->global->nick_on_global )
		{
			ircd::global_notice( core::$config->global->nick, '['.$nick.'] '.$message );
		}
		else
		{
			ircd::global_notice( core::$config->global->nick, $message );
		}
		// send the message!!
		
		ircd::globops( core::$config->operserv->nick, $nick.' just used GLOBAL command.' );
		// we globop the command being used.
	}
	
	/*
	* main (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	public function main( &$ircdata, $startup = false )
	{
		if ( ( core::$config->settings->loglevel == 'server' || core::$config->settings->loglevel == 'all' ) && ircd::on_connect( &$ircdata ) )
		{
			$nick = core::get_nick( &$ircdata, ( core::$config->server->ircd == 'inspircd12' ) ? 4 : 3 );
			// get nick
			
			ircd::notice( core::$config->global->nick, $nick, 'Services are currently running in debug mode, please be careful when sending passwords.' );
			// give them a quick notice that people can see their passwords.
		}
	}
}

// EOF;