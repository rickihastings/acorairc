<?php

/*
* Acora IRC Services
* modules/stats.os.php: OperServ stats module
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

class os_stats implements module
{
	
	const MOD_VERSION = '0.0.3';
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
		modules::init_module( 'os_stats', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_stats', 'help', &operserv::$help->OS_HELP_STATS_1 );
		operserv::add_help( 'os_stats', 'help stats', &operserv::$help->OS_HELP_STATS_ALL );
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
		$type = $ircdata[0];
		// what type is it, currently valid types are
		// UPTIME, NETWORK, SERVERS, OPERS
		
		if ( strtolower( $type ) == 'uptime' )
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_U_1, array( 'time' => core::format_time( core::$uptime ) ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_U_2, array( 'memory' => core::get_size( memory_get_usage() ), 'real' => memory_get_usage() ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_U_3, array( 'memory' => core::get_size( core::$incoming ), 'real' => core::$incoming ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_U_4, array( 'memory' => core::get_size( core::$outgoing ), 'real' => core::$outgoing ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_U_5, array( 'lines' => core::$lines_processed ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_U_6, array( 'lines' => core::$lines_sent ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_U_7, array( 'time' => core::$burst_time.'s' ) );
			// uptime info, etc.
		}
		elseif ( strtolower( $type ) == 'network' )
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_N_1, array( 'network' => core::$config->server->network_name ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_N_2, array( 'version' => core::$version ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_N_3, array( 'users' => core::$max_users ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_N_4, array( 'users' => count( core::$nicks ) ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_N_5, array( 'chans' => count( core::$chans ) ) );
			// network info.
		}
		elseif ( strtolower( $type ) == 'opers' )
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_O_1 );
			
			foreach ( core::$nicks as $user => $info )
			{
				if ( !$info['ircop'] || $info['server'] == core::$config->server->name ) continue;
				// skip if they aint an ircop
				
				$false_host = core::get_full_hostname( $user );
				
				if ( !isset( $false_host[45] ) )
				{
					$y = strlen( $false_host );
					for ( $i = $y; $i <= 44; $i++ )
						$false_host .= ' ';
				}
				// this is just a bit of fancy fancy, so everything displays neat
				
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_O_2, array( 'host' => $false_host, 'time' => date( "F j, Y, g:i a", $info['timestamp'] ) ) );
			}
			// opers info.
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'STATS' ) );
			return false;
			// wrong syntax
		}
		// if/else for our type, if one isnt given we bail out.
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
		// we don't need to listen for anything in this module
		// so we just return true immediatly.
	}
}

// EOF;