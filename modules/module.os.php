<?php

/*
* Acora IRC Services
* modules/module.os.php: OperServ modules module
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* This project is licensed under the GNU Public License
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class os_module implements module
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
		modules::init_module( 'os_module', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_module', 'help', &operserv::$help->OS_HELP_MODULES_1 );
		operserv::add_help( 'os_module', 'help', &operserv::$help->OS_HELP_MODLOAD_1 );
		operserv::add_help( 'os_module', 'help', &operserv::$help->OS_HELP_MODUNLOAD_1 );
		operserv::add_help( 'os_module', 'help modlist', &operserv::$help->OS_HELP_MODLIST_ALL );
		operserv::add_help( 'os_module', 'help modload', &operserv::$help->OS_HELP_MODLOAD_ALL );
		operserv::add_help( 'os_module', 'help modunload', &operserv::$help->OS_HELP_MODUNLOAD_ALL );
		// add the help
		
		operserv::add_command( 'modlist', 'os_module', 'modlist_command' );
		operserv::add_command( 'modload', 'os_module', 'modload_command' );
		operserv::add_command( 'modunload', 'os_module', 'modunload_command' );
		// add the commands
	}
	
	/*
	* modlist_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function modlist_command( $nick, $ircdata = array() )
	{
		// we don't even need to listen for any
		// parameters, because its just a straight command
		
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_MODLIST_1 );
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_MODLIST_2 );
		
		$x = 0;
		foreach ( modules::$list as $module => $data )
		{
			$x++;
			$false_name = $data['name'];
				
			if ( !isset( $data['name'][15] ) )
			{
				$y = strlen( $data['name'] );
				for ( $i = $y; $i <= 14; $i++ )
					$false_name .= ' ';
			}
			// this is just a bit of fancy fancy, so everything displays neat
			
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_MODLIST_3, array( 'name' => $false_name, 'version' => $data['version'], 'author' => $data['author'], 'type' => $data['type'], 'extra' => $data['extra'] ) );
		}
		// loop through the currently loaded modules.
	}
	
	/*
	* modload_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	public function modload_command( $nick, $ircdata = array() )
	{
		$module = $ircdata[0];
		// get the module thats been requested.
		
		if ( services::is_root( $nick ) )
		{
			if ( trim( $module ) == '' )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'MODLOAD' ) );
				// wrong syntax
				return false;
			}
		
			if ( isset( modules::$list[$module] ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_MODLOAD_3, array( 'name' => $module ) );
				return false;
			}
		
			if ( !class_exists( $module ) )
			{
				modules::load_module( $name, $details['file'] );
				// load the module 
			}
			else
			{
				if ( !modules::$list[$module]['class'] = new $module() )
				{
					services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_MODLOAD_1, array( 'name' => $module ) );
					core::alog( core::$config->operserv->nick.': unable to load module '.$module );
					core::alog( 'modload_command(): unable to load module '.$module.' (boot error)', 'BASIC' );
					// log what we need to log.
					
					return false;
				}
				// module failed to start
			}
			// load the module, if the class don't exist, include it
			
			modules::$list[$module]['class']->modload();
			// onload handler.
			
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_MODLOAD_2, array( 'name' => $module ) );
			core::alog( core::$config->operserv->nick.': loaded module '.$module );
			ircd::globops( core::$config->operserv->nick, $nick.' loaded module '.$module );
			// let everyone know
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_ACCESS_DENIED );
		}
	}
	
	/*
	* modunload_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	public function modunload_command( $nick, $ircdata = array() )
	{
		$module = $ircdata[0];
		// get the module thats been requested.
		
		if ( services::is_root( $nick ) )
		{
			if ( trim( $module ) == '' )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'MODUNLOAD' ) );
				// wrong syntax
				return false;
			}
		
			if ( !isset( modules::$list[$module] ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_MODUNLOAD_1, array( 'name' => $module ) );
				return false;
			}
			
			if ( modules::$list[$module]['extra'] == 'static' )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_MODUNLOAD_2, array( 'name' => $module ) );
				core::alog( core::$config->operserv->nick.': unable to unload static module '.$module );
				core::alog( 'modunload_command(): unable to unload static module '.$module.' (cannot be unloaded)', 'BASIC' );
				// log what we need to log.
				
				return false;
			}
			
			if ( !class_exists( $module ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_MODUNLOAD_2, array( 'name' => $module ) );
				core::alog( core::$config->operserv->nick.': unable to unload module '.$module );
				core::alog( 'modunload_command(): unable to unload module '.$module.' (not booted)', 'BASIC' );
				// log what we need to log.
				
				return false;
			}
			
			if ( is_callable( array( $module, 'modunload' ), true ) && method_exists( $module, 'modunload' ) )
			{
				modules::$list[$module]['class']->modunload();
			}
			// if the module has an unload method, call it now before we destroy the class.
			
			unset( modules::$list[$module] );
			// unset the module
			
			modules::_unset_docs( $module );
			// unset the modules help docs etc.
			
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_MODUNLOAD_3, array( 'name' => $module ) );
			core::alog( core::$config->operserv->nick.': unloaded module '.$module );
			ircd::globops( core::$config->operserv->nick, $nick.' unloaded module '.$module );
			// let everyone know :D	
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