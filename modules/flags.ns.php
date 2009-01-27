<?php

/*
* Acora IRC Services
* modules/flags.ns.php: NickServ flags module
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

class ns_flags implements module
{
	
	const MOD_VERSION = '0.0.2';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $flags;
	static public $p_flags;
	// valid flags.
	
	static public $set;
	static public $already_set;
	static public $not_set;
	
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
		modules::init_module( 'ns_flags', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_flags', 'help', &nickserv::$help->NS_HELP_FLAGS_1 );
		nickserv::add_help( 'ns_flags', 'help flags', &nickserv::$help->NS_HELP_FLAGS_ALL );
		nickserv::add_help( 'ns_flags', 'help', &nickserv::$help->NS_HELP_SAFLAGS_1, true );
		nickserv::add_help( 'ns_flags', 'help saflags', &nickserv::$help->NS_HELP_SAFLAGS_ALL, true );
		// add the help
		
		nickserv::add_command( 'flags', 'ns_flags', 'flags_command' );
		nickserv::add_command( 'saflags', 'ns_flags', 'saflags_command' );
		// add the command
		
		self::$flags = '+-eumSP';
		self::$p_flags = 'eum';
		// flags WITH parameters
	}
	
	/*
	* flags_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function flags_command( $nick, $ircdata = array() )
	{
		$flags = $ircdata[0];
		$param = core::get_data_after( &$ircdata, 1 );
		$rparams = explode( '||', $param );
		// get the channel.
		
		if ( !$user = services::user_exists( $nick, false, array( 'display', 'id', 'identified', 'salt' ) ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_UNREGISTERED );
			return false;	
		}
		// find out if our user is registered
		
		if ( $user->identified == 0 )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_NOT_IDENTIFIED );
			return false;
		}
		// are they identified?
		
		$flag_a = array();
		foreach ( str_split( $flags ) as $flag )
		{
			if ( strpos( self::$flags, $flag ) === false )
			{
				services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_UNKNOWN, array( 'flag' => $flag ) );
				return false;
			}
			// flag is invalid.
			
			$flag_a[$flag]++;
			// plus
			
			if ( $flag_a[$flag] > 1 || $flag != '-' && $flag != '+' )
				$flag_a[$flag]--;
			// check for dupes
		}
		// check if the flag is valid
		
		$flags = '';
		foreach ( $flag_a as $flag => $count )
			$flags .= $flag;
		// reconstruct the flags
		
		$flag_array = mode::sort_modes( $flags, false );
		// sort our flags up
		
		foreach ( str_split( self::$p_flags ) as $flag )
		{
			$param_num = strpos( $flag_array['plus'], $flag );
			
			if ( $param_num !== false )
				$params[$flag] = trim( $rparams[$param_num] );
			// we do!
		}
		// check if we have any paramtized flags, eg +me
		
		foreach ( str_split( $flag_array['plus'] ) as $flag )
		{
			// paramtized flags (lowercase) ones come first
			
			// ----------- +e ----------- //
			if ( $flag == 'e' )
			{
				self::set_flag( $nick, $nick, '+e', $params['e'] );
				// +e the target in question
			}
			// ----------- +e ----------- //
			
			// ----------- +u ----------- //
			elseif ( $flag == 'u' )
			{
				self::set_flag( $nick, $nick, '+u', $params['u'] );
				// +u the target in question
			}
			// ----------- +u ----------- //
			
			// non paramatized flags (uppercase)
			
			// ----------- +S ----------- //
			elseif ( $flag == 'S' )
			{
				self::set_flag( $nick, $nick, '+S', '' );
				// +S the target in question
			}
			// ----------- +S ----------- //
			
			// ----------- +P ----------- //
			elseif ( $flag == 'P' )
			{
				self::set_flag( $nick, $nick, '+P', '' );
				// +P the target in question
			}
			// ----------- +P ----------- //
		}
		
		foreach ( str_split( $flag_array['minus'] ) as $flag )
		{
			// paramtized flags (lowercase) ones come first
			
			// ----------- -e ----------- //
			if ( $flag == 'e' )
			{
				self::set_flag( $nick, $nick, '-e', $params['e'] );
				// -e the target in question
			}
			// ----------- -e ----------- //
			
			// ----------- -u ----------- //
			elseif ( $flag == 'u' )
			{
				self::set_flag( $nick, $nick, '-u', $params['u'] );
				// -u the target in question
			}
			// ----------- -u ----------- //
			
			// non paramatized flags (uppercase)
			
			// ----------- -S ----------- //
			elseif ( $flag == 'S' )
			{
				self::set_flag( $nick, $nick, '-S', '' );
				// -S the target in question
			}
			// ----------- -S ----------- //
			
			// ----------- -P ----------- //
			elseif ( $flag == 'P' )
			{
				self::set_flag( $nick, $nick, '-P', '' );
				// -P the target in question
			}
			// ----------- -P ----------- //
		}
		
		if ( isset( self::$set[$nick] ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_SET, array( 'flag' => self::$set[$nick], 'target' => $nick ) );
			unset( self::$set[$nick] );
		}
		// send back the target stuff..
		
		if ( isset( self::$already_set[$nick] ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_ALREADY_SET, array( 'flag' => self::$already_set[$nick], 'target' => $nick ) );
			unset( self::$already_set[$nick] );
		}
		// send back the target stuff..
		
		if ( isset( self::$not_set[$nick] ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_NOT_SET, array( 'flag' => self::$not_set[$nick], 'target' => $nick ) );
			unset( self::$not_set[$nick] );
		}
		// send back the target stuff..			
	}
	
	/*
	* saflags_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function saflags_command( $nick, $ircdata = array() )
	{
		$unick = core::get_nick( &$ircdata, 0 );
		$flags = $ircdata[1];
		$param = core::get_data_after( &$ircdata, 2 );
		$rparams = explode( '||', $param );
		// get the channel.
		
		if ( !$user = services::user_exists( $unick, false, array( 'display', 'id', 'identified', 'salt' ) ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			return false;	
		}
		// find out if our user is registered
		
		if ( services::is_root( $unick ) && !services::is_root( $nick ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// is a non-root trying to change a root's password?
		
		if ( !core::$nicks[$nick]['ircop'] || services::user_exists( $nick, true, array( 'display', 'identified' ) ) === false )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// do we have access to do this?
		
		$flag_a = array();
		foreach ( str_split( $flags ) as $flag )
		{
			if ( strpos( self::$flags, $flag ) === false )
			{
				services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_UNKNOWN, array( 'flag' => $flag ) );
				return false;
			}
			// flag is invalid.
			
			$flag_a[$flag]++;
			// plus
			
			if ( $flag_a[$flag] > 1 || $flag != '-' && $flag != '+' )
				$flag_a[$flag]--;
			// check for dupes
		}
		// check if the flag is valid
		
		$flags = '';
		foreach ( $flag_a as $flag => $count )
			$flags .= $flag;
		// reconstruct the flags
		
		$flag_array = mode::sort_modes( $flags, false );
		// sort our flags up
		
		foreach ( str_split( self::$p_flags ) as $flag )
		{
			$param_num = strpos( $flag_array['plus'], $flag );
			
			if ( $param_num !== false )
				$params[$flag] = trim( $rparams[$param_num] );
			// we do!
		}
		// check if we have any paramtized flags, eg +me
		
		foreach ( str_split( $flag_array['plus'] ) as $flag )
		{
			// paramtized flags (lowercase) ones come first
			
			// ----------- +e ----------- //
			if ( $flag == 'e' )
			{
				self::set_flag( $nick, $unick, '+e', $params['e'] );
				// +e the target in question
			}
			// ----------- +e ----------- //
			
			// ----------- +u ----------- //
			elseif ( $flag == 'u' )
			{
				self::set_flag( $nick, $unick, '+u', $params['u'] );
				// +u the target in question
			}
			// ----------- +u ----------- //
			
			// non paramatized flags (uppercase)
			
			// ----------- +S ----------- //
			elseif ( $flag == 'S' )
			{
				self::set_flag( $nick, $unick, '+S', '' );
				// +S the target in question
			}
			// ----------- +S ----------- //
			
			// ----------- +P ----------- //
			elseif ( $flag == 'P' )
			{
				self::set_flag( $nick, $unick, '+P', '' );
				// +P the target in question
			}
			// ----------- +P ----------- //
		}
		
		foreach ( str_split( $flag_array['minus'] ) as $flag )
		{
			// paramtized flags (lowercase) ones come first
			
			// ----------- -e ----------- //
			if ( $flag == 'e' )
			{
				self::set_flag( $nick, $unick, '-e', $params['e'] );
				// -e the target in question
			}
			// ----------- -e ----------- //
			
			// ----------- -u ----------- //
			elseif ( $flag == 'u' )
			{
				self::set_flag( $nick, $unick, '-u', $params['u'] );
				// -u the target in question
			}
			// ----------- -u ----------- //
			
			// non paramatized flags (uppercase)
			
			// ----------- -S ----------- //
			elseif ( $flag == 'S' )
			{
				self::set_flag( $nick, $unick, '-S', '' );
				// -S the target in question
			}
			// ----------- -S ----------- //
			
			// ----------- -P ----------- //
			elseif ( $flag == 'P' )
			{
				self::set_flag( $nick, $unick, '-P', '' );
				// -P the target in question
			}
			// ----------- -P ----------- //
		}
		
		if ( isset( self::$set[$unick] ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_SET, array( 'flag' => self::$set[$unick], 'target' => $unick ) );
			unset( self::$set[$unick] );
		}
		// send back the target stuff..
		
		if ( isset( self::$already_set[$unick] ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_ALREADY_SET, array( 'flag' => self::$already_set[$unick], 'target' => $unick ) );
			unset( self::$already_set[$unick] );
		}
		// send back the target stuff..
		
		if ( isset( self::$not_set[$unick] ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_NOT_SET, array( 'flag' => self::$not_set[$unick], 'target' => $unick ) );
			unset( self::$not_set[$unick] );
		}
		// send back the target stuff..	
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
	}
	
	/*
	* set_flag (private)
	* 
	* @params
	* $nick - nick
	* $target - who to set the flag on.
	* $flag - flag
	* $param - optional flag parameter.
	*/
	static public function set_flag( $nick, $target, $flag, $param )
	{
		$mode = $flag[0];
		$r_flag = $flag[1];
		// get the real flag, eg. V, v and mode
		
		if ( in_array( $r_flag, str_split( self::$p_flags ) ) && $param == '' && $mode == '+' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_NEEDS_PARAM, array( 'flag' => $flag ) );
			return false;
		}
		// are they issuing a flag, that HAS to have a parameter?
		// only if mode is + and parameter is empty.
		
		if ( $r_flag == 'e' )
			$param_field = 'email';
		if ( $r_flag == 'u' )
			$param_field = 'url';
		if ( $r_flag == 'm' )
			$param_field = 'msn';
		// translate. some craq.
		
		if ( $r_flag == 'e' && $mode == '-' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_CANT_UNSET, array( 'flag' => $flag ) );
			return false;
		}
		// we're not allowed to let +e be unset
		
		if ( in_array( $r_flag, str_split( self::$p_flags ) ) && $mode == '+' )
		{
			$check_e = database::select( 'users_flags', array( 'id', 'email' ), array( 'email', '=', $param ) );
			
			if ( $r_flag == 'e' && database::num_rows( $check_e ) > 0 )
			{
				services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_EMAIL_IN_USE );
				return false;
			}
			// check if the email is in use.
			
			if ( $r_flag == 'e' && services::valid_email( $param ) === false )
			{
				services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_INVALID_E, array( 'flag' => $flag ) );
				return false;
			}
			// is the email invalid?
			
			if ( $r_flag == 'm' && services::valid_email( $param ) === false )
			{
				services::communicate( core::$config->nickserv->nick, $nick, &nickserv::$help->NS_FLAGS_INVALID_E, array( 'flag' => $flag ) );
				return false;
			}
			// is the email invalid?
		}
		// check for invalid values
		
		if ( nickserv::check_flags( $target, array( $r_flag ) ) )
		{
			$nick_flag_q = database::select( 'users_flags', array( 'id', 'nickname', 'flags' ), array( 'nickname', '=', $target ) );
			
			if ( $mode == '-' )
			{
				if ( strpos( self::$set[$target], '-' ) === false )
					self::$set[$target] .= '-';
				// ok, no - ?
				
				$nick_flag = database::fetch( $nick_flag_q );
				// get the flag record
				
				$new_nick_flags = str_replace( $r_flag, '', $nick_flag->flags );
				
				if ( in_array( $r_flag, str_split( self::$p_flags ) ) )
				{
					database::update( 'users_flags', array( 'flags' => $new_nick_flags, $param_field => $param ), array( 'nickname', '=', $target ) );	
					// update the row with the new flags.
				}
				else
				{
					database::update( 'users_flags', array( 'flags' => $new_nick_flags ), array( 'nickname', '=', $target ) );	
					// update the row with the new flags.
				}
				
				self::$set[$target] .= $r_flag;
				// some magic :O
				return true;
			}
			
			if ( $mode == '+' )
			{
				if ( !in_array( $r_flag, str_split( self::$p_flags ) ) )
				{
					self::$already_set[$target] .= $r_flag;
					// some magic :O
					return false;
				}
				
				if ( strpos( self::$set[$target], '+' ) === false )
					self::$set[$target] .= '+';
				// ok, no + ?
				
				$nick_flag = database::fetch( $nick_flag_q );
				// get the flag record
				
				database::update( 'user_flags', array( $param_field => $param ), array( 'nickname', '=', $target ) );	
				// update the row with the new flags.
				
				self::$set[$target] .= $r_flag;
				// some magic :O
				return true;
			}
			// the flag IS set, so now we check whether they are trying to -, or + it
			// if they are trying to - it, go ahead, error if they are trying to + it.
		}
		else
		{
			$nick_flag_q = database::select( 'users_flags', array( 'id', 'nickname', 'flags' ), array( 'nickname', '=', $target ) );
			
			if ( $mode == '+' )
			{
				if ( strpos( self::$set[$target], '+' ) === false )
					self::$set[$target] .= '+';
				// ok, no + ?
				
				$nick_flag = database::fetch( $nick_flag_q );
				// get the flag record
				
				$new_nick_flags = $nick_flag->flags.$r_flag;
				
				if ( !in_array( $r_flag, str_split( self::$p_flags ) ) )
				{
					database::update( 'users_flags', array( 'flags' => $new_nick_flags ), array( 'nickname', '=', $target ) );	
					// update the row with the new flags.
					
					self::$set[$target] .= $r_flag;
					// some magic :O
					return true;
				}
				else
				{
					database::update( 'users_flags', array( 'flags' => $new_nick_flags, $param_field => $param ), array( 'nickname', '=', $target ) );	
					// update the row with the new flags.
					
					self::$set[$target] .= $r_flag;
					// some magic :O
					return true;
				}
			}
			// the flag ISNT set, so now we check whether they are trying to -, or + it
			// if they are trying to + it, go ahead, error if they are trying to _ it.
			
			if ( $mode == '-' )
			{
				self::$not_set[$target] .= $r_flag;
				// some magic :O
				return false;
			}
		}
		// check if the flag is already set?
	}
}

// EOF;