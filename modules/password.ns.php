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
	const MOD_VERSION = '0.0.3';
	const MOD_AUTHOR = 'Acora';
	// module info
	
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
		
		self::_change_pass( $nick, $nick, $ircdata[0], $ircdata[1] );
		// call _change_pass
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
		
		self::_change_pass( $nick, $ircdata[0], $ircdata[1], $ircdata[2] );
		// call _change_pass
	}
	
	/*
	* _change_pass (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $unick - The account to change password for
	* $new_pass - The new password
	* $conf_pass - The confirmed password
	*/
	static public function _change_pass( $nick, $unick, $new_pass, $conf_pass )
	{
		$user = database::select( 'users', array( 'display', 'id', 'salt' ), array( 'display', '=', $unick ) );
		if ( database::num_rows( $user ) == 0 )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			return false;
		}
		// look for the user
		
		if ( strtolower( $new_pass ) == strtolower( $unick ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_PASSWORD_NICK_U );
			return false;
		}
		// are they using a reasonable password, eg. != their nick, lol.
		
		if ( $new_pass != $conf_pass )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_PASSWORD_DIFF );
			return false;
		}
		// the passwords are different
		
		$user = database::fetch( $user );
		database::update( 'users', array( 'pass' => sha1( $new_pass.$user->salt ) ), array( 'display', '=', $unick ) );
		// we update the password here, with the users salt.
		
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NEW_PASSWORD_U, array( 'nick' => $unick, 'pass' => $new_pass ) );
		// let them know
		
		core::alog( core::$config->nickserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') changed the password for '.$unick );
		// logchan
	}
}

// EOF;