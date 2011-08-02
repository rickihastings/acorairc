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

class os_session extends module
{
	
	const MOD_VERSION = '0.1.3';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'SESSION_EXISTS'	=> 2,
		'SESSION_NO_EXIST'	=> 3,
		'SESSION_LIST_EMPTY'=> 4,
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
		modules::init_module( 'os_session', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		operserv::add_help( 'os_session', 'help', operserv::$help->OS_HELP_SESSION_1, true );
		operserv::add_help( 'os_session', 'help session', operserv::$help->OS_HELP_SESSION_ALL );
		// add the help
		
		operserv::add_command( 'session', 'os_session', 'session_command' );
		// add the command
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		
		if ( $mode == 'add' )
		{
			if ( !services::oper_privs( $nick, 'global_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// you have to be globop to add/del an exception
			
			$return_data = self::_add_exception( $input, $nick, $ircdata[1], $ircdata[2], core::get_data_after( $ircdata, 3 ) );
			// call add ip exception
			
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
			// you have to be globop to add/del an exception
			
			$return_data = self::_del_exception( $input, $nick, $ircdata[1] );
			// call del ip exception
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		// mode is del
		elseif ( $mode == 'list' )
		{
			$return_data = self::_list_exception( $input );
			// call list exception
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
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
	 * on_burst_connect (event hook)
	 */
	static public function on_burst_connect( $connect_data )
	{
		if ( core::$config->operserv->limit_on_connect )
			self::on_connect( $connect_data );
	}
	
	/*
	* on_connect (event hook)
	*/
	static public function on_connect( $connect_data )
	{
		if ( $connect_data['ip_address'] == '' )
			return false;
		// this shouldn't EVER occur in a live net, sometimes it did during the stress testing phases though
		// reason why it occured, the stress tester created clients named "spam" + random 5 digit number
		// occasionally and rarely the same number was generated, introducing 2 clients with the same nick
		// the ircd doesn't normally allow this, however our stress tester isn't an ircd, so it introduces
		// it anyway, causing the real ircd to invalidate the EUID command, forcing a nick change to a UID
		
		$session_limit = ( !isset( core::$config->operserv->connection_limit ) || core::$config->operserv->connection_limit <= 0 ) ? 5 : core::$config->operserv->connection_limit;
	
		$nick = $connect_data['nick'];
		$clients = core::$ips[$connect_data['ip_address']];
		
		if ( !$startup && $clients > 1 )
			core::alog( core::$config->operserv->nick.': Multiple clients detected ('.$connect_data['ident'].'@'.$connect_data['host'].') ('.$clients.' clients) on ('.$connect_data['ip_address'].')' );
		// log multiple sessions
		
		$match = $session_limit;
		// determine match if there is no session exceptions
		
		foreach ( operserv::$session_rows as $i => $sessions )
		{
			if ( $sessions->ip_address != $connect_data['ip_address'] )
				continue;
			// it doesnt match the record, skip to next one.
				
			if ( $sessions->limit > $session_limit )
				$match = $sessions->limit;
			else
				$match = $session_limit;
			// the session limit in the database is higher than the config file limit..
			// determine which limit we actually use.
			
			break;
		}
		// check the sessions database
		
		if ( $clients > $match )
		{
			ircd::kill( core::$config->operserv->nick, $nick, 'Session limit for '.$connect_data['ip_address'].' reached!' );
			core::alog( core::$config->operserv->nick.': Client limit reached ('.$connect_data['nick'].'!'.$connect_data['ident'].'@'.$connect_data['host'].') ('.$clients.' clients) on ('.$connect_data['ip_address'].')' );
		}
		// their limit has been bypassed >:) KILL THEM
	}
	
	/*
	* _add_exception (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $ip_address - The ip address to add except
	* $limit - New limit
	* $description - Description
	*/
	static public function _add_exception( $input, $nick, $ip_address, $limit, $description )
	{
		$return_data = module::$return_data;
		if ( trim( $ip_address ) == '' || trim( $description ) == '' || !is_numeric( $limit ) || !filter_var( $ip_address, FILTER_VALIDATE_IP ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'SESSION' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
		
		if ( $limit <= 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_NOLIMIT );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_LIMIT;
			return $return_data;
		}
		// if the limit is 0 bail
	
		$check_record_q = database::select( 'sessions', array( 'ip_address' ), array( 'ip_address', '=', $ip_address, 'AND', 'akill', '=', 0 ) );
		if ( database::num_rows( $check_record_q ) > 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_EXISTS, array( 'ip_addr' => $ip_address ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->SESSION_EXISTS;
			return $return_data;
		}
		// a session exists
		
		database::insert( 'sessions', array( 'nick' => $nick, 'ip_address' => $ip_address, 'description' => $description, 'limit' => $limit, 'time' => core::$network_time, 'akill' => 0 ) );
		// update session
	
		$query = database::select( 'sessions', array( 'nick', 'ip_address', 'hostmask', 'description', 'limit', 'time', 'expire', 'akill' ) );
		while ( $session = database::fetch( $query ) )
			operserv::$session_rows[] = $session;
		// re read the session array.
		
		core::alog( core::$config->operserv->nick.': ('.$input['hostname'].') ('.$input['account'].') added an exception limit for ('.$ip_address.') at ('.$limit.')' );
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_ADD, array( 'ip_addr' => $ip_address, 'limit' => $limit ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back & log
	}
	
	/*
	* _del_exception (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $ip_address - The ip address to del except
	*/
	static public function _del_exception( $input, $nick, $ip_address )
	{
		$return_data = module::$return_data;
		if ( trim( $ip_address ) == '' || !filter_var( $ip_address, FILTER_VALIDATE_IP ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'SESSION' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
	
		$check_record_q = database::select( 'sessions', array( 'ip_address' ), array( 'ip_address', '=', $ip_address, 'AND', 'akill', '=', 0 ) );
		if ( database::num_rows( $check_record_q ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_NOEXISTS, array( 'ip_addr' => $ip_address ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->SESSION_NO_EXIST;
			return $return_data;
		}
		// no exception can be found, let's go!
		
		database::delete( 'sessions', array( 'ip_address', '=', $ip_address ) );
		// delete the session
		
		$query = database::select( 'sessions', array( 'nick', 'ip_address', 'hostmask', 'description', 'limit', 'time', 'expire', 'akill' ) );
		while ( $session = database::fetch( $query ) )
			operserv::$session_rows[] = $session;
		// re read the session array.
		
		core::alog( core::$config->operserv->nick.': ('.$input['hostname'].') ('.$input['account'].') removed the exception limit for ('.$ip_address.')' );
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_DEL, array( 'ip_addr' => $ip_address ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back & log it
	}
	
	/*
	* _list_exception (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	*/
	static public function _list_exception( $input )
	{
		$return_data = module::$return_data;
		$check_record_q = database::select( 'sessions', array( 'nick', 'ip_address', 'description', 'limit' ), array( 'akill', '=', 0 ) );
		$session_limit = ( !isset( core::$config->operserv->connection_limit ) || core::$config->operserv->connection_limit <= 0 ) ? 5 : core::$config->operserv->connection_limit;
		
		if ( database::num_rows( $check_record_q ) > 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_LIST_T );
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_LIST_D );
			// t-o-l
			
			$limit = $session_limit;
			$y_x = strlen( $limit );
			for ( $i_x = $y_x; $i_x <= 5; $i_x++ )
				$limit .= ' ';
			// tidy tidy, limit
			
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_LIST1, array( 'limit' => $limit ) );
			$return_data[CMD_DATA][] = array( 'ip_addr' => '*', 'limit' => $limit );
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
				
				$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_LIST, array( 'num' => $num, 'ip_addr' => $ip_address, 'limit' => $limit, 'nick' => $session->nick, 'desc' => $session->description ) );
				$return_data[CMD_DATA][] = array( 'ip_addr' => $ip_address, 'limit' => $limit, 'nick' => $session->nick, 'desc' => $session->description );
			}
			// loop through the records
			
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_LIST_D );
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_LIST_B, array( 'num' => $x ) );
		}
		// display list
		else
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_LIST_T );
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_LIST_D );
			// t-o-l
			
			$limit = $session_limit;
			$y_x = strlen( $limit );
			for ( $i_x = $y_x; $i_x <= 5; $i_x++ )
				$limit .= ' ';
			// tidy tidy, limit
			
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_LIST1, array( 'limit' => $limit ) );
			// add * limit
			
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_LIST_D );
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_EXCP_LIST_B, array( 'num' => 1 ) );
		}
		// empty list. display the config record
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back & log it
	}
}

// EOF;
