<?php

/*
* Acora IRC Services
* modules/suspend.ns.php: NickServ suspend module
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

class ns_suspend extends module
{
	
	const MOD_VERSION = '0.0.4';
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
		modules::init_module( 'ns_suspend', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_suspend', 'help', nickserv::$help->NS_HELP_SUSPEND_1, true, 'nickserv_op' );
		nickserv::add_help( 'ns_suspend', 'help', nickserv::$help->NS_HELP_UNSUSPEND_1, true, 'nickserv_op' );
		nickserv::add_help( 'ns_suspend', 'help suspend', nickserv::$help->NS_HELP_SUSPEND_ALL, false, 'nickserv_op' );
		nickserv::add_help( 'ns_suspend', 'help unsuspend', nickserv::$help->NS_HELP_UNSUSPEND_ALL, false, 'nickserv_op' );
		// add the help
		
		nickserv::add_command( 'suspend', 'ns_suspend', 'suspend_command' );
		nickserv::add_command( 'unsuspend', 'ns_suspend', 'unsuspend_command' );
		// add the commands
	}
	
	/*
	* suspend_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function suspend_command( $nick, $ircdata = array() )
	{
		if ( !services::oper_privs( $nick, 'nickserv_op' ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// they've gotta be identified and opered..
		
		self::_unsuspend_nick( $nick, $ircdata[0], core::get_data_after( $ircdata, 1 ) );
		// call _unsuspend_nick
	}
	
	/*
	* unsuspend_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function unsuspend_command( $nick, $ircdata = array() )
	{
		if ( !services::oper_privs( $nick, 'nickserv_op' ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// they've gotta be identified and opered..
		
		self::_unsuspend_nick( $nick, $ircdata[0] );
		// call _unsuspend_nick
	}

	/*
	* _suspend_nick (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $unick - The nickname of the account to suspend
	* $reason - The reason to suspend this user
	*/
	public function _suspend_nick( $nick, $unick, $reason )
	{
		if ( trim( $unick ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'SUSPEND' ) );
			return false;
		}
		// make sure unick isnt empty!
		
		if ( services::has_privs( $unick ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// is a non-root trying to drop a root?
		
		if ( trim( $reason ) == '' ) $reason = 'No reason';
		// is there a reason? if not we set it to 'No Reason'
		
		if ( $user = services::user_exists( $unick, false, array( 'display', 'suspended' ) ) )
		{
			if ( $user->suspended == 1 )
			{
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_SUSPEND_2, array( 'nick' => $unick ) );
				return false;
				// channel is already suspended lol
			}
			else
			{
				database::update( 'users', array( 'suspended' => 1, 'suspend_reason' => $reason ), array( 'display', '=', $user->display ) );
				// channel isn't suspended, but it IS registered
			}
		}
		else
		{
			$user_info = array(
				'display'		=>	$unick,
				'last_timestamp'=>	core::$network_time,
				'timestamp'		=>	core::$network_time,
				'real_user'		=>	0,
				'suspended'		=>	1,
				'suspend_reason'=>	$reason,
			);
			// setup the user info array.
			
			database::insert( 'users', $user_info );
			// insert it into the database.
		}
		
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_SUSPEND_3, array( 'nick' => $unick, 'reason' => $reason ) );
		core::alog( core::$config->nickserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') SUSPENDED '.$unick.' with the reason ('.$reason.')' );
		ircd::wallops( core::$config->nickserv->nick, $nick.' SUSPENDED '.$unick );
		
		$unicks = array_change_key_case( core::$nicks, CASE_LOWER );
		if ( isset( $unicks[strtolower( $unick )] ) )
		{
			$unick = $unicks[$unick]['nick'];
			ircd::on_user_logout( $unick );
			core::$nicks[$unick]['identified'] = false;
			core::$nicks[$unick]['account'] = '';
			$random_nick = 'Unknown'.rand( 10000, 99999 );
			
			services::communicate( core::$config->nickserv->nick, $unick, nickserv::$help->NS_SUSPEND_1, array( 'nick' => $unick ) );
			services::communicate( core::$config->nickserv->nick, $unick, nickserv::$help->NS_NICK_CHANGE, array( 'nick' => $random_nick ) );
			ircd::svsnick( $unick, $random_nick, core::$nicks[$unick]['timestamp'] );
		}
		// is the nick in use? we need to force change it.
	}
	
	/*
	* _unsuspend_nick (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $unick - The nickname of the account to unsuspend
	*/
	public function _unsuspend_nick( $nick, $unick )
	{
		if ( trim( $unick ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'UNSUSPEND' ) );
			return false;
		}
		// make sure unick isnt empty!
		
		if ( !$user = services::user_exists( $unick, false, array( 'display', 'suspended', 'real_user' ) ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_SUSPEND_4, array( 'nick' => $unick ) );
			return false;
		}
		// nick isn't even registered.
		
		if ( $user->suspended == 0 )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_SUSPEND_4, array( 'nick' => $unick ) );
			return false;
		}
		// nick isn't suspended
		
		database::update( 'users', array( 'suspended' => 0, 'suspend_reason' => null ), array( 'display', '=', $unick ) );
		
		if ( $user->real_user == 0 )
			database::delete( 'users', array( 'display', '=', $unick ) );
		// nick wasen't registered by a real person, drop it
		
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_SUSPEND_5, array( 'nick' => $unick ) );
		core::alog( core::$config->nickserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') UNSUSPENDED '.$unick );
		ircd::wallops( core::$config->nickserv->nick, $nick.' UNSUSPENDED '.$unick );
		// oh well, was fun while it lasted eh?cunsuspend it :P
	}
	
	/*
	* on_burst_connect (event hook)
	*/
	static public function on_burst_connect( $connect_data )
	{
		self::on_connect( $connect_data );
	}
	
	/*
	* on_connect (event hook)
	*/
	static public function on_connect( $connect_data )
	{
		$user = nickserv::$nick_q[$connect_data['nick']];
		// get nick
		
		if ( !isset( $user ) || $user === false )
			return false;
			
		$nick = $connect_data['nick'];
		// re-allocate it after we know we actually need to use $nick, will shave milliseconds off huge bursts
		// not amazing but better than nothing.
			
		if ( $user->suspended == 0 )
			return false;
			
		$random_nick = 'Unknown'.rand( 10000, 99999 );
		
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_SUSPEND_1, array( 'nick' => $user->display ) );
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NICK_CHANGE, array( 'nick' => $random_nick ) );
		ircd::svsnick( $nick, $random_nick, core::$nicks[$nick]['timestamp'] );
		// check if the nick is suspended etc.
	}
	
	/*
	* on_nick_change (event hook)
	*/
	static public function on_nick_change( $old_nick, $nick )
	{
		$user = services::user_exists( $nick, false, array( 'display', 'suspended' ) );
		
		if ( $user === false )
			return false;
		if ( $user->suspended == 0 )
			return false;
		// not registered or suspended.
		
		$random_nick = 'Unknown'.rand( 10000, 99999 );
		
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_SUSPEND_1, array( 'nick' => $user->display ) );
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NICK_CHANGE, array( 'nick' => $random_nick ) );
		ircd::svsnick( $nick, $random_nick, core::$nicks[$nick]['timestamp'] );
		// change nick
	}
}

// EOF;