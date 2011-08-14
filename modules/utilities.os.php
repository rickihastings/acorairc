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
	
	const MOD_VERSION = '0.1.5';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'SERVER_EXISTS'		=> 2,
		'CHAN_INVALID'		=> 3,
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
		modules::init_module( __CLASS__, self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		commands::add_help( 'operserv', 'os_utilities', 'help', operserv::$help->OS_HELP_JUPE_1, true, 'local_op' );
		commands::add_help( 'operserv', 'os_utilities', 'help jupe', operserv::$help->OS_HELP_JUPE_ALL, false, 'local_op' );
		commands::add_help( 'operserv', 'os_utilities', 'help', operserv::$help->OS_HELP_MODE_1, true, 'local_op' );
		commands::add_help( 'operserv', 'os_utilities', 'help mode', operserv::$help->OS_HELP_MODE_ALL, false, 'local_op' );
		commands::add_help( 'operserv', 'os_utilities', 'help', operserv::$help->OS_HELP_KICK_1, true, 'local_op' );
		commands::add_help( 'operserv', 'os_utilities', 'help kick', operserv::$help->OS_HELP_KICK_ALL, false, 'local_op' );
		// add the help
		
		commands::add_command( 'operserv', 'jupe', 'os_utilities', 'jupe_command' );
		commands::add_command( 'operserv', 'mode', 'os_utilities', 'mode_command' );
		commands::add_command( 'operserv', 'kick', 'os_utilities', 'kick_command' );
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
		if ( !services::oper_privs( $nick, 'local_op' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// access?
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_jupe_server( $input, $nick, $ircdata[0], $ircdata[1] );
		// call _unsuspend_nick
		
		services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		if ( !services::oper_privs( $nick, 'local_op' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// access?
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_mode( $input, $nick, $ircdata[0], core::get_data_after( $ircdata, 1 ) );
		// call _unsuspend_nick
		
		services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		if ( !services::oper_privs( $nick, 'local_op' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// access?
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_kick( $input, $nick, $ircdata[1], $ircdata[0], core::get_data_after( $ircdata, 2 ) );
		// call _unsuspend_nick
		
		services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _jupe_server (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $server - the server name to jupe
	* $numeric - the server numeric
	*/
	public function _jupe_server( $input, $nick, $server, $numeric )
	{
		$return_data = module::$return_data;
	
		if ( trim( $server ) == '' || trim( $numeric ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'JUPE' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// is the server value empty?
		// if it is we tell them that it's the invalid syntax
		
		if ( $server == core::$config->server->name || isset( core::$servers[$server] ) )
		{
			core::alog( core::$config->operserv->nick.': ('.$input['hostname'].') ('.$input['account'].') tried to jupe ('.$server.')' );
			core::alog( 'jupe_command(): WARNING '.$nick.' tried to jupe '.$server, 'BASIC' );
			// log what we need to log.
			
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_JUPE_1, array( 'server' => $server ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->SERVER_EXISTS;
			return $return_data;
		}
		// wtf, someone tried to jupe an existing server
		
		ircd::$jupes[$server] = $server;
		core::$servers[$server] = $server;
		// add it to the jupes & servers array
		
		ircd::init_server( $server, core::$config->conn->password, 'Juped by '.$nick, $numeric );
		core::alog( core::$config->operserv->nick.': ('.$input['hostname'].') ('.$input['account'].') juped ('.$server.')' );
		core::alog( 'jupe_command(): '.$server.' juped', 'BASIC' );
		// log what we need to log.
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_JUPE_2, array( 'server' => $server ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// log this and return.
	}
	
	/*
	* _mode (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $channel - The channel to set modes on
	* $modes - The modes to set, should be a string.
	*/
	public function _mode( $input, $nick, $channel, $modes )
	{
		$return_data = module::$return_data;
	
		if ( trim( $channel ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'MODE' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// are we missing channel? invalid syntax if so.
		
		if ( !isset( core::$chans[$channel] ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_CHAN_INVALID, array( 'chan' => $channel ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->CHAN_INVALID;
			return $return_data;
		}
		// does the channel exist?
		
		mode::set( core::$config->operserv->nick, $channel, $modes );
		// set the mode, globops it.
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// log this and return.
	}
	
	/*
	* _kick (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $unick - The user to kick
	* $channel - The channel to kick the user from
	* $reason - The reason to use
	*/
	public function _kick( $input, $nick, $unick, $channel, $reason )
	{
		$return_data = module::$return_data;
	
		if ( trim( $unick ) == '' || trim( $channel ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'KICK' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// are we missing nick and channel? invalid syntax if so.
		
		if ( trim( $reason ) == '' ) $reason = 'Kick command issued by '.$nick;
		// if they haven't suplied a reason let's fill it in.
		
		$cnicks = array_change_key_case( core::$chans[$channel]['users'], CASE_LOWER );
		if ( $user = core::search_nick( $who ) && isset( core::$chans[$channel] ) && isset( $cnicks[strtolower( $unick )] ) )
		{
			$unick = $user['nick'];
			ircd::kick( core::$config->operserv->nick, $unick, $channel, $reason );
			core::alog( core::$config->operserv->nick.': ('.$input['hostname'].') ('.$input['account'].') used KICK to remove ('.$unick.') from ('.$channel.')' );
		}
		// now we check 3 things, if the user exists, if the channel exists
		// and if the user is even in that channel, if they arn't we just leave it
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// log this and return.
	}
}

// EOF;
