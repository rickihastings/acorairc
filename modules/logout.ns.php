<?php

/*
* Acora IRC Services
* modules/logout.ns.php: NickServ logout module
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class ns_logout implements module
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
		modules::init_module( 'ns_logout', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_logout', 'help', &nickserv::$help->NS_HELP_LOGOUT_1 );
		nickserv::add_help( 'ns_logout', 'help logout', &nickserv::$help->NS_HELP_LOGOUT_ALL );
		// add the help
		
		nickserv::add_command( 'logout', 'ns_logout', 'logout_command' );
		// add the command
	}
	
	/*
	* logout_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function logout_command( $nick, $ircdata = array() )
	{
		// no parameter commands ftw.
		
		if ( $user = services::user_exists( $nick, false, array( 'display', 'id', 'identified', 'vhost' ) ) )
		{
			if ( $user->identified == 1 )
			{
				ircd::umode( core::$config->nickserv->nick, $nick, '-'.ircd::$reg_modes['nick'] );
				// here we set unregistered mode
				database::update( 'users', array( 'identified' => 0, 'last_timestamp' => core::$network_time ), "`display` = '".database::quote( $nick )."'" );
				// unidentify them
				services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_LOGGED_OUT );
				// let them know
				core::alog( core::$config->nickserv->nick.': '.core::get_full_hostname( $nick ).' logged out of '.core::$nicks[$nick]['nick'] );
				// and log it.
			}
			else
			{
				services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_NOT_IDENTIFIED );
				// not even identified
			}
		}
		else
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_UNREGISTERED );
			// unregistered nick name
		}
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
		// we don't listen for anything here	
	}	
}

// EOF;