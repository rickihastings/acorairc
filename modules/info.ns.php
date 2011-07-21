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

class ns_info extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'NICK_UNREGISTERED'	=> 2,
	);
	// return codes
	
	/*
	* modload (private)
	* 
	* @params
	* void
	*/
	public function modload()
	{
		modules::init_module( 'ns_info', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_info', 'help', nickserv::$help->NS_HELP_INFO_1, true );
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_nick_info( $input, $nick, $ircdata[0] );
		// $who is the user we're adding REMEMBER!
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _nick_info (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick commandeeer
	* $unick - The nick to search for
	*/
	static public function _nick_info( $input, $nick, $unick )
	{
		$return_data = module::$return_data;
		
		if ( trim( $unick ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'INFO' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// make sure they've entered a channel
		
		if ( !$user = services::user_exists( $unick, false, array( 'display', 'suspended', 'suspend_reason', 'last_hostmask', 'timestamp', 'last_timestamp' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
			return $return_data;
		}
		// make sure the user exists
		
		if ( $user->suspended == 1 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INFO_SUSPENDED_1, array( 'nick' => $user->display ) );
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INFO_SUSPENDED_2, array( 'reason' => $user->suspend_reason ) );
			$return_data[CMD_DATA] = array( 'suspended' => 1, 'nick' => $user->display, 'reason' => $user->suspend_reason );
			$return_data[CMD_SUCCESS] = true;
			return $return_data;
		}
		// suspended channel
		
		$hostmask = explode( '!', $user->last_hostmask );
		$hostmask = $hostmask[1];
		// get the hostmask
		
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INFO_1, array( 'nick' => $user->display ) );
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INFO_2, array( 'time' => date( "F j, Y, g:i a", $user->timestamp ) ) );
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INFO_3, array( 'time' => date( "F j, Y, g:i a", ( $user->last_timestamp != 0 ) ? $user->last_timestamp : core::$network_time ) ) );
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INFO_4, array( 'host' => $hostmask ) );
		// standard messages
		$return_data[CMD_DATA]['suspended'] = 0;
		$return_data[CMD_DATA]['nick'] = $user->display;
		$return_data[CMD_DATA]['timestamp'] = $user->timestamp;
		$return_data[CMD_DATA]['last_timestamp'] = ( $user->last_timestamp != 0 ) ? $user->last_timestamp : core::$network_time;
		$return_data[CMD_DATA]['host'] = $hostmask;
		
		if ( ( $input['internal'] && core::$nicks[$nick]['ircop'] && core::$nicks[$nick]['identified'] ) || $unick == $input['account'] )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INFO_5, array( 'email' => nickserv::get_flags( $unick, 'e' ) ) );
			$return_data[CMD_DATA]['email'] = nickserv::get_flags( $unick, 'e' );
		}
		// if the person doing /ns info has staff powers we show the email
		// or if someone is doing /ns info on themselves we show it.
		
		$url = nickserv::get_flags( $unick, 'u' );
		if ( $url != null )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INFO_6, array( 'url' => $url ) );
			$return_data[CMD_DATA]['url'] = $url;
		}
		// url

		$list = '';
		if ( nickserv::check_flags( $unick, array( 'S' ) ) )
			$list .= 'Secure, ';
		if ( nickserv::check_flags( $unick, array( 'H' ) ) )
			$list .= 'Hostmask, ';
		if ( nickserv::check_flags( $unick, array( 'P' ) ) )
			$list .= 'Private Message';
		
		if ( substr( $list, -2, 2 ) == ', ' ) 
			$list = substr( $list, 0 ,-2 );
		// compile our list of options
		
		if ( $list != '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INFO_7, array( 'options' => $list ) );
			$return_data[CMD_DATA]['options'] = $list;
		}
		// if our list doesn't equal '', eg. empty show the info.
		
		$expiry_time = core::$config->nickserv->expire * 86400;
		$return_data[CMD_DATA]['expiry_time'] = ( $user->last_timestamp != 0 ) ? $user->last_timestamp : core::$network_time + $expiry_time;
		
		if ( core::$nicks[$nick]['ircop'] && core::$nicks[$nick]['identified'] && core::$config->nickserv->expire != 0 )
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INFO_8, array( 'time' => date( "F j, Y, g:i a", ( $user->last_timestamp != 0 ) ? $user->last_timestamp : core::$network_time + $expiry_time ) ) );
		// if the nick in question has staff powers, we show the expiry times.
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
}

// EOF;