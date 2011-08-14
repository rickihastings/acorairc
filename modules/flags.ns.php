<?php

/*
* Acora IRC Services
* modules/flags.ns.php: NickServ flags module
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

class ns_flags extends module
{
	
	const MOD_VERSION = '0.1.6';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $flags;
	static public $p_flags;
	// valid flags.
	
	static public $set;
	static public $already_set;
	static public $not_set;
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'NICK_UNREGISTERED'	=> 2,
		'INVALID_FLAG'		=> 3,
	);
	// return codes
	
	/*
	* modload (private)
	* 
	* @params
	* void
	*/
	static public function modload()
	{
		modules::init_module( 'ns_flags', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_flags', 'help', nickserv::$help->NS_HELP_FLAGS_1, true );
		nickserv::add_help( 'ns_flags', 'help', nickserv::$help->NS_HELP_SAFLAGS_1, true, 'nickserv_op' );
		nickserv::add_help( 'ns_flags', 'help saflags', nickserv::$help->NS_HELP_SAFLAGS_ALL, false, 'nickserv_op' );
		// add the help
		
		nickserv::add_help_fix( 'ns_flags', 'prefix', 'help flags', nickserv::$help->NS_HELP_FLAGS_ALL_PRE );
		nickserv::add_help_fix( 'ns_flags', 'suffix', 'help flags', nickserv::$help->NS_HELP_FLAGS_ALL_SUF );
		// add help prefixes and stuff
		
		nickserv::add_command( 'flags', 'ns_flags', 'flags_command' );
		nickserv::add_command( 'saflags', 'ns_flags', 'saflags_command' );
		// add the command
		
		$flag_structure = array( 'array' => &nickserv::$flags, 'module' => __CLASS__, 'command' => array( 'help flags' ), 'type' => 'nsflags' );
		services::add_flag( $flag_structure, 'e', nickserv::$help->NS_FLAGS_e );
		services::add_flag( $flag_structure, 'u', nickserv::$help->NS_FLAGS_u );
		services::add_flag( $flag_structure, 's', nickserv::$help->NS_FLAGS_s );
		services::add_flag( $flag_structure, 'S', nickserv::$help->NS_FLAGS_S );
		services::add_flag( $flag_structure, 'P', nickserv::$help->NS_FLAGS_P );
		services::add_flag( $flag_structure, 'H', nickserv::$help->NS_FLAGS_H );
		// add flags
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
		if ( !core::$nicks[$nick]['identified'] )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NOT_IDENTIFIED );
			return false;
		}
		// are they identified?
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'], 'command' => 'FLAGS' );
		$return_data = self::_set_flags_nick( $input, $nick, $nick, $ircdata[0], core::get_data_after( $ircdata, 0 ), core::get_data_after( $ircdata, 1 ) );
		// call _set_flags_nick
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		if ( ( core::$nicks[$nick]['account'] != $ircdata[0] && services::has_privs( $ircdata[0] ) ) || !services::oper_privs( $nick, 'nickserv_op' ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// they don't even have access to do this.
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'], 'command' => 'SAFLAGS' );
		$return_data = self::_set_flags_nick( $input, $nick, $ircdata[0], $ircdata[1], core::get_data_after( $ircdata, 1 ), core::get_data_after( $ircdata, 2 ) );
		// call _set_flags_nick
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _set_flags_nick (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $unick - The name of the account to change flags
	* $flags - The flags, like '+ei'
	* $full_flags - The flags and params, like '+ei email@addr.com'
	* $params - The params, like 'email@addr.com'
	*/
	static public function _set_flags_nick( $input, $nick, $unick, $flags, $full_flags, $param )
	{
		$return_data = module::$return_data;
		$rparams = explode( '||', $param );
		$user = database::select( 'users', array( 'display', 'id', 'salt' ), array( 'display', '=', $unick ) );
		
		if ( $unick == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => $input['command'] ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
		
		if ( database::num_rows( $user ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
			return $return_data;
		}
		// look for the user
		
		if ( $flags == '' )
		{
			$flags_q = database::select( 'users_flags', array( 'id', 'nickname', 'flags' ), array( 'nickname', '=', $unick ) );
			$flags_q = database::fetch( $flags_q );
			// get the flag records
			
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_FLAGS_LIST, array( 'nick' => $flags_q->nickname, 'flags' => $flags_q->flags ) );
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_FLAGS_LIST2, array( 'nick' => $flags_q->nickname ) );
			$return_data[CMD_DATA] = array( 'nick' => $flags_q->nickname, 'flags' => $flags_q->flags );
			$return_data[CMD_SUCCESS] = true;
			return $return_data;
		}
		// are no flags sent? ie they're using /ns flags, asking for the current flags.
		
		$flag_a = array();
		foreach ( str_split( $flags ) as $flag )
		{
			if ( $flag != '-' && $flag != '+' && !isset( nickserv::$flags[$flag] ) )
			{
				$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_FLAGS_UNKNOWN, array( 'flag' => $flag ) );
				$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_FLAG;
				return $return_data;
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
		
		$flag_array = mode::sort_modes( $full_flags, false );
		// sort our flags up
		
		$param_num = 0;
		foreach ( str_split( $flags ) as $flag )
		{
			if ( !nickserv::$flags[$flag]['has_param'] )
				continue;
			// not a parameter-ized flag
			
			$params[$flag] = trim( $rparams[$param_num] );
			$param_num++;
			// we do!
		}
		// check if we have any paramtized flags, eg +me
		
		foreach ( str_split( $flag_array['plus'] ) as $flag )
			self::_set_flags( $nick, $unick, $flag, '+', $params, $return_data );
		
		foreach ( str_split( $flag_array['minus'] ) as $flag )
			self::_set_flags( $nick, $unick, $flag, '-', $params, $return_data );
		
		if ( isset( self::$set[$unick] ) )
		{
			$response .= services::parse( nickserv::$help->NS_FLAGS_SET, array( 'flag' => self::$set[$unick], 'target' => $unick ) );
			$response .= ( isset( self::$already_set[$unick] ) || isset( self::$not_set[$unick] ) || isset( $return_data['FALSE_RESPONSE'] ) ) ? ', ' : '';
			$return_data[CMD_DATA]['set'] = self::$set[$unick];
			unset( self::$set[$unick] );
		}
		// send back the target stuff..
		
		if ( isset( self::$already_set[$unick] ) )
		{
			$response .= services::parse( nickserv::$help->NS_FLAGS_ALREADY_SET, array( 'flag' => self::$already_set[$unick], 'target' => $unick ) );
			$response .= ( isset( self::$not_set[$unick] ) || isset( $return_data['FALSE_RESPONSE'] ) ) ? ', ' : '';
			$return_data[CMD_DATA]['already_set'] = self::$already_set[$unick];
			unset( self::$already_set[$unick] );
		}
		// send back the target stuff..
		
		if ( isset( self::$not_set[$unick] ) )
		{
			$response .= services::parse( nickserv::$help->NS_FLAGS_NOT_SET, array( 'flag' => self::$not_set[$unick], 'target' => $unick ) );
			$response .= ( isset( $return_data['FALSE_RESPONSE'] ) ) ? ', ' : '';
			$return_data[CMD_DATA]['not_set'] = self::$not_set[$chan];
			unset( self::$not_set[$unick] );
		}
		// send back the target stuff..
		
		if ( isset( $return_data['FALSE_RESPONSE'] ) )
		{
			$response .= $return_data['FALSE_RESPONSE'];
			unset( $return_data['FALSE_RESPONSE'] );
		}
		// do we have any additional responses?
		
		$return_data[CMD_RESPONSE][] = $response;
		$return_data[CMD_DATA]['nick'] = $unick;
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return data
	}
	
	/*
	* _set_flags
	* 
	* $nick, $unick, $mode, $params, &$return_data
	*/
	public function _set_flags( $nick, $unick, $flag, $mode, $params, &$return_data )
	{
		if ( isset( nickserv::$flags[$flag] ) )
		{
			$flag_data = nickserv::$flags[$flag];
			// get the flag data
			
			self::set_flag( $nick, $unick, $mode.$flag, $params[$flag], $return_data );
			// pass our data to set_flag
			
			if ( $mode == '+' && $flag_data[FLAG_SET_METHOD] != null )
				call_user_func_array( $flag_data[FLAG_SET_METHOD], array( $nick, $unick, $flag, $mode, $params, $return_data ) );
			if ( $mode == '-' && $flag_data[FLAG_UNSET_METHOD] != null )
				call_user_func_array( $flag_data[FLAG_UNSET_METHOD], array( $nick, $unick, $flag, $mode, $params, $return_data ) );
			// call any set/unset methods
		}
		// check if flag exists
	}
	
	/*
	* set_flag (private)
	* 
	* @params
	* $nick - nick
	* $target - who to set the flag on.
	* $flag - flag
	* $param - optional flag parameter.
	* &$return_data - a valid array from module::$return_data
	*/
	static public function set_flag( $nick, $target, $flag, $param, &$return_data )
	{
		$mode = $flag[0];
		$r_flag = $flag[1];
		// get the real flag, eg. V, v and mode
		
		if ( nickserv::$flags[$r_flag]['has_param'] && $param == '' && $mode == '+' )
		{
			$return_data['FALSE_RESPONSE'] = services::parse( nickserv::$help->NS_FLAGS_NEEDS_PARAM, array( 'flag' => $flag ) );
			return false;
		}
		// are they issuing a flag, that HAS to have a parameter?
		// only if mode is + and parameter is empty.
		
		if ( $r_flag == 'e' )
			$param_field = 'email';
		if ( $r_flag == 'u' )
			$param_field = 'url';
		if ( $r_flag == 's' )
			$param_field = 'secured_time';
		// translate. some craq.
		
		if ( $r_flag == 'e' && $mode == '-' )
		{
			$return_data['FALSE_RESPONSE'] = services::parse( nickserv::$help->NS_FLAGS_CANT_UNSET, array( 'flag' => $flag ) );
			return false;
		}
		// we're not allowed to let +e be unset
		
		if ( nickserv::$flags[$r_flag]['has_param'] && $mode == '+' )
		{
			$check_e = database::select( 'users_flags', array( 'id', 'email' ), array( 'email', '=', $param ) );
			
			if ( $r_flag == 'e' && database::num_rows( $check_e ) > 0 )
			{
				$return_data['FALSE_RESPONSE'] = services::parse( nickserv::$help->NS_EMAIL_IN_USE );
				return false;
			}
			// check if the email is in use.
			
			if ( $r_flag == 'e' && services::valid_email( $param ) === false )
			{
				$return_data['FALSE_RESPONSE'] = services::parse( nickserv::$help->NS_FLAGS_INVALID_E, array( 'flag' => $flag ) );
				return false;
			}
			// is the email invalid?
			
			if ( $r_flag == 's' && ( $param < 5 || $param > core::$config->nickserv->secure_time ) )
			{
				$return_data['FALSE_RESPONSE'] = services::parse( nickserv::$help->NS_FLAGS_INVALID_S, array( 'flag' => $flag, 'limit' => core::$config->nickserv->secure_time ) );
				return false;
			}
			// is secure time valid?
		}
		// check for invalid values
		
		if ( nickserv::check_flags( core::$nicks[$target]['account'], array( $r_flag ) ) )
		{
			$nick_flag_q = database::select( 'users_flags', array( 'id', 'nickname', 'flags' ), array( 'nickname', '=', core::$nicks[$target]['account'] ) );
			
			if ( $mode == '-' )
			{
				if ( strpos( self::$set[$target], '-' ) === false )
					self::$set[$target] .= '-';
				// ok, no - ?
				
				$nick_flag = database::fetch( $nick_flag_q );
				// get the flag record
				
				$new_nick_flags = str_replace( $r_flag, '', $nick_flag->flags );
				
				if ( nickserv::$flags[$r_flag]['has_param'] )
				{
					database::update( 'users_flags', array( 'flags' => $new_nick_flags, $param_field => $param ), array( 'nickname', '=', core::$nicks[$target]['account'] ) );	
					// update the row with the new flags.
				}
				else
				{
					database::update( 'users_flags', array( 'flags' => $new_nick_flags ), array( 'nickname', '=', core::$nicks[$target]['account'] ) );	
					// update the row with the new flags.
				}
				
				self::$set[$target] .= $r_flag;
				// some magic :O
				return true;
			}
			
			if ( $mode == '+' )
			{
				if ( !nickserv::$flags[$r_flag]['has_param'] )
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
				
				database::update( 'user_flags', array( $param_field => $param ), array( 'nickname', '=', core::$nicks[$target]['account'] ) );	
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
			$nick_flag_q = database::select( 'users_flags', array( 'id', 'nickname', 'flags' ), array( 'nickname', '=', core::$nicks[$target]['account'] ) );
			
			if ( $mode == '+' )
			{
				if ( strpos( self::$set[$target], '+' ) === false )
					self::$set[$target] .= '+';
				// ok, no + ?
				
				$nick_flag = database::fetch( $nick_flag_q );
				// get the flag record
				
				$new_nick_flags = $nick_flag->flags.$r_flag;
				
				if ( !nickserv::$flags[$r_flag]['has_param'] )
				{
					database::update( 'users_flags', array( 'flags' => $new_nick_flags ), array( 'nickname', '=', core::$nicks[$target]['account'] ) );	
					// update the row with the new flags.
					
					self::$set[$target] .= $r_flag;
					// some magic :O
					return true;
				}
				else
				{
					database::update( 'users_flags', array( 'flags' => $new_nick_flags, $param_field => $param ), array( 'nickname', '=', core::$nicks[$target]['account'] ) );	
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
