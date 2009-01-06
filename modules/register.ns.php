<?php

/*
* Acora IRC Services
* modules/register.ns.php: NickServ register module
* 
* Copyright (c) 2009 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* This project is licensed under the GNU Public License
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class ns_register implements module
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
		modules::init_module( 'ns_register', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_register', 'help', &nickserv::$help->NS_HELP_REGISTER_1 );
		nickserv::add_help( 'ns_register', 'help register', &nickserv::$help->NS_HELP_REGISTER_ALL );
		
		if ( core::$config->nickserv->force_validation )
		{
			nickserv::add_help( 'ns_register', 'help', &nickserv::$help->NS_HELP_CONFIRM_1 );
			nickserv::add_help( 'ns_register', 'help confirm', &nickserv::$help->NS_HELP_CONFIRM_ALL );
		}
		// add the help
		
		nickserv::add_command( 'register', 'ns_register', 'register_command' );
		
		if ( core::$config->nickserv->force_validation )
			nickserv::add_command( 'confirm', 'ns_register', 'confirm_command' );
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
		$password = $ircdata[0];
		$email = $ircdata[1];
		
		if ( trim( $password ) == '' || trim( $email ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'REGISTER' ) );
			return false;
		}
		// wrong syntax
		
		if ( strtolower( $password ) == strtolower( $nick ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_PASSWORD_NICK );
			return false;
		}
		// are they using a reasonable password, eg. != their nick, lol.
		
		if ( services::valid_email( $email ) === false )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_INVALID_EMAIL );
			return false;
		}
		// is the email valid?
		
		if ( $user = services::user_exists( $nick, false, array( 'display', 'id' ) ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_ALREADY_REGISTERED );
			return false;
		}
		// are we registered?
		// apprently not, let's move on!
		
		$check_e = database::select( 'users', array( 'email' ), "`email` = '".database::quote( $email )."'" );
		
		if ( database::num_rows( $check_e ) > 0 )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_EMAIL_IN_USE );
			return false;
		}
		// check if the email is in use.
		
		$salt = '';
		
		for ( $i = 0; $i < 8; $i++ )
		{
			$possible = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$salt .= substr( $possible, rand( 0, strlen( $possible ) - 1 ), 1 );
		}
		
		$user_info = array(
			'display'		=>	$nick,
			'pass'			=>	sha1( $password.$salt ),
			'salt'			=>	$salt,
			'last_hostmask' =>	core::get_full_hostname( $nick ),
			'last_timestamp'=>	core::$network_time,
			'timestamp'		=>	core::$network_time,
			'identified'	=>	0,
			'validated'		=>	( core::$config->nickserv->force_validation === true ) ? 0 : 1,
			'real_user'		=>	1,
		);
		// setup the user info array.
		
		$flags = core::$config->nickserv->default_flags;
		$flags = str_replace( 'u', '', $flags );
		$flags = str_replace( 'e', '', $flags );
		$flags = str_replace( 'm', '', $flags );
			// ignore parameter flags
		
		database::insert( 'users', $user_info );
		database::insert( 'users_flags', array( 'nickname' => $nick, 'flags' => $flags.'e', 'email' => $email ) );
		// insert it into the database.
		
		if ( core::$config->nickserv->force_validation === true )
		{
			$validation_code = mt_rand();
			
			core::alog( core::$config->nickserv->nick.': '.$nick.' requested by '.core::get_full_hostname( $nick ) );
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
			
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_NICK_REQUESTED, array( 'email' => $email ) );
		}
		else
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_NICK_REGISTERED );
			core::alog( core::$config->nickserv->nick.': '.$nick.' registered by '.core::get_full_hostname( $nick ) );
			// logchan
			
			core::alog( 'register_command(): '.$nick.' registered by '.core::get_full_hostname( $nick ), 'BASIC' );
			// log what we need to log.
		}
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
		$code = $ircdata[0];
		
		if ( trim( $code ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'CONFIRM' ) );
			return false;
		}
		// wrong syntax
		
		if ( !$user = services::user_exists( $nick, false, array( 'display', 'id' ) ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_UNREGISTERED );
			return false;
		}
		// unregistered
		
		$code_array = database::select( 'validation_codes', array( 'nick', 'code' ), "`nick` = '".database::quote( $nick )."' AND `code` = '".database::quote( $code )."'" );
		
		if ( database::num_rows( $code_array ) == 0 )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_INVALID_PASSCODE );
		}
		else
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_VALIDATED );
			// let them know.
			
			database::update( 'users', array( 'validated' => 1 ), "`id` = '".$user->id."'" );
			// user is now validated.
			
			database::delete( 'validation_codes', "`nick` = '".database::quote( $nick )."' AND `code` = '".database::quote( $code )."'" );
			// delete the code now that we've validated them
			
			core::alog( core::$config->nickserv->nick.': '.$nick.' activated' );
			// logchan
		}
		// no passcode found
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
		// nothing to do here.
	}
}

// EOF;