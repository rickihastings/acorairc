<?php

/*
* Acora IRC Services
* core/services.php: Collection of functions for basic usage
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
class services
{
	
	public function __construct() { }
	// __construct, makes everyone happy.
	
	/*
	* oper_privs
	*
	* @params
	* $nick - The nickname to check.
	* $privs - Privs to check
	*/
	static public function oper_privs( $nick, $privs )
	{
		if ( !core::$nicks[$nick]['ircop'] || !core::$nicks[$nick]['identified'] )
			return false;
	
		foreach ( core::$config->opers as $i => $data )
		{
			$split = explode( ':', $data );
			
			if ( strtolower( $split[0] ) != strtolower( $nick ) )
				continue;
			// no privs here.
			
			unset( $split[0] );
			
			if ( in_array( $privs, $split ) )
				return true;
			// we've found some privs for the nick, let's get the privs and try match them against $privs
		}
		// loop for privs!
		
		return false;
	}
	
	/*
	* has_privs
	*
	* @params
	* $nick - The nickname to check.
	*/
	static public function has_privs( $nick )
	{
		foreach ( core::$config->opers as $i => $data )
		{
			$split = explode( ':', $data );
			
			if ( strtolower( $split[0] ) == strtolower( $nick ) )
				return true;
			// no privs here.
		}
	
		return false;
	}
	
	/*
	* show_privs
	*
	* @params
	* $nick - The nickname to check.
	*/
	static public function show_privs( $nick )
	{
		foreach ( core::$config->opers as $i => $data )
		{
			$split = explode( ':', $data );
			
			if ( strtolower( $split[0] ) == strtolower( $nick ) )
			{
				unset( $split[0] );
				return implode( ':', $split );
			}
			// no privs here.
		}
	
		return false;
	}
	
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
		if ( $identified && !core::$nicks[$nick]['identified'] )
			return false;
			
		$user_q = database::select( 'users', $array, array( 'display', '=', $nick ) );

		if ( database::num_rows( $user_q ) == 0 )
			return false;
		// user isnt registered || isnt identified
		
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
			$user_q = database::select( 'users', $array, array( 'id', '=', $uid, 'AND', 'identified', '=', '1' ) );
		else
			$user_q = database::select( 'users', $array, array( 'id', '=', $uid ) );
			
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
		$channel_q = database::select( 'chans', $array, array( 'channel', '=', $chan ) );
		
		if ( database::num_rows( $channel_q ) == 0 )
			return false;
		// chan isnt even registered.
		
		$row = database::fetch( $channel_q );
		
		return $row;
		// if it is registered return the object.
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
		
		$ntemplate = str_replace( '{chanserv}', core::$config->chanserv->nick, $ntemplate );
		$ntemplate = str_replace( '{nickserv}', core::$config->nickserv->nick, $ntemplate );
		$ntemplate = str_replace( '{operserv}', core::$config->operserv->nick, $ntemplate );
		// replace *Serv with it's actual name
		
		if ( $data != '' && is_array( $data ) )
		{
			foreach ( $data as $var => $value )
				$ntemplate = str_replace( '{'.$var.'}', $value, $ntemplate );
			// loop through the array replacing each variable.
		}
		// IF there is a $data defined, we do some replacing
		// otherwise leave it alone
		
		if ( core::$nicks[$nick]['identified'] && nickserv::check_flags( $to, array( 'P' ) ) )
			ircd::msg( $from, $to, $ntemplate );
		else
			ircd::notice( $from, $to, $ntemplate );
		// if they are registered, we check their means of contact
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
			
			if ( !preg_match( "/^[a-z0-9]*$/i", $char ) && !preg_match( "/^[a-z0-9]*$/i", $split_host[$index + 1] ) )
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