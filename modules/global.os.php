<?php

/*
* Acora IRC Services
* modules/global.os.php: OperServ global module
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

class os_global extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'INVALID_GLOBAL'	=> 2,
	);
	// return codes
	
	/*
	* modload (private)
	* 
	* @params
	* void
	*/
	static public function modload()
	{
		modules::init_module( 'os_global', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
	
		if ( isset( core::$config->global ) )
			ircd::introduce_client( core::$config->global->nick, core::$config->global->user, core::$config->global->host, core::$config->global->real );
		// i decided to change global from a core feature into a module based feature
		// seen as though global won't do anything really without this module it's going here
		
		operserv::add_help( 'os_global', 'help', operserv::$help->OS_HELP_GLOBAL_1, true, 'global_op' );
		operserv::add_help( 'os_global', 'help global', operserv::$help->OS_HELP_GLOBAL_ALL, false, 'global_op' );
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
		
		core::alog( 'Now sending log messages to '.core::$config->settings->logchan );
		// tell the chan we're logging shit.
	}
	
	/*
	* modunload (private)
	* 
	* @params
	* void
	*/
	static public function modunload()
	{
		if ( isset( core::$config->global->nick ) || core::$config->global->nick != null )
			ircd::remove_client( core::$config->global->nick, 'module unloaded' );
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
		if ( !services::oper_privs( $nick, 'global_op' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// access?
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );		
		$return_data = self::_global_message( $input, $nick, $ircdata[0], core::get_data_after( $ircdata, 1 ) );
		// throw to a sub command
		
		services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _global_message (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $mask - The mask to send the message to
	* $message - The actual message
	*/
	static public function _global_message( $input, $nick, $mask, $message )
	{
		$return_data = module::$return_data;
	
		if ( trim( $mask ) == '' || trim( $message ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array ( 'help' => 'GLOBAL' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// are they sending a message?
		
		if ( strpos( $mask, '@' ) === false )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_GLOBAL_INVALID );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_GLOBAL;
			return $return_data;	
		}
		// is the mask valid?
		
		if ( strpos( $mask, '!' ) === false )
			$mask = '*!'.$mask;
		// prepend the *! to the mask
		
		if ( core::$config->global->nick_on_global )
			ircd::global_notice( core::$config->global->nick, $mask, '['.$nick.'] '.$message );
		else
			ircd::global_notice( core::$config->global->nick, $mask, $message );
		// send the message!!
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* on_connect (event hook)
	*/
	static public function on_connect( $connect_data )
	{
		if ( core::$config->settings->loglevel == 'server' || core::$config->settings->loglevel == 'all' )
		{
			$nick = $connect_data['nick'];
			// get nick
			
			ircd::notice( core::$config->global->nick, $nick, 'Services are currently running in debug mode, please be careful when sending passwords.' );
			// give them a quick notice that people can see their passwords.
		}
	}
	
	/*
	* on_chan_create (event hook)
	*/
	static public function on_chan_create( $chan )
	{
		if ( core::$config->settings->logchan == $chan )
			self::join_logchan();
		// join global to the logchan.	
	}
}

// EOF;