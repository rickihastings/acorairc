<?php

/*
* Acora IRC Services
* core/services.php: Collection of functions for basic usage
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class services
{
	
	public function __construct() {}
	// __construct, makes everyone happy.
	
	/*
	* user_exists
	*
	* @params
	* $nick - The nickname to check.
	* $identified - Do you want to check if they're identified?
	* $array - This is what they want to grab, can be *
	*/
	static public function user_exists( $nick, $identified = true, $array )
	{
		if ( $identified )
			$user_q = database::select( 'users', $array, "`display` = '".database::quote( $nick )."' AND `identified` = '1'" );
		else
			$user_q = database::select( 'users', $array, "`display` = '".database::quote( $nick )."'" );

		if ( $user_q == 0 )
			return false;
		// chan isnt even registered.
		
		$row = database::fetch( $user_q );
		
		return $row;
		// if it is registered return the object.
	}
	
	/*
	* user_exists_id
	*
	* @params
	* $uid - The uid to check.
	* $identified - Do you want to check if they're identified?
	* $array - This is what they want to grab, can be *
	*/
	static public function user_exists_id( $uid, $identified = true, $array )
	{
		if ( $identified )
			$user_q = database::select( 'users', $array, "`id` = '".database::quote( $uid )."' AND `identified` = '1'" );
		else
			$user_q = database::select( 'users', $array, "`id` = '".database::quote( $uid )."'" );
			
		if ( $user_q == 0 )
			return false;
		// chan isnt even registered.
		
		$row = database::fetch( $user_q );
		
		return $row;
		// if it is registered return the object.
	}
	
	/*
	* chan_exists
	*
	* @params
	* $chan - The channel to check.
	* $array - This is what they want to grab, can be *
	*/
	static public function chan_exists( $chan, $array )
	{
		$channel_q = database::select( 'chans', $array, "`channel` = '".database::quote( $chan )."'" );
		
		if ( database::num_rows( $channel_q ) == 0 )
			return false;
		// chan isnt even registered.
		
		$row = database::fetch( $channel_q );
		
		return $row;
		// if it is registered return the object.
	}
	
	/*
	* is_root
	* 
	* @params
	* $nick - Which nickname to check.
	*/
	static public function is_root( $nick )
	{
		$root_array = (array) core::$config->root;
		
		if ( in_array( $nick, $root_array ) )
			return true;
		else
			return false;
	}
	
		
	/*
	* communicate
	* 
	* @params
	* $from - The bot to send from, should be a valid nick
	* $to - The nick to send the result to
	* $template - The template to use
	* $data - The data to use (optional)
	*/
	static public function communicate( $from, $to, $template, $data = '' )
	{
		$ntemplate = $template;
		
		if ( $data != '' && is_array( $data ) )
		{
			foreach ( $data as $var => $value )
			{
				$ntemplate = str_replace( '{'.$var.'}', $value, $ntemplate );
			}
			// loop through the array replacing each variable.
		}
		// IF there is a $data defined, we do some replacing
		// otherwise leave it alone
		
		if ( $user = self::user_exists( $to, true, array( 'display', 'privmsg' ) ) )
		{
			if ( nickserv::check_flags( $to, array( 'P' ) ) )
				ircd::msg( $from, $to, $ntemplate );
			else
				ircd::notice( $from, $to, $ntemplate );
			// if they are registered, we check their means of contact
		}
		else
		{
			ircd::notice( $from, $to, $ntemplate );
		}
		// if the user isn't registered we notice them by default
	}
	
	
	/*
	* match (private)
	* 
	* @params
	* $hostname - real hostname to check
	* $mask - mask based hostname, *!*@* etc.
	*/
	static public function match( $hostname, $mask )
	{
		if ( strpos( $mask, '~' ) !== false )
			$mask = str_replace( '~', '', $mask );
		if ( strpos( $hostname, '~' ) !== false )
			$hostname = str_replace( '~', '', $hostname );
		// strip out ~ of the ident
		
		if ( strpos( $mask, '/' ) !== false )
			$mask = str_replace( '/', '', $mask );
		if ( strpos( $hostname, '/' ) !== false )
			$hostname = str_replace( '/', '', $hostname );
		// also strip out / which seems to cause problems
		
		$regex = $mask;
		// set our regex.
		
		$regex = '/'.preg_quote( $regex ).'/i';
		$regex = str_replace( '\*', '(.*)', $regex );
		// match the hostname
		
		preg_match( $regex, $hostname, $r_matches );
		// match it
		
		if ( count( $r_matches ) != 0 || $hostname == $mask )
			return true;
		else
			return false;
		// check our results
	}
	
	/*
	* check_mask_ignore (private)
	* 
	* @params
	* $nick - the nick to check
	*/
	static function check_mask_ignore( $nick )
	{
		$ignored_user = database::select( 'ignored_users', array( 'who' ) );
		
		$hostname = core::get_full_hostname( $nick );
		// we generate the hostname
			
		if ( database::num_rows( $ignored_user ) > 0 )
		{
			while ( $ignore = database::fetch( $ignored_user ) )
			{
				if ( $nick == $ignore->who )
					return true;
				elseif ( strpos( $ignore->who, '@' ) && self::match( $hostname, $ignore->who ) )
					return true;
				// we've found a match!
			}
			// loop through records, on the first match we instantly break the loop.
		}
		else
		{
			return false;
		}
		// there are records
	}
	
	/* 
	* valid_host (private)
	*
	* @params
	* $host - domain to check
	*/
	static public function valid_host( $host )
	{
		$split_host = str_split( $host );
		
		foreach ( $split_host as $index => $char )
		{
			if ( strpos( core::$config->settings->hostmap, $char ) === false )
				return false;
			// invalid character
			
			if ( !ereg( "^[a-zA-Z]*$", $char ) && !ereg( "^[a-zA-Z]*$", $split_host[$index + 1] ) )
				return false;
			// invalid format, eg something..fake.vhost
		}
	}
	
	/*
	* valid_email (private)
	* 
	* @params
	* $email - email to check.
	*/
	static public function valid_email( $email )
	{
		if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) )
			return true;
		else
			return false;
		// much easier ^_^
	}
}

// EOF;