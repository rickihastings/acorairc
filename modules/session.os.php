<?php

/*
* Acora IRC Services
* modules/session.os.php: OperServ session module
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

class os_session implements module
{
	
	const MOD_VERSION = '0.0.1';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $session_limit;
	
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
		modules::init_module( 'os_session', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_session', 'help', operserv::$help->OS_HELP_SESSION_1 );
		operserv::add_help( 'os_session', 'help session', operserv::$help->OS_HELP_SESSION_ALL );
		// add the help
		
		operserv::add_command( 'session', 'os_session', 'session_command' );
		// add the command
		
		self::$session_limit = ( !isset( core::$config->operserv->session_limit ) || core::$config->operserv->session_limit <= 0 ) ? 5 : core::$config->operserv->session_limit;
	}
	
	/*
	* session_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function session_command( $nick, $ircdata = array() )
	{
		$mode = strtolower( $ircdata[0] );
		
		if ( $mode == 'add' )
		{
			$ip_address = $ircdata[1];
			$limit = $ircdata[2];
			$description = core::get_data_after( $ircdata, 3 );
			// get our vars
			
			if ( !services::is_root( $nick ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// you have to be root to add/del an exception
			
			if ( trim( $ip_address ) == '' || trim( $description ) == '' || !is_numeric( $limit ) || !filter_var( $ip_address, FILTER_VALIDATE_IP ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'SESSION' ) );
				return false;
			}
			// wrong syntax
			
			if ( $limit <= 0 )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_NOLIMIT );
				return false;
			}
			// if the limit is 0 bail
			
			self::_add_exception( $nick, $ip_address, $limit, $description );
			// call add ip exception
		}
		// mode is add
		else if ( $mode == 'del' )
		{
			$ip_address = $ircdata[1];
			// get our vars
			
			if ( !services::is_root( $nick ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// you have to be root to add/del an exception
			
			if ( trim( $ip_address ) == '' || !filter_var( $ip_address, FILTER_VALIDATE_IP ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'SESSION' ) );
				return false;
			}
			// wrong syntax
			
			self::_del_exception( $nick, $ip_address );
			// call del ip exception
		}
		// mode is del
		else if ( $mode == 'list' )
		{
			self::_list_exception( $nick );
			// call list exception
		}
		// mode is list
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'SESSION' ) );
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
			$clients = core::$ips[$connect_data['ip_address']];
		
			if ( $clients > 1 )
				core::alog( 'WARNING: multiple clients detected ('.$connect_data['ident'].'@'.$connect_data['host'].') ('.$clients.' clients) on ('.$connect_data['ip_address'].')' );
			// log multiple sessions
			
			if ( database::num_rows( core::$session_rows ) == 0 )
				$match = self::$session_limit;
			// determine match if there is no session exceptions
			
			while ( $sessions = database::fetch( core::$session_rows ) )
			{
				if ( $sessions->ip_address != $connect_data['ip_address'] )
					continue;
				// it doesnt match the record, skip to next one.
					
				if ( $sessions->limit > self::$session_limit )
					$match = $sessions->limit;
				else
					$match = self::$session_limit;
				// the session limit in the database is higher than the config file limit..
				// determine which limit we actually use.
				
				break;
			}
			// check the sessions database
			
			if ( $clients > $match )
			{
				ircd::kill( core::$config->operserv->nick, $nick, 'Session limit for '.$connect_data['ip_address'].' reached!' );
				core::alog( 'WARNING: client limit reached ('.$connect_data['ident'].'@'.$connect_data['host'].') ('.$clients.' clients) on ('.$connect_data['ip_address'].')' );
			}
			// their limit has been bypassed >:) KILL THEM
		}
	}
	
	/*
	* _add_exception (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ip_address - The ip address to add except
	* $limit - New limit
	* $description - Description
	*/
	static public function _add_exception( $nick, $ip_address, $limit, $description )
	{
		$check_record_q = database::select( 'sessions', array( 'ip_address' ), array( 'ip_address', '=', $ip_address, 'AND', 'akill', '=', 0 ) );
		
		if ( database::num_rows( $check_record_q ) == 0 )
		{
			database::insert( 'sessions', array( 'nick' => $nick, 'ip_address' => $ip_address, 'description' => $description, 'limit' => $limit, 'time' => core::$network_time, 'akill' => 0 ) );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_ADD, array( 'ip_addr' => $ip_address, 'limit' => $limit ) );
			core::alog( core::$config->operserv->nick.': '.$nick.' added an exception limit for '.$ip_address.' at '.$limit );
			// as simple, as.
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_EXISTS, array( 'ip_addr' => $ip_address ) );
			// already got an exception 
		}
	}
	
	/*
	* _del_exception (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ip_address - The ip address to del except
	*/
	static public function _del_exception( $nick, $ip_address )
	{
		$check_record_q = database::select( 'sessions', array( 'ip_address' ), array( 'ip_address', '=', $ip_address, 'AND', 'akill', '=', 0 ) );
		
		if ( database::num_rows( $check_record_q ) > 0 )
		{
			database::delete( 'sessions', array( 'ip_address', '=', $ip_address ) );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_DEL, array( 'ip_addr' => $ip_address ) );
			core::alog( core::$config->operserv->nick.': '.$nick.' removed the exception limit for '.$ip_address );
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_NOEXISTS, array( 'ip_addr' => $ip_address ) );
			// already got an exception
		}
	}
	
	/*
	* _list_exception (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	*/
	static public function _list_exception( $nick )
	{
		$check_record_q = database::select( 'sessions', array( 'nick', 'ip_address', 'description', 'limit' ), array( 'akill', '=', 0 ) );
		
		if ( database::num_rows( $check_record_q ) > 0 )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_LIST_T );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_LIST_D );
			// t-o-l
			
			$limit = self::$session_limit;
			$y_x = strlen( $limit );
			for ( $i_x = $y_x; $i_x <= 5; $i_x++ )
				$limit .= ' ';
			// tidy tidy, limit
			
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_LIST1, array( 'limit' => $limit ) );
			// add * limit
			
			$x = 1;
			while ( $session = database::fetch( $check_record_q ) )
			{
				$ip_address = $session->ip_address;
				$x++;
				
				$num = $x;
				$y_i = strlen( $num );
				for ( $i_i = $y_i; $i_i <= 5; $i_i++ )
					$num .= ' ';
				// tidy tidy, num
				
				if ( !isset( $session->ip_address[15] ) )
				{
					$y = strlen( $session->ip_address );
					for ( $i = $y; $i <= 14; $i++ )
						$ip_address .= ' ';
				}
				// this is just a bit of fancy fancy, so everything displays neat
				
				$limit = $session->limit;
				$y_x = strlen( $limit );
				for ( $i_x = $y_x; $i_x <= 5; $i_x++ )
					$limit .= ' ';
				// tidy tidy, limit
				
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_LIST, array( 'num' => $num, 'ip_addr' => $ip_address, 'limit' => $limit, 'nick' => $session->nick, 'desc' => $session->description ) );
			}
			// loop through the records
			
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_LIST_D );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_LIST_B, array( 'num' => $x ) );
		}
		// display list
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_LIST_T );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_LIST_D );
			// t-o-l
			
			$limit = self::$session_limit;
			$y_x = strlen( $limit );
			for ( $i_x = $y_x; $i_x <= 5; $i_x++ )
				$limit .= ' ';
			// tidy tidy, limit
			
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_LIST1, array( 'limit' => $limit ) );
			// add * limit
			
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_LIST_D );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_EXCP_LIST_B, array( 'num' => 1 ) );
		}
		// empty list. display the config record
	}
}

// EOF;