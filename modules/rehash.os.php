<?php

/*
* Acora IRC Services
* modules/rehash.os.php: OperServ rehash module
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class os_rehash implements module
{
	
	const MOD_VERSION = '0.0.1';
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
		modules::init_module( 'os_rehash', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_rehash', 'help', &operserv::$help->OS_HELP_REHASH_1 );
		operserv::add_help( 'os_rehash', 'help rehash', &operserv::$help->OS_HELP_REHASH_ALL );
		// add the help
		
		operserv::add_command( 'rehash', 'os_rehash', 'rehash_command' );
		// add the commands
	}
	
		/*
	* rehash_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function rehash_command( $nick, $ircdata = array() )
	{
		$parser = new parser( CONFPATH.'services.conf' );
		// load the parser
		
		$total_modules_exceptions = array();
		$mod_info = array( 'cs' => 'chanserv', 'ns' => 'nickserv', 'os' => 'operserv', 'core' => 'core' );
		
		foreach ( $mod_info as $short => $full )
		{
			$category_name = $full.'_modules';
			
			foreach ( core::$config->$category_name as $id => $module )
			{
				$total_modules[$short.'_'.$module] = array( 'type' => $short, 'file' => $module.'.'.$short.'.php' );
			}
		}
		// merge all the arrays to check that the loaded and excluded modules are all correct
		
		foreach ( modules::$list as $name => $details )
		{
			if ( !isset( $total_modules[$name] ) && $details['extra'] != 'static' )
			{
				if ( is_callable( array( $name, 'modunload' ), true ) && method_exists( $name, 'modunload' ) )
				{
					modules::$list[$name]['class']->modunload();
				}
				// if the module has an unload method, call it now before we destroy the class.
				
				unset( modules::$list[$name] );
				modules::_unset_docs( $name );
				// destory relevant data to the module
				
				core::alog( core::$config->operserv->nick.': unloaded module '.$name );
				ircd::globops( core::$config->operserv->nick, 'unloaded module '.$name );
				// unset the module
				
				core::alog( 'rehash_command(): '.$name.' unloaded from rehash', 'BASIC' );
				// log what we need to log.
			}
			// the module is loaded and should be unloaded
		}
		// go through each set module and unset the now exempt modules
		
		foreach ( $total_modules as $name => $details )
		{
			if ( !isset( modules::$list[$name] ) )
			{
				if ( !class_exists( $name ) )
				{
					modules::load_module( $name, $details['file'] );
					// load the module 
				}
				else
				{
					if ( !modules::$list[$name]['class'] = new $name() )
					{
						core::alog( 'load_module(): unable to start: '.$name.' (boot error)', 'BASIC' );
						return false;
					}
				}
				
				core::alog( core::$config->operserv->nick.': loaded module '.$name );
				ircd::globops( core::$config->operserv->nick, 'loaded module '.$name );
				// load it up
					
				core::alog( 'rehash_command(): '.$name.' loaded from rehash', 'BASIC' );
				// log what we need to log.
					
				modules::$list[$name]['class']->modload();
				// onload handler.
			}
		}
		// go through every module
		// load the ones that are new.
		
		core::alog( core::$config->operserv->nick.': Successfully reloaded configuration.' );
		ircd::globops( core::$config->operserv->nick, $nick.' performed a REHASH' );
		
		core::alog( 'rehash_command(): sucessful rehash', 'BASIC' );
		// log what we need to log.
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