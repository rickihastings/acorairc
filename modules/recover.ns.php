<?php

/*
* Acora IRC Services
* modules/recover.ns.php: NickServ recover module
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

class ns_recover extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $expiry_time = 60;
	static public $held_nicks = array();
	static public $introduce = array();
	// some variables
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'NICK_UNREGISTERED'	=> 2,
		'NOT_IN_USE'		=> 3,
		'CANT_RECOVER_SELF' => 4,
		'NO_HOLD'			=> 5,
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
		modules::init_module( __CLASS__, self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		commands::add_help( 'nickserv', 'ns_recover', 'help', nickserv::$help->NS_HELP_RECOVER_1, true );
		commands::add_help( 'nickserv', 'ns_recover', 'help recover', nickserv::$help->NS_HELP_RECOVER_ALL );
		commands::add_help( 'nickserv', 'ns_recover', 'help', nickserv::$help->NS_HELP_RELEASE_1, true );
		commands::add_help( 'nickserv', 'ns_recover', 'help release', nickserv::$help->NS_HELP_RELEASE_ALL );
		// add the help
		
		commands::add_command( 'nickserv', 'recover', 'ns_recover', 'recover_command' );
		commands::add_command( 'nickserv', 'release', 'ns_recover', 'release_command' );
		// add the commands
	}
	
	/*
	* recover_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function recover_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_recover_nick( $input, $nick, $ircdata[0], $ircdata[1] );
		// call _recover_nick
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* release_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function release_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_release_nick( $input, $nick, $ircdata[0], $ircdata[1] );
		// call _release_nick
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _recover_nick (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $unick - The nickname of the account to recover
	* $password - The password of that account
	*/
	public function _recover_nick( $input, $nick, $unick, $password )
	{
		$return_data = module::$return_data;
		
		if ( trim( $unick ) == '' || trim( $password ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'RECOVER' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// invalid syntax
		
		if ( !core::search_nick( $unick ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_NOT_IN_USE, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NOT_IN_USE;
			return $return_data;
		}
		// nickname isn't in use
		
		if ( !$user = services::user_exists( $unick, false, array( 'display', 'pass', 'salt' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
			return $return_data;
		}
		// doesn't even exist..
		
		if ( $nick == $unick )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_CANT_RECOVER_SELF );
			$return_data[CMD_FAILCODE] = self::$return_codes->CANT_RECOVER_SELF;
			return $return_data;
		}
		// you can't ghost yourself.. waste of time, and clearly useless.
	
		if ( $user->pass != sha1( $password.$user->salt ) || !services::oper_privs( $nick, 'nickserv_op' ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_PASSWORD );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_PASSWORD;
			return $return_data;
		}
		// password isn't correct
	
		$random_nick = 'Unknown'.rand( 10000, 99999 );
		// generate a random nick
		
		ircd::svsnick( $unick, $random_nick, core::$nicks[$unick]['timestamp'] );
		// force the nick change, ONLY, we set a timer to introduce the
		// enforcer client, in the next iteration of the main loop
		// to make sure the ircd class can preserve all information
		// about the target, and not have it overwritten with introduce_client()
		
		core::alog( core::$config->nickserv->nick.': RECOVER command used on '.$unick.' by ('.$input['hostname'].') ('.$input['account'].')' );
		timer::add( array( 'ns_recover', 'introduce_callback', array( $unick ) ), 1, 1 );
		// set it into the array which we check, in the next second
		
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_NICK_RECOVERED, array( 'nick' => $unick ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* _release_nick (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $unick - The nickname of the account to release
	* $password - The password of that account
	*/
	public function _release_nick( $input, $nick, $unick, $password )
	{
		$return_data = module::$return_data;
		
		if ( trim( $unick ) == '' || trim( $password ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'RELEASE' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// invalid syntax

		if ( !$user = services::user_exists( $unick, false, array( 'display', 'pass', 'salt' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
			return $return_data;
		}
		// doesn't even exist..
		
		if ( $user->pass != sha1( $password.$user->salt ) || !services::oper_privs( $nick, 'nickserv_op' ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_PASSWORD );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_PASSWORD;
			return $return_data;
		}
		// password isn't correct
			
		if ( !isset( self::$held_nicks[$unick] ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_NO_HOLD, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NO_HOLD;
			return $return_data;
		}
		// nickname isnt locked.
		
		ircd::remove_client( $unick, 'RELEASED by '.$nick );
		core::alog( core::$config->nickserv->nick.': RELEASE command on '.$unick.' used by ('.$input['hostname'].') ('.$input['account'].')' );
		timer::remove( array( 'ns_recover', 'remove_callback', array( $unick ) ) );
		// if they are, remove client, respectively unsetting data and removing them.
		
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_NICK_RELEASED, array( 'nick' => $unick ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* introduce_callback (timer)
	* 
	* @params
	* $nick - the nick to "introduce"
	*/
	public function introduce_callback( $nick )
	{
		ircd::introduce_client( $nick, 'enforcer', core::$config->server->name, $nick, true );
		self::$held_nicks[$nick] = core::$network_time;
		// introduce the client, set us as a held nick
		
		timer::add( array( 'ns_recover', 'remove_callback', array( $nick ) ), self::$expiry_time, 1 );
		// add a timer.
	}
	
	/*
	* remove_callback (timer)
	* 
	* @params
	* $nick - the nick to "introduce"
	*/
	public function remove_callback( $nick )
	{
		ircd::remove_client( $nick, 'Hold on '.$nick.' expiring' );
		core::alog( core::$config->nickserv->nick.': Hold on '.$nick.' expiring' );
		// remove client, respectively
	}
	
}
