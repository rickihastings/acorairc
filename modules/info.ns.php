<?php

/*
* Acora IRC Services
* modules/info.ns.php: NickServ info module
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

class ns_info implements module
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
		modules::init_module( 'ns_info', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_info', 'help', nickserv::$help->NS_HELP_INFO_1 );
		nickserv::add_help( 'ns_info', 'help info', nickserv::$help->NS_HELP_INFO_ALL );
		// add the help
		
		nickserv::add_command( 'info', 'ns_info', 'info_command' );
		// add the info command
	}
	
	/*
	* info_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function info_command( $nick, $ircdata = array() )
	{
		$unick = $ircdata[0];
		// get the nickname.
		
		if ( trim( $unick ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'INFO' ) );
			// wrong syntax
			return false;
		}
		// make sure they've entered a channel
		
		if ( !$user = services::user_exists( $unick, false, array( 'display', 'suspended', 'suspend_reason', 'last_hostmask', 'timestamp', 'last_timestamp' ) ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			return false;
		}
		// make sure the user exists
		
		if ( $user->suspended == 1 )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INFO_SUSPENDED_1, array( 'nick' => $user->display ) );
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INFO_SUSPENDED_2, array( 'reason' => $user->suspend_reason ) );
		}
		else
		{
			$hostmask = explode( '!', $user->last_hostmask );
			$hostmask = $hostmask[1];
			// get the hostmask
			
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INFO_1, array( 'nick' => $user->display ) );
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INFO_2, array( 'time' => date( "F j, Y, g:i a", $user->timestamp ) ) );
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INFO_3, array( 'time' => date( "F j, Y, g:i a", ( $user->last_timestamp != 0 ) ? $user->last_timestamp : core::$network_time ) ) );
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INFO_4, array( 'host' => $hostmask ) );
			// standard messages
			
			if ( core::$nicks[$nick]['ircop'] && core::$nicks[$nick]['identified'] || $unick == $nick && core::$nicks[$nick]['identified'] )
			{
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INFO_5, array( 'email' => nickserv::get_flags( $nick, 'e' ) ) );
			}
			// if the person doing /ns info has staff powers we show the email
			// or if someone is doing /ns info on themselves we show it.
			
			$url = nickserv::get_flags( $unick, 'u' );
			if ( $url != null )
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INFO_6, array( 'url' => $url ) );
			// url
	
			$list = '';
			
			if ( nickserv::check_flags( $unick, array( 'S' ) ) )
				$list .= 'Secure, ';
			if ( nickserv::check_flags( $unick, array( 'P' ) ) )
				$list .= 'Private Message';
			
			if ( substr( $list, -2, 2 ) == ', ' ) 
				$list = substr( $list, 0 ,-2 );
			// compile our list of options
			
			if ( $list != '' )
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INFO_7, array( 'options' => $list ) );
			// if our list doesn't equal '', eg. empty show the info.
			
			if ( core::$nicks[$nick]['ircop'] && core::$nicks[$nick]['identified'] && core::$config->nickserv->expire != 0 )
			{
				$expiry_time = core::$config->nickserv->expire * 86400;
				
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INFO_8, array( 'time' => date( "F j, Y, g:i a", ( $user->last_timestamp != 0 ) ? $user->last_timestamp : core::$network_time + $expiry_time ) ) );
			}
			// if the nick in question has staff powers, we show the expiry times.
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

// EOF;