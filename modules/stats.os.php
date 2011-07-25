<?php

/*
* Acora IRC Services
* modules/stats.os.php: OperServ stats module
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

class os_stats extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	/*
	* modload (private)
	* 
	* @params
	* void
	*/
	static public function modload()
	{
		modules::init_module( 'os_stats', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_stats', 'help', operserv::$help->OS_HELP_STATS_1, true );
		operserv::add_help( 'os_stats', 'help stats', operserv::$help->OS_HELP_STATS_ALL );
		// add the help
		
		operserv::add_command( 'stats', 'os_stats', 'stats_command' );
		// add the stats command
	}
	
	/*
	* stats_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function stats_command( $nick, $ircdata = array() )
	{
		$type = strtolower( $ircdata[0] );
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		// what type is it, currently valid types are
		// UPTIME, NETWORK, SERVERS, OPERS
		
		if ( $type == 'uptime' )
		{
			$return_data = self::_stats_uptime( $input );
			// call list exception
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		elseif ( $type == 'network' )
		{
			$return_data = self::_stats_network( $input );
			// call list exception
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		elseif ( $type == 'opers' )
		{
			$return_data = self::_stats_opers( $input );
			// call list exception
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'STATS' ) );
			return false;
			// wrong syntax
		}
		// if/else for our type, if one isnt given we bail out.
	}
	
	/*
	* _stats_uptime (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	*/
	static public function _stats_uptime( $input )
	{
		$return_data = module::$return_data;
	
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_U_1, array( 'time' => core::format_time( core::$uptime ) ) );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_U_2, array( 'memory' => core::get_size( memory_get_usage() ), 'real' => memory_get_usage() ) );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_U_3, array( 'memory' => core::get_size( core::$incoming ), 'real' => core::$incoming ) );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_U_4, array( 'memory' => core::get_size( core::$outgoing ), 'real' => core::$outgoing ) );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_U_5, array( 'lines' => core::$lines_processed ) );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_U_6, array( 'lines' => core::$lines_sent ) );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_U_7, array( 'time' => core::$burst_time.'s' ) );
		// compile responses
		
		$return_data[CMD_DATA] = array( 'time' => core::$uptime, 'memory_usage' => memory_get_usage(), 'incoming_data' => core::$incoming, 'outgoing_data' => core::$outgoing, 'lines_processed' => core::$lines_processed, 'lines_sent' => core::$lines_sent, 'burst_length' => core::$burst_time ); 
		// compile data, for RPC calls
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* _stats_network (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	*/
	static public function _stats_network( $input )
	{
		$return_data = module::$return_data;
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_N_1, array( 'network' => core::$config->server->network_name ) );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_N_2, array( 'version' => core::$version ) );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_N_3, array( 'users' => core::$max_users ) );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_N_4, array( 'users' => count( core::$nicks ) ) );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_N_5, array( 'chans' => count( core::$chans ) ) );
		// compile network info response.
		
		$return_data[CMD_DATA] = array( 'network' => core::$config->server->network_name, 'version' => core::$version, 'max_users' => core::$max_users, 'users' => count( core::$nicks ), 'chans' => count( core::$chans ) ); 
		// compile data, for RPC calls
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* _stats_opers (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	*/
	static public function _stats_opers( $input )
	{
		$return_data = module::$return_data;
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_O_T );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_O_D );
		
		$x = 0;
		while ( list( $user, $info ) = each( core::$nicks ) )
		{
			if ( !$info['ircop'] || $info['server'] == core::$config->server->name ) continue;
			// skip if they aint an ircop
			
			$x++;
			$false_host = core::get_full_hostname( $user );
			$privs = services::show_privs( $user );
			$privs = ( !$privs ) ? '' : ' ['.$privs.']';
			// some vars.
			
			$num = $x;
			$y_i = strlen( $num );
				for ( $i_i = $y_i; $i_i <= 5; $i_i++ )
					$num .= ' ';
			
			if ( !isset( $false_host[50] ) )
			{	
				$y = strlen( $false_host );
				for ( $i = $y; $i <= 49; $i++ )
					$false_host .= ' ';
			}
			// this is just a bit of fancy fancy, so everything displays neat
			
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_O_L, array( 'num' => $num, 'host' => $false_host, 'time' => date( "F j, Y, g:i a", $info['timestamp'] ), 'privs' => $privs ) );
			
			$return_data[CMD_DATA][] = array( 'host' => core::get_full_hostname( $user ), 'time' => $info['timestamp'], 'privs' => $privs ); 
		// compile data, for RPC calls
		}
		reset( core::$nicks );
		// opers info.
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_O_D );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_STATS_O_B, array( 'num' => $x ) );
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
}

// EOF;