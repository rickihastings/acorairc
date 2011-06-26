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

class os_akill implements module
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
		modules::init_module( 'os_akill', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_akill', 'help', operserv::$help->OS_HELP_AKILL_1 );
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
			$hostname = $ircdata[1];
			$rexpire = $ircdata[2]; 
			$reason = core::get_data_after( $ircdata, 3 );
			
			if ( !services::oper_privs( $nick, 'global_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// you have to be root to add/del an akill
			
			$reason = ( $reason == '' ) ? 'No reason' : $reason;
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
					$expire = $expire + $days;
				}
				if ( isset( $parsed[$fi_] ) && $parsed[$fi_] == 'h' )
				{
					$hours = ( $p_ * 3600 );
					$expire = $expire + $hours;
				}
				if ( isset( $parsed[$fi_] ) && $parsed[$fi_] == 'm' )
				{
					$minutes = ( $p_ * 60 );
					$expire = $expire + $minutes;
				}
				// days hours and mins converted to seconds
			}
			// loop through calculating it into seconds
			
			if ( trim( $hostname ) == '' || count( $parsed ) == 0 )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'AKILL' ) );
				return false;
			}
			// wrong syntax
			
			$hostname = ( strpos( $hostname, '@' ) === false ) ? '*!*@'.$hostname : $hostname;
			
			self::_add_akill( $nick, $hostname, $expire, $reason );
			// add the ban
			
			if ( $expire != 0 )
				timer::add( array( 'os_akill', '_del_akill', array( $nick, $hostname, true ) ), $expire, 1 );
			// add a timer to remove the ban.
		}
		// mode is add
		elseif ( $mode == 'del' )
		{
			$hostname = $ircdata[1];
			$hostname = ( strpos( $hostname, '@' ) === false ) ? '*!*@'.$hostname : $hostname;
			// get our vars
		
			if ( !services::oper_privs( $nick, 'global_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// you have to be root to add/del an akill
			
			self::_del_akill( $nick, $hostname, false );
			// call del ip akill
		}
		// mode is del
		elseif ( $mode == 'list' )
		{
			self::_list_akill( $nick );
			// call list akill
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
	* main (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	public function main( $ircdata, $startup = false )
	{
		$connect_data = ircd::on_connect( $ircdata );
		if ( $connect_data !== false )
		{
			$nick = $connect_data['nick'];
			$kill = false;
			// some vars
			
			if ( database::num_rows( core::$session_rows ) == 0 )
				return;
			// determine match if there is no session exceptions
			
			while ( $sessions = database::fetch( core::$session_rows ) )
			{
				if ( $sessions->akill == 0 )
					continue;
				// check limits
				
				if ( services::match( $connect_data['host'], $sessions->hostmask ) )
					continue;
				// no akill found, check next one.
				
				$reason = $sessions->description;
				$kill = true;
				break;
				// we've found an akill, let's do some KILLING!
			}
			// check the sessions database
			
			if ( $kill )
				ircd::kill( core::$config->operserv->nick, $nick, 'AKILLED: '.$reason );
			// they're banned for some reason.
		}
	}
	
	/*
	* _add_akill (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $hostname - The hostname to akill
	* $time - time
	* $description - Description
	*/
	static public function _add_akill( $nick, $hostname, $time, $reason )
	{
		$check_record_q = database::select( 'sessions', array( 'hostmask', 'akill' ), array( 'hostmask', '=', $hostname, 'AND', 'akill', '=', 1 ) );
		$expire = ( $time == 0 ) ? 'Never' : core::format_time( $time );
		
		if ( database::num_rows( $check_record_q ) == 0 )
		{
			database::insert( 'sessions', array( 'nick' => $nick, 'hostmask' => $hostname, 'description' => $reason, 'expire' => $time, 'time' => core::$network_time, 'akill' => 1, 'limit' => 0 ) );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_AKILL_ADD, array( 'hostname' => $hostname, 'expire' => $expire ) );
			core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') added an auto kill for ('.$hostname.') to expire in ('.$expire.')' );
			// as simple, as.
			
			foreach ( core::$nicks as $unick => $data )
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
			// loop users and autokill them. excluding ircops
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_AKILL_EXISTS, array( 'hostname' => $hostname ) );
			// already got an exception 
		}
	}
	
	/*
	* _del_akill (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $hostname - The host to del except
	* $expired - true
	*/
	static public function _del_akill( $nick, $hostname, $expired = true )
	{
		$check_record_q = database::select( 'sessions', array( 'hostmask', 'akill' ), array( 'hostmask', '=', $hostname, 'AND', 'akill', '=', 1 ) );
		
		if ( database::num_rows( $check_record_q ) > 0 )
		{
			database::delete( 'sessions', array( 'hostmask', '=', $hostname ) );
			
			if ( $expired )
			{
				core::alog( core::$config->operserv->nick.': Auto kill for ('.$hostname.') expired' );
			}
			else
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_AKILL_DEL, array( 'hostname' => $hostname ) );
				core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') removed the auto kill for ('.$hostname.')' );
			}
			// is it expiring or what??
		}
		else
		{
			if ( !$expired )
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_AKILL_NOEXISTS, array( 'hostname' => $hostname ) );
			// already got an exception
		}
	}
	
	/*
	* _list_akill (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	*/
	static public function _list_akill( $nick )
	{
		$check_record_q = database::select( 'sessions', array( 'nick', 'hostmask', 'description', 'expire' ), array( 'akill', '=', 1 ) );
		
		if ( database::num_rows( $check_record_q ) > 0 )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_AKILL_LIST_T );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_AKILL_LIST_D );
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
				
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_AKILL_LIST, array( 'num' => $num, 'hostname' => $hostmask, 'nick' => $session->nick, 'expire' => $expire, 'desc' => $session->description ) );
			}
			// loop through the records
			
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_AKILL_LIST_D );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_AKILL_LIST_B, array( 'num' => $x ) );
		}
		// display list
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_AKILL_LIST_B, array( 'num' => 0 ) );
		}
		// empty list. display the config record*/
	}
}

// EOF;