<?php

/*
* Acora IRC Services
* modules/rehash.os.php: OperServ rehash module
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

class os_rehash extends module
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
		modules::init_module( 'os_rehash', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_rehash', 'help', operserv::$help->OS_HELP_REHASH_1, true, 'root' );
		operserv::add_help( 'os_rehash', 'help rehash', operserv::$help->OS_HELP_REHASH_ALL, false, 'root' );
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
		if ( !services::oper_privs( $nick, 'root' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// access?
	
		$parser = new parser( CONFPATH.'services.conf' );
		// load the parser
		
		$total_modules_exceptions = array();
		$mod_info = array( 'cs' => 'chanserv', 'ns' => 'nickserv', 'os' => 'operserv', 'core' => 'core' );
		
		foreach ( $mod_info as $short => $full )
		{
			$category_name = $full.'_modules';
			
			foreach ( core::$config->$category_name as $id => $module )
				$total_modules[$short.'_'.$module] = array( 'type' => $short, 'file' => $module.'.'.$short.'.php' );
		}
		// merge all the arrays to check that the loaded and excluded modules are all correct
		
		foreach ( modules::$list as $name => $details )
		{
			if ( !isset( $total_modules[$name] ) && $details['extra'] != 'static' )
			{
				if ( is_callable( array( $name, 'modunload' ), true ) && method_exists( $name, 'modunload' ) )
					modules::$list[$name]['class']->modunload();
				// if the module has an unload method, call it now before we destroy the class.
				
				core::alog( core::$config->operserv->nick.': REHASH unloaded module ('.$name.') ('.modules::$list[$name]['version'].') ('.modules::$list[$name]['author'].'/'.modules::$list[$name]['type'].'/'.modules::$list[$name]['extra'].')' );
				ircd::wallops( core::$config->operserv->nick, 'unloaded module '.$name );
				// unset the module
				
				unset( modules::$list[$name] );
				modules::_unset_docs( $name );
				// destory relevant data to the module
				
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
				
				core::alog( core::$config->operserv->nick.': REHASH loaded module ('.$name.') ('.modules::$list[$name]['version'].') ('.modules::$list[$name]['author'].'/'.modules::$list[$name]['type'].'/'.modules::$list[$name]['extra'].')' );
				ircd::wallops( core::$config->operserv->nick, 'loaded module '.$name );
				// load it up
					
				core::alog( 'rehash_command(): '.$name.' loaded from rehash', 'BASIC' );
				// log what we need to log.
					
				modules::$list[$name]['class']->modload();
				// onload handler.
			}
		}
		// go through every module
		// load the ones that are new.
		
		modules::on_rehash();
		
		core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') Successfully performed a REHASH' );
		ircd::wallops( core::$config->operserv->nick, $nick.' performed a REHASH' );
		
		core::alog( 'rehash_command(): sucessful rehash', 'BASIC' );
		// log what we need to log.
	}	
}

// EOF;