<?php

/*
* Acora IRC Services
* modules/register.ns.php: NickServ register module
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

class ns_register extends module
{
	
	const MOD_VERSION = '0.1.3';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'BAD_PASSWORD'		=> 2,
		'INVALID_EMAIL'		=> 3,
		'ALREADY_REGISTERED'=> 4,
		'EMAIL_IN_USE'		=> 5,
		'NICK_UNREGISTERED'	=> 6,
		'INVALID_PASSCODE'	=> 7,
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
		
		commands::add_help( 'nickserv', 'ns_register', 'help', nickserv::$help->NS_HELP_REGISTER_1, true );
		commands::add_help( 'nickserv', 'ns_register', 'help register', nickserv::$help->NS_HELP_REGISTER_ALL );
		
		if ( core::$config->nickserv->force_validation )
		{
			commands::add_help( 'nickserv', 'ns_register', 'help', nickserv::$help->NS_HELP_CONFIRM_1, true );
			commands::add_help( 'nickserv', 'ns_register', 'help confirm', nickserv::$help->NS_HELP_CONFIRM_ALL );
		}
		// add the help
		
		commands::add_command( 'nickserv', 'register', 'ns_register', 'register_command' );
		
		if ( core::$config->nickserv->force_validation )
			commands::add_command( 'nickserv', 'confirm', 'ns_register', 'confirm_command' );
		// add the commands
	}
	
	/*
	* register_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function register_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_register_user( $input, $nick, $ircdata[0], $ircdata[1] );
		// call _register_user
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* confirm_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function confirm_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_confirm_user( $input, $nick, $ircdata[0] );
		// call _confirm_user
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _register_user (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $password - The password to use
	* $email - The email addr to use
	*/
	static public function _register_user( $input, $nick, $password, $email )
	{
		$return_data = module::$return_data;
		if ( trim( $password ) == '' || trim( $email ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'REGISTER' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
		
		if ( strtolower( $password ) == strtolower( $nick ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_PASSWORD_NICK );
			$return_data[CMD_FAILCODE] = self::$return_codes->BAD_PASSWORD;
			return $return_data;
		}
		// are they using a reasonable password, eg. != their nick, lol.
		
		if ( services::valid_email( $email ) === false )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_EMAIL );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_EMAIL;
			return $return_data;
		}
		// is the email valid?
		
		if ( core::$nicks[$nick]['identified'] || $user = services::user_exists( $nick, false, array( 'display', 'id' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_ALREADY_REGISTERED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ALREADY_REGISTERED;
			return $return_data;
		}
		// are we registered? apprently not, let's move on!
		
		$check_e = database::select( 'users_flags', array( 'email' ), array( 'email', '=', $email ) );
		if ( database::num_rows( $check_e ) > 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_EMAIL_IN_USE );
			$return_data[CMD_FAILCODE] = self::$return_codes->EMAIL_IN_USE;
			return $return_data;
		}
		// check if the email is in use.
		
		$salt = '';
		for ( $i = 0; $i < 8; $i++ )
		{
			$possible = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$salt .= substr( $possible, rand( 0, strlen( $possible ) - 1 ), 1 );
		}
		// generate a salt AND VINEGAR!
		
		$user_info = array(
			'display'		=>	$nick,
			'pass'			=>	sha1( $password.$salt ),
			'salt'			=>	$salt,
			'last_hostmask' =>	$input['hostname'],
			'last_timestamp'=>	core::$network_time,
			'timestamp'		=>	core::$network_time,
			'validated'		=>	( core::$config->nickserv->force_validation === true ) ? 0 : 1,
			'real_user'		=>	1,
		);
		// setup the user info array.
		
		$flags = core::$config->nickserv->default_flags;
		$flags = str_replace( 'u', '', $flags );
		$flags = str_replace( 'e', '', $flags );
		// ignore parameter flags
		
		database::insert( 'users', $user_info );
		database::insert( 'users_flags', array( 'nickname' => $nick, 'flags' => $flags.'e', 'email' => $email ) );
		// insert it into the database.
		
		if ( core::$config->nickserv->force_validation )
		{
			$validation_code = mt_rand();
			
			core::alog( core::$config->nickserv->nick.': '.$nick.' requested by ('.$input['hostname'].')' );
			// logchan
			database::insert( 'validation_codes', array( 'nick' => $nick, 'code' => $validation_code ) );
			// insert the random code to the database
			
			$to      = $nick.' <'.$email.'>';
			$subject = 'Registration';
			$headers = 'From: '.core::$config->server->network_name.' <'.( isset( core::$config->email_from ) ) ? core::$config->email_from : core::$config->service_user.'>\n';
			$message = '
Thank you for using '.core::$config->server->network_name.'

Nickname: '.$nick.'
Password: '.$password.'
Confirmation Code: '.$validation_code.'

To confirm your nickname type the following when connected to '.core::$config->server->network_name.'
/msg '.core::$config->nickserv->nick.' confirm '.$validation_code.'

You will then be able to identify with the password you chose by typing
/msg '.core::$config->nickserv->nick.' identify '.$password.'
			';
			// generate the email information
			
			@mail( $to, $subject, $message, $headers );
			// let's send the email
			
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_NICK_REQUESTED, array( 'email' => $email ) );
		}
		else
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_NICK_REGISTERED );
			core::alog( core::$config->nickserv->nick.': '.$nick.' registered by ('.$input['hostname'].')' );
			// logchan
			
			core::alog( 'register_command(): '.$nick.' registered by '.$input['hostname'], 'BASIC' );
			// log what we need to log.
		}
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return data
	}
	
	/*
	* _confirm_user (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $code - The confirm code
	*/
	static public function _confirm_user( $input, $nick, $code )
	{
		$return_data = module::$return_data;
		if ( trim( $code ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'CONFIRM' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
		
		if ( !$user = services::user_exists( $nick, false, array( 'display', 'id' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_UNREGISTERED );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
			return $return_data;
		}
		// unregistered
		
		$code_array = database::select( 'validation_codes', array( 'nick', 'code' ), array( 'nick', '=', $nick, 'AND', 'code', '=', $code ) );
		if ( database::num_rows( $code_array ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_PASSCODE );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_PASSCODE;
			return $return_data;
		}
		// invalid passcode
		
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_VALIDATED );
		// let them know.
		
		database::update( 'users', array( 'validated' => 1 ), array( 'id', '=', $user->id ) );
		// user is now validated.
		
		database::delete( 'validation_codes', array( 'nick', '=', $nick, 'AND', 'code', '=', $code ) );
		// delete the code now that we've validated them
		
		core::alog( core::$config->nickserv->nick.': '.$nick.' activated by ('.$input['hostname'].')' );
		// logchan
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return data
	}
}

// EOF;
