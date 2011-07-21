<?php

/*
* Acora IRC Services
* modules/akill.os.php: OperServ akill module
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

class os_akill extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'AKILL_EXISTS'		=> 2,
		'AKILL_NO_EXIST'	=> 3,
		'AKILL_LIST_EMPTY' 	=> 4,
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
		modules::init_module( 'os_akill', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		operserv::add_help( 'os_akill', 'help', operserv::$help->OS_HELP_AKILL_1, true );
		operserv::add_help( 'os_akill', 'help akill', operserv::$help->OS_HELP_AKILL_ALL );
		// add the help
		
		operserv::add_command( 'akill', 'os_akill', 'akill_command' );
		// add the command
		
		$check_record_q = database::select( 'sessions', array( 'nick', 'hostmask', 'expire', 'time' ), array( 'akill', '=', 1 ) );
		
		if ( database::num_rows( $check_record_q ) == 0 )
			return;
		
		while ( $session = database::fetch( $check_record_q ) )
		{
			if ( $session->expire == 0 )
				continue;
			$expire = ( $session->time + $session->expire ) - core::$network_time;
			timer::add( array( 'os_akill', '_del_akill', array( $session->nick, $session->hostmask, true ) ), $expire, 1 );
		}
		// get akill sessions and re-add a timer
	}
	
	/*
	* akill_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function akill_command( $nick, $ircdata = array() )
	{
		$mode = strtolower( $ircdata[0] );
		
		if ( $mode == 'add' )
		{
			if ( !services::oper_privs( $nick, 'global_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// you have to be root to add/del an akill
			
			$reason = ( core::get_data_after( $ircdata, 3 ) == '' ) ? 'No reason' : core::get_data_after( $ircdata, 3 );
			$return_data = self::_add_akill( true, $nick, $ircdata[1], $ircdata[2], $reason );
			// add the ban and get the response from add_akill
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		// mode is add
		elseif ( $mode == 'del' )
		{
			if ( !services::oper_privs( $nick, 'global_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// you have to be root to add/del an akill
			
			$return_data = self::_del_akill( true, $nick, $ircdata[1], false );
			// call del akill
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		// mode is del
		elseif ( $mode == 'list' )
		{
			$return_data = self::_list_akill( true );
			// call list akill
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		// mode is list
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'AKILL' ) );
			return false;
		}
		// no comprende?
	}
	
	/*
	* on_connect (event hook)
	*/
	static public function on_connect( $connect_data )
	{
		if ( empty( operserv::$session_rows ) )
			return false;
		// determine match if there is no session exceptions
		
		$nick = $connect_data['nick'];
		
		foreach ( operserv::$session_rows as $i => $sessions )
		{
			if ( services::match( $connect_data['host'], $sessions->hostmask ) )
				continue;
			// no akill found, check next one.
			
			ircd::kill( core::$config->operserv->nick, $nick, 'AKILLED: '.$sessions->description );
			// they're banned for some reason.
			break;
			// we've found an akill, let's do some KILLING!
		}
		// check the sessions database
	}
	
	/*
	* _add_akill (private)
	* 
	* @params
	* $internal - Should ALWAYS be true when calling from a command or likewise
	* $nick - The nick of the person issuing the command
	* $hostname - The hostname to akill
	* $time - time
	* $description - Description
	*/
	static public function _add_akill( $internal, $nick, $hostname, $rexpire, $reason )
	{
		$return_data = module::$return_data;
	
		$days = $hours = $minutes = $expire = 0;
		// grab the reason etc
		
		$parsed = preg_split( '/(d|h|m)/', $rexpire, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		// format %%d%%h%%m to a timestamp
		
		$fi_ = 0;
		foreach ( $parsed as $i_ => $p_ )
		{
			$fi_++;
			if ( isset( $parsed[$fi_] ) && $parsed[$fi_] == 'd' )
			{
				$days = ( $p_ * 86400 );
				$time = $time + $days;
			}
			if ( isset( $parsed[$fi_] ) && $parsed[$fi_] == 'h' )
			{
				$hours = ( $p_ * 3600 );
				$time = $time + $hours;
			}
			if ( isset( $parsed[$fi_] ) && $parsed[$fi_] == 'm' )
			{
				$minutes = ( $p_ * 60 );
				$time = $time + $minutes;
			}
			// days hours and mins converted to seconds
		}
		// loop through calculating it into seconds
	
		if ( trim( $hostname ) == '' || count( $parsed ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'AKILL' ) );
			$return_data[CMD_FAILCODE] =  self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
			
		$hostname = ( strpos( $hostname, '@' ) === false ) ? '*!*@'.$hostname : $hostname;
		$check_record_q = database::select( 'sessions', array( 'hostmask', 'akill' ), array( 'hostmask', '=', $hostname, 'AND', 'akill', '=', 1 ) );
		$expire = ( $time == 0 ) ? 'Never' : core::format_time( $time );
		// set some vars
		
		if ( database::num_rows( $check_record_q ) != 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_AKILL_EXISTS, array( 'hostname' => $hostname ) );
			$return_data[CMD_FAILCODE] =  self::$return_codes->AKILL_EXISTS;
			return $return_data;
		}
		// already got an exception 
		
		database::insert( 'sessions', array( 'nick' => $nick, 'hostmask' => $hostname, 'description' => $reason, 'expire' => $time, 'time' => core::$network_time, 'akill' => 1, 'limit' => 0 ) );
		
		if ( $internal )
			core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') added an auto kill for ('.$hostname.') to expire in ('.$expire.')' );
		// as simple, as.
		
		while ( list( $unick, $data ) = each( core::$nicks ) )
		{
			if ( $data['ircop'] )
				continue;
			// skip ircops
			
			if ( services::match( $data['oldhost'], $mask ) )
			{
				ircd::kill( core::$config->operserv->nick, $unick, 'AKILLED: '.$reason );
				core::alog( 'Auto kill matched ('.core::get_full_hostname( $unick ).'). Killed client' );
			}
			// search old vhost incase they've got a vee-host
		}
		reset( core::$nicks );
		// loop users and autokill them. excluding ircops
		
		$query = database::select( 'sessions', array( 'nick', 'ip_address', 'hostmask', 'description', 'limit', 'time', 'expire', 'akill' ) );
		while ( $session = database::fetch( $query ) )
			operserv::$session_rows[] = $session;
		// re read the session array.
		
		if ( $time != 0 )
			timer::add( array( 'os_akill', '_del_akill', array( $nick, $hostname, true ) ), $time, 1 );
		// add a timer to remove the ban.
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_AKILL_ADD, array( 'hostname' => $hostname, 'expire' => $expire ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* _del_akill (private)
	* 
	* @params
	* $internal - Should ALWAYS be true when calling from a command or likewise
	* $nick - The nick of the person issuing the command
	* $hostname - The host to del except
	* $expired - true
	*/
	static public function _del_akill( $internal, $nick, $hostname, $expired = true )
	{
		$return_data = module::$return_data;
		
		$hostname = ( strpos( $hostname, '@' ) === false ) ? '*!*@'.$hostname : $hostname;
		$check_record_q = database::select( 'sessions', array( 'hostmask', 'akill' ), array( 'hostmask', '=', $hostname, 'AND', 'akill', '=', 1 ) );
		// set some gear
		
		if ( database::num_rows( $check_record_q ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = ( $expired ) ? '' : services::parse( operserv::$help->OS_AKILL_NOEXISTS, array( 'hostname' => $hostname ) );
			$return_data[CMD_FAILCODE] =  self::$return_codes->AKILL_NO_EXIST;
			return $return_data;
		}
		// already got an exception
		
		database::delete( 'sessions', array( 'hostmask', '=', $hostname ) );
		
		if ( $internal && $expired )
			core::alog( core::$config->operserv->nick.': Auto kill for ('.$hostname.') expired' );
		else
		{
			if ( $internal )
				core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') removed the auto kill for ('.$hostname.')' );
		
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_AKILL_DEL, array( 'hostname' => $hostname ) );
			$return_data[CMD_SUCCESS] = true;
		}
		// is it expiring or what??
		
		$query = database::select( 'sessions', array( 'nick', 'ip_address', 'hostmask', 'description', 'limit', 'time', 'expire', 'akill' ) );
		while ( $session = database::fetch( $query ) )
			operserv::$session_rows[] = $session;
		// re read the session array.
		
		return $return_data;
		// return data back
	}
	
	/*
	* _list_akill (private)
	* 
	* @params
	* $internal - Should ALWAYS be true when calling from a command or likewise
	*/
	static public function _list_akill( $internal )
	{
		$return_data = module::$return_data;
	
		$check_record_q = database::select( 'sessions', array( 'nick', 'hostmask', 'description', 'expire' ), array( 'akill', '=', 1 ) );
		if ( database::num_rows( $check_record_q ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_AKILL_LIST_B, array( 'num' => 0 ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->AKILL_LIST_EMPTY;
			return $return_data;
		}
		// empty list. display the config record
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_AKILL_LIST_T );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_AKILL_LIST_D );
		// t-o-l
		
		$x = 0;
		while ( $session = database::fetch( $check_record_q ) )
		{
			$hostmask = $session->hostmask;
			$expire = ( $session->expire == 0 ) ? 'Never' : core::format_time( $session->expire );
			$x++;
			
			$num = $x;
			$y_i = strlen( $num );
			for ( $i_i = $y_i; $i_i <= 5; $i_i++ )
				$num .= ' ';
			// tidy tidy, num
			
			if ( !isset( $session->hostmask[50] ) )
			{
				$y = strlen( $session->hostmask );
				for ( $i = $y; $i <= 49; $i++ )
					$hostmask .= ' ';
			}
			// this is just a bit of fancy fancy, so everything displays neat
			
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_AKILL_LIST, array( 'num' => $num, 'hostname' => $hostmask, 'nick' => $session->nick, 'expire' => $expire, 'desc' => $session->description ) );
			$return_data[CMD_DATA][] = array( 'hostname' => $hostmask, 'added_by' => $session->nick, 'expire' => $expire, 'reason' => $session->description );
		}
		// loop through the records
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_AKILL_LIST_D );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_AKILL_LIST_B, array( 'num' => $x ) );
		// display list
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return shiz
	}
}

// EOF;