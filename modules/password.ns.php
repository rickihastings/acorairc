<?php

/*
* Acora IRC Services
* modules/password.ns.php: NickServ password module
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

class ns_password implements module
{
	const MOD_VERSION = '0.0.1';
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
		modules::init_module( 'ns_password', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_password', 'help', &nickserv::$help->NS_HELP_PASSWORD_1 );
		nickserv::add_help( 'ns_password', 'help password', &nickserv::$help->NS_HELP_PASSWORD_ALL );
		nickserv::add_help( 'ns_password', 'help', &nickserv::$help->NS_HELP_SAPASS_1, true );
		nickserv::add_help( 'ns_password', 'help sapass', &nickserv::$help->NS_HELP_SAPASS_ALL, true );
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
		$new_pass = $ircdata[0];
		$conf_pass = $ircdata[1];
		// new password.
		
		if ( !$user = services::user_exists( $nick, false, array( 'display', 'id', 'identified', 'salt' ) ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_UNREGISTERED );
			return false;	
		}
		// find out if our user is registered
		
		if ( $user->identified == 0 )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_NOT_IDENTIFIED );
			return false;
		}
		// are they identified?
		
		if ( strtolower( $new_pass ) == strtolower( $nick ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_PASSWORD_NICK );
			return false;
		}
		// are they using a reasonable password, eg. != their nick, lol.
		
		if ( $new_pass != $conf_pass )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_PASSWORD_DIFF );
			return false;
		}
		// the passwords are different
			
		database::update( 'users', array( 'pass' => sha1( $new_pass.$user->salt ) ), array( 'display', '=', $nick ) );
		// we update the password here, with the users salt.
		
		services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_NEW_PASSWORD, array( 'pass' => $new_pass ) );
		// let them know
		
		core::alog( core::$config->nickserv->nick.': '.core::get_full_hostname( $nick ).' changed their password' );
		// logchan
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
		$unick = core::get_nick( &$ircdata, 0 );
		$new_pass = $ircdata[1];
		$conf_pass = $ircdata[2];
		// new password.
		
		if ( !$user = services::user_exists( $unick, false, array( 'display', 'id', 'identified', 'salt' ) ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			return false;	
		}
		// find out if our user is registered
		
		if ( services::is_root( $unick ) && !services::is_root( $nick ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// is a non-root trying to change a root's password?
		
		if ( !core::$nicks[$nick]['ircop'] || services::user_exists( $nick, true, array( 'display', 'identified' ) ) === false )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// do we have access to do this?
		
		if ( strtolower( $new_pass ) == strtolower( $unick ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_PASSWORD_NICK_U );
			return false;
		}
		// are they using a reasonable password, eg. != their nick, lol.
		
		if ( $new_pass != $conf_pass )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_PASSWORD_DIFF );
			return false;
		}
		// the passwords are different
			
		database::update( 'users', array( 'pass' => sha1( $new_pass.$user->salt ) ), array( 'display', '=', $unick ) );
		// we update the password here, with the users salt.
		
		services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_NEW_PASSWORD_U, array( 'nick' => $unick, 'pass' => $new_pass ) );
		// let them know
		
		core::alog( core::$config->nickserv->nick.': '.core::get_full_hostname( $nick ).' changed the password for '.$unick );
		// logchan
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
	}
}

// EOF;