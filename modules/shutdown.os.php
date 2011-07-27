<?php

/*
* Acora IRC Services
* modules/shutdown.os.php: OperServ shutdown module
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

class os_shutdown extends module
{
	
	const MOD_VERSION = '0.1.3';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	/*
	* modload (private)
	* 
	* @params
	* void
	*/
	static public function modload()
	{
		modules::init_module( 'os_shutdown', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_shutdown', 'help', operserv::$help->OS_HELP_SHUTDOWN_1, true, 'root' );
		operserv::add_help( 'os_shutdown', 'help shutdown', operserv::$help->OS_HELP_SHUTDOWN_ALL, false, 'root' );
		// add the help
		
		operserv::add_command( 'shutdown', 'os_shutdown', 'shutdown_command' );
		// add the shutdown command
		
		operserv::add_help( 'os_shutdown', 'help', operserv::$help->OS_HELP_RESTART_1, true, 'root' );
		operserv::add_help( 'os_shutdown', 'help restart', operserv::$help->OS_HELP_RESTART_ALL, false, 'root' );
		// add the help
			
		operserv::add_command( 'restart', 'os_shutdown', 'restart_command' );
		// add the command
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
		if ( !services::oper_privs( $nick, 'root' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		
		$return_data = self::_shutdown( $input );
		// call list exception
		
		services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		if ( !services::oper_privs( $nick, 'root' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		
		$return_data = self::_restart( $input );
		// call list exception
		
		services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _shutdown (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	*/
	static public function _shutdown( $input )
	{
		$return_data = module::$return_data;
		if ( isset( core::$config->settings->shutdown_message ) || core::$config->settings->shutdown_message != null )
			ircd::global_notice( core::$config->global->nick, '*!*@*', core::$config->settings->shutdown_message );
		// is there a shutdown message?
		
		core::save_logs();
		// save logs.
		
		ircd::shutdown( 'shutdown command from '.$input['hostname'], true );
		// exit the program
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back & log
	}
	
	/*
	* _restart (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	*/
	static public function _restart( $input )
	{
		$return_data = module::$return_data;
		if ( isset( core::$config->settings->shutdown_message ) || core::$config->settings->shutdown_message != null )
			ircd::global_notice( core::$config->global->nick, '*!*@*', core::$config->settings->shutdown_message );
		// is there a shutdown message?
		
		core::save_logs();
		// save logs.
		
		ircd::shutdown( 'shutdown command from '.$input['hostname'], false );
		// exit the server
		
		socket_engine::close( 'core' );
		// close the socket first.
		
		if ( substr( php_uname(), 0, 7 ) != 'Windows' )
		{
			if ( core::$debug )
				system( 'php '.BASEPATH.'/services.php debug' );
			else
				exec( 'php '.BASEPATH.'/services.php > /dev/null &' );
			// reboot if we're running anything but windows
			// if debug we send the output back to the screen, else we send it to /dev/null
		}
		else
		{
			if ( !isset( core::$config->settings->php_dir ) || core::$config->settings->php_dir == '' )
				define( 'PHPDIR', 'C:\php\php.exe' );
			else
				define( 'PHPDIR', core::$config->settings->php_dir );
			// define where the php binary is located.
			
			exec( '@cd '.BASEPATH );
			// cd to the basedir
			
			if ( core::$debug )
				system( '@'.PHPDIR.' services.php debug' );
			else
				exec( '@'.PHPDIR.' services.php' );
			// if we run windows we do a different method of reboot
			// again if we debug we send it to the screen, if not.. we don't
		}
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back & log
	}
}

// EOF;