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

class ns_drop implements module
{
	
	const MOD_VERSION = '0.0.3';
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
		modules::init_module( 'ns_drop', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_drop', 'help', nickserv::$help->NS_HELP_DROP_1 );
		nickserv::add_help( 'ns_drop', 'help drop', nickserv::$help->NS_HELP_DROP_ALL );
		nickserv::add_help( 'ns_drop', 'help', nickserv::$help->NS_HELP_FDROP_1, 'nickserv_op' );
		nickserv::add_help( 'ns_drop', 'help sadrop', nickserv::$help->NS_HELP_SADROP_ALL, 'nickserv_op' );
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
		$unick = $ircdata[0];
		$password = $ircdata[1];
		// get the nick.
		
		if ( trim( $unick ) == '' || trim( $password ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'DROP' ) );
			return false;
		}
		// invalid syntax
		
		if ( $user = services::user_exists( $unick, false, array( 'id', 'display', 'pass', 'salt', 'suspended' ) ) )
		{
			if ( $user->suspended == 1 )
			{
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_SUSPEND_1, array( 'nick' => $user->display ) );
				return false;
			}
			// are they suspended?
			
			if ( $user->pass == sha1( $password.$user->salt ) )
			{
				database::delete( 'users', array( 'display', '=', $user->display ) );
				database::delete( 'users_flags', array( 'nickname', '=', $user->display ) );
				// delete the users record
				
				database::delete( 'chans_levels', array( 'target', '=', $user->display ) );
				// also delete this users channel access.
				
				core::alog( core::$config->nickserv->nick.': '.$user->display.' has been dropped by ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].')' );
				// logchan it
				
				core::alog( 'drop_command(): '.$user->display.' has been dropped by '.core::get_full_hostname( $nick ), 'BASIC' );
				// log what we need to log.
				
				if ( isset( core::$nicks[$user->display] ) )
					ircd::on_user_logout( $nick->display );
				// if the nick is being used unregister it, even though it shouldn't be?
				
				core::$nicks[$user->display]['identified'] = false;
				core::$nicks[$user->display]['account'] = '';
				// set identified to false
				
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NICK_DROPPED, array( 'nick' => $user->display ) );
				// let the nick know the account has been dropped.
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
	* sadrop_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function sadrop_command( $nick, $ircdata = array() )
	{
		$unick = $ircdata[0];
		
		if ( trim( $unick ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'SADROP' ) );
			return false;
		}
		// invalid syntax
		
		if ( ( core::$nicks[$nick]['account'] != $unick && services::has_privs( $unick ) ) || !services::oper_privs( $nick, 'nickserv_op' ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// access denied
		
		if ( $user = services::user_exists( $unick, false, array( 'id', 'display','suspended' ) ) )
		{
			if ( $user->suspended == 1 )
			{
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_SUSPEND_1, array( 'nick' => $user->display ) );
				return false;
			}
			// are they suspended?
			
			database::delete( 'users', array( 'display', '=', $user->display ) );
			database::delete( 'users_flags', array( 'nickname', '=', $user->display ) );
			// delete the users record
			
			database::delete( 'chans_levels', array( 'target', '=', $user->display ) );
			// also delete this users channel access.
			
			core::alog( core::$config->nickserv->nick.': '.$user->display.' has been dropped by ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].')' );
			// logchan it
			
			core::alog( 'drop_command(): '.$user->display.' has been dropped by '.core::get_full_hostname( $nick ), 'BASIC' );
			// log what we need to log.
			
			if ( isset( core::$nicks[$user->display] ) )
				ircd::on_user_logout( $nick->display );
			// if the nick is being used unregister it, even though it shouldn't be?
			
			core::$nicks[$user->display]['identified'] = false;
			core::$nicks[$user->display]['account'] = '';
			// set identified to false
			
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NICK_DROPPED, array( 'nick' => $user->display ) );
			// let the nick know the account has been dropped.
		}
		else
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			return false;
			// doesn't even exist..
		}
	}
}

//EOF;