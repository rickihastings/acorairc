<?php

/*
* Acora IRC Services
* modules/recover.ns.php: NickServ recover module
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

class ns_recover implements module
{
	
	const MOD_VERSION = '0.0.1';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $expiry_time = 0;
	static public $held_nicks = array();
	static public $introduce = array();
	// some variables
	
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
		modules::init_module( 'ns_recover', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_recover', 'help', nickserv::$help->NS_HELP_RECOVER_1 );
		nickserv::add_help( 'ns_recover', 'help recover', nickserv::$help->NS_HELP_RECOVER_ALL );
		nickserv::add_help( 'ns_recover', 'help', nickserv::$help->NS_HELP_RELEASE_1 );
		nickserv::add_help( 'ns_recover', 'help release', nickserv::$help->NS_HELP_RELEASE_ALL );
		// add the help
		
		nickserv::add_command( 'recover', 'ns_recover', 'recover_command' );
		nickserv::add_command( 'release', 'ns_recover', 'release_command' );
		// add the commands
		
		self::$expiry_time = 60;
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
		$unick = $ircdata[0];
		$password = $ircdata[1];
		// get the parameters.
		
		if ( trim( $unick ) == '' || trim( $password ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'RECOVER' ) );
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
			if ( $user->pass == sha1( $password.$user->salt ) || ( core::$nicks[$nick]['ircop'] && services::user_exists( $nick, true, array( 'display', 'identified' ) ) !== false ) )
			{
				$random_nick = 'Unknown'.rand( 10000, 99999 );
				// generate a random nick
				
				ircd::svsnick( $unick, $random_nick, core::$network_time );
				// force the nick change, ONLY, we set a timer to introduce the
				// enforcer client, in the next iteration of the main loop
				// to make sure the ircd class can preserve all information
				// about the target, and not have it overwritten with introduce_client()
				
				core::alog( core::$config->nickserv->nick.': RECOVER command used on '.$unick.' by '.core::get_full_hostname( $nick ) );
				// introduce a client, logchan everything etc.
				
				timer::add( array( 'ns_recover', 'introduce_callback', array( $unick ) ), 1, 1 );
				// set it into the array which we check, in the next second
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
	* release_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function release_command( $nick, $ircdata = array() )
	{
		$unick = $ircdata[0];
		$password = $ircdata[1];
		// get the parameters.
		
		if ( trim( $unick ) == '' || trim( $password ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'RELEASE' ) );
			return false;
		}
		// invalid syntax

		if ( $user = services::user_exists( $unick, false, array( 'display', 'pass', 'salt' ) ) )
		{
			if ( $user->pass == sha1( $password.$user->salt ) || ( core::$nicks[$nick]['ircop'] && services::user_exists( $nick, true, array( 'display', 'identified' ) ) !== false ) )
			{
				if ( !isset( self::$held_nicks[$unick] ) )
				{
					services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NO_HOLD, array( 'nick' => $unick ) );
					return false;
					// nickname isnt locked.
				}
				
				ircd::remove_client( $unick, 'RELEASED by '.$nick );
				core::alog( core::$config->nickserv->nick.': RELEASE command on '.$unick.' used by '.core::get_full_hostname( $nick ) );
				timer::remove( array( 'ns_recover', 'remove_callback', array( $unick ) ) );
				// if they are, remove client, respectively
				// unsetting data and removing them.
				
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NICK_RELEASED, array( 'nick' => $unick ) );
				// tell the user their nick has been released (Y)
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