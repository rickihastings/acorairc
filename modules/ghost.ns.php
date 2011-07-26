<?php

/*
* Acora IRC Services
* modules/recover.ns.php: NickServ ghost module
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

class ns_ghost extends module
{
	
	const MOD_VERSION = '0.1.3';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'NOT_IN_USE'		=> 2,
		'CANT_GHOST_SELF'	=> 3,
		'NICK_UNREGISTERED'	=> 4,
		'INVALID_PASSWORD'	=> 5,
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
		modules::init_module( 'ns_ghost', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_ghost', 'help', nickserv::$help->NS_HELP_GHOST_1, true );
		nickserv::add_help( 'ns_ghost', 'help ghost', nickserv::$help->NS_HELP_GHOST_ALL );
		// add the help
		
		nickserv::add_command( 'ghost', 'ns_ghost', 'ghost_command' );
		// add the ghost command
	}
	
	/*
	* ghost_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function ghost_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );		
		$return_data = self::_ghost_nick( $input, $nick, $ircdata[0], $ircdata[1] );
		// call _ghost_nick
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _ghost_nick (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $unick - The account to ghost
	* $password - The password of the account
	*/
	static public function _ghost_nick( $input, $nick, $unick, $password )
	{
		$return_data = module::$return_data;
		
		if ( trim( $unick ) == '' || trim( $password ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'GHOST' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// invalid syntax
		
		if ( !isset( core::$nicks[$unick] ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_NOT_IN_USE, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NOT_IN_USE;
			return $return_data;
		}
		// nickname isn't in use
		
		if ( $nick == $unick )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_CANT_GHOST_SELF );
			$return_data[CMD_FAILCODE] = self::$return_codes->CANT_GHOST_SELF;
			return $return_data;
		}
		// you can't ghost yourself.. waste of time, and clearly useless.
		
		if ( !$user = services::user_exists( $unick, false, array( 'display', 'pass', 'salt' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
			return $return_data;
		}
		// doesn't even exist..
		
		if ( $user->pass == sha1( $password.$user->salt ) || services::oper_privs( $nick, 'nickserv_op' ) )
		{
			ircd::kill( core::$config->nickserv->nick, $unick, 'GHOST command used by '.$input['hostname'] );
			core::alog( core::$config->nickserv->nick.': GHOST command used on '.$unick.' by ('.$input['hostname'].')' );
			
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_GHOSTED, array( 'nick' => $unick ) );
			$return_data[CMD_SUCCESS] = true;
			return $return_data;
		}
		else
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_PASSWORD );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_PASSWORD;
			return $return_data;
		}
	}
}

// EOF;