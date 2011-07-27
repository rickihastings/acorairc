<?php

/*
* Acora IRC Services
* modules/password.ns.php: NickServ password module
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

class ns_password extends module
{

	const MOD_VERSION = '0.1.3';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'NICK_UNREGISTERED'	=> 2,
		'BAD_PASSWORD'		=> 3,
		'BAD_MATCH'			=> 4,
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
		modules::init_module( 'ns_password', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_password', 'help', nickserv::$help->NS_HELP_PASSWORD_1, true );
		nickserv::add_help( 'ns_password', 'help password', nickserv::$help->NS_HELP_PASSWORD_ALL );
		nickserv::add_help( 'ns_password', 'help', nickserv::$help->NS_HELP_SAPASS_1, true, 'nickserv_op' );
		nickserv::add_help( 'ns_password', 'help sapass', nickserv::$help->NS_HELP_SAPASS_ALL, false, 'nickserv_op' );
		// add the help docs
		
		nickserv::add_command( 'password', 'ns_password', 'password_command' );
		nickserv::add_command( 'sapass', 'ns_password', 'sapass_command' );
		// add the password command
	}
	
	/*
	* password_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function password_command( $nick, $ircdata = array() )
	{
		if ( !core::$nicks[$nick]['identified'] )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NOT_IDENTIFIED );
			return false;
		}
		// are they identified?
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'], 'command' => 'PASSWORD' );
		$return_data = self::_change_pass( $input, $nick, $nick, $ircdata[0], $ircdata[1] );
		// call _change_pass
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* sapass_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function sapass_command( $nick, $ircdata = array() )
	{
		if ( ( core::$nicks[$nick]['account'] != $unick && services::has_privs( $unick ) ) || !services::oper_privs( $nick, 'nickserv_op' ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// access denied.
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'], 'command' => 'SAPASS' );
		$return_data = self::_change_pass( $input, $nick, $ircdata[0], $ircdata[1], $ircdata[2] );
		// call _change_pass
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _change_pass (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $unick - The account to change password for
	* $new_pass - The new password
	* $conf_pass - The confirmed password
	*/
	static public function _change_pass( $input, $nick, $unick, $new_pass, $conf_pass )
	{
		$return_data = module::$return_data;
		if ( trim( $unick ) == '' || trim( $new_pass ) == '' || trim( $conf_pass ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->INVALID_INVALID_SYNTAX_RE, array( 'help' => $input['command'] ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// invalid syntax
	
		$user = database::select( 'users', array( 'display', 'id', 'salt' ), array( 'display', '=', $unick ) );
		if ( database::num_rows( $user ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
			return $return_data;
		}
		// look for the user
		
		if ( strtolower( $new_pass ) == strtolower( $unick ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_PASSWORD_NICK_U );
			$return_data[CMD_FAILCODE] = self::$return_codes->BAD_PASSWORD;
			return $return_data;
		}
		// are they using a reasonable password, eg. != their nick, lol.
		
		if ( $new_pass != $conf_pass )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_PASSWORD_DIFF );
			$return_data[CMD_FAILCODE] = self::$return_codes->BAD_MATCH;
			return $return_data;
		}
		// the passwords are different
		
		$user = database::fetch( $user );
		database::update( 'users', array( 'pass' => sha1( $new_pass.$user->salt ) ), array( 'display', '=', $unick ) );
		// we update the password here, with the users salt.
		core::alog( core::$config->nickserv->nick.': ('.$input['hostname'].') ('.$input['account'].') changed the password for '.$unick );
		// logchan
		
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_NEW_PASSWORD_U, array( 'nick' => $unick, 'pass' => $new_pass ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return shiz
	}
}

// EOF;