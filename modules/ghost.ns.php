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

class ns_ghost implements module
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
		modules::init_module( 'ns_ghost', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_ghost', 'help', nickserv::$help->NS_HELP_GHOST_1 );
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
		$unick = $ircdata[0];
		$password = $ircdata[1];
		// get the parameters.
		
		if ( trim( $unick ) == '' || trim( $password ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'GHOST' ) );
			return false;
		}
		// invalid syntax
		
		if ( !isset( core::$nicks[$unick] ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NOT_IN_USE, array( 'nick' => $unick ) );
			return false;
			// nickname isn't in use
		}
		
		if ( $user = services::user_exists( $unick, false, array( 'display', 'pass', 'salt' ) ) )
		{
			if ( $user->pass == sha1( $password.$user->salt ) || ( core::$nicks[$nick]['ircop'] && core::$nicks[$nick]['identified'] ) )
			{
				ircd::kill( core::$config->nickserv->nick, $unick, 'GHOST command used by '.core::get_full_hostname( $nick ) );
				core::alog( core::$config->nickserv->nick.': GHOST command used on '.$unick.' by '.core::get_full_hostname( $nick ) );
			}
			else
			{
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_PASSWORD );
				// password isn't correct
			}
		}
		else
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			return false;
			// doesn't even exist..
		}
	}
	
	/*
	* main (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	public function main( $ircdata, $startup = false )
	{
		return true;
		// we don't need to listen for anything in this module
		// so we just return true immediatly.
	}
	
}