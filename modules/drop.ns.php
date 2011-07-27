<?php

/*
* Acora IRC Services
* modules/drop.ns.php: NickServ drop module
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

class ns_drop extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'ACCESS_DENIED'		=> 2,
		'NICK_SUSPENDED'	=> 3,
		'NICK_UNREGISTERED' => 4,
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
		modules::init_module( 'ns_drop', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_drop', 'help', nickserv::$help->NS_HELP_DROP_1, true );
		nickserv::add_help( 'ns_drop', 'help drop', nickserv::$help->NS_HELP_DROP_ALL );
		nickserv::add_help( 'ns_drop', 'help', nickserv::$help->NS_HELP_SADROP_1, true, 'nickserv_op' );
		nickserv::add_help( 'ns_drop', 'help sadrop', nickserv::$help->NS_HELP_SADROP_ALL, false, 'nickserv_op' );
		// add the help
		
		nickserv::add_command( 'drop', 'ns_drop', 'drop_command' );
		nickserv::add_command( 'sadrop', 'ns_drop', 'sadrop_command' );
		// add the drop command
	}
	
	/*
	* drop_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function drop_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );		
		$return_data = self::_drop_nick( $input, $nick, $ircdata[0], $ircdata[1], false );
		// call _drop_nick
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}

	/*
	* sadrop_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function sadrop_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );		
		$return_data = self::_drop_nick( $input, $nick, $ircdata[0], '', true );
		// call _drop_nick
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _drop_nick (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $unick - The account of the nick to drop
	* $password - The account password
	* $sadrop - If this is set to true, we don't need a $password (however, privilages are still checked)
	*/
	static public function _drop_nick( $input, $nick, $unick, $password, $sadrop = false )
	{
		$return_data = module::$return_data;
		if ( trim( $unick ) == '' || ( !$sadrop && trim( $password ) == '' ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => ( ( $sadrop ) ? 'SADROP' : 'DROP' ) ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// invalid syntax
		
		if ( $sadrop && ( ( core::$nicks[$nick]['account'] != $unick && services::has_privs( $unick ) ) || !services::oper_privs( $nick, 'nickserv_op' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// access denied
		
		if ( !$user = services::user_exists( $unick, false, array( 'id', 'display', 'pass', 'salt', 'suspended' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
			return $return_data;
		}
		// doesn't even exist..
		
		if ( $user->suspended == 1 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_SUSPEND_1, array( 'nick' => $user->display ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_SUSPENDED;
			return $return_data;
		}
		// are they suspended?
		
		if ( ( !$sadrop && $user->pass == sha1( $password.$user->salt ) ) || $sadrop )
		{
			database::delete( 'users', array( 'display', '=', $user->display ) );
			database::delete( 'users_flags', array( 'nickname', '=', $user->display ) );
			// delete the users record
			
			database::delete( 'chans_levels', array( 'target', '=', $user->display ) );
			// also delete this users channel access.
			
			core::alog( core::$config->nickserv->nick.': '.$user->display.' has been dropped by ('.$input['hostname'].') ('.$input['account'].')' );
			core::alog( 'drop_command(): '.$user->display.' has been dropped by '.$input['hostname'], 'BASIC' );
			
			if ( isset( core::$nicks[$user->display] ) )
				ircd::on_user_logout( $nick->display );
			// if the nick is being used unregister it, even though it shouldn't be?
			
			core::$nicks[$user->display]['identified'] = false;
			core::$nicks[$user->display]['account'] = '';
			// set identified to false
			
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_NICK_DROPPED, array( 'nick' => $user->display ) );
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

//EOF;