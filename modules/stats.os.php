<?php

/*
* Acora IRC Services
* modules/stats.os.php: OperServ stats module
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
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
		// we don't even need to listen for any
		// parameters, because its just a straight command
		
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_1, array( 'network' => core::$config->server->network_name ) );
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_2, array( 'version' => core::$version ) );
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_3, array( 'users' => core::$max_users ) );
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_4, array( 'users' => count( core::$nicks ) ) );
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_5, array( 'chans' => count( core::$chans ) ) );
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_6, array( 'time' => core::format_time( core::$uptime ) ) );
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_7, array( 'memory' => core::get_size( memory_get_usage() ), 'real' => memory_get_usage() ) );
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_8, array( 'memory' => core::get_size( core::$incoming ), 'real' => core::$incoming ) );
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_STATS_9, array( 'memory' => core::get_size( core::$outgoing ), 'real' => core::$outgoing ) );
		// send out our statistics	
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