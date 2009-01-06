<?php

/*
* Acora IRC Services
* modules/chankill.os.php: OperServ chankill module
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

class os_chanclear implements module
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
		modules::init_module( 'os_chanclear', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_chanclear', 'help', &operserv::$help->OS_HELP_CHANCLEAR_1 );
		operserv::add_help( 'os_chanclear', 'help chanclear', &operserv::$help->OS_HELP_CHANCLEAR_ALL );
		// add the help
		
		operserv::add_command( 'chanclear', 'os_chanclear', 'chanclear_command' );
		// add the command
	}
	
	/*
	* chanclear_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function chanclear_command( $nick, $ircdata = array() )
	{
		$chan = core::get_chan( &$ircdata, 1 );
		$reason = core::get_data_after( &$ircdata, 2 );
		$mode = strtoupper( $ircdata[0] );
		// get the data.
			
		if ( trim( $chan ) == '' || trim( $reason ) == '' || !in_array( $mode, array( 'KICK', 'KILL', 'GLINE' ) ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'CHANCLEAR' ) );
			return false;
			// wrong syntax
		}
		
		if ( $chan[0] != '#' )
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'CHANCLEAR' ) );
			return false;
			// wrong syntax
		}
		
		if ( isset( core::$chans[$chan] ) )
		{
			foreach ( core::$chans[$chan]['users'] as $user => $umode )
			{
				if ( core::$nicks[$user]['ircop'] )
				{
					core::alog( core::$config->operserv->nick.': Ignoring IRC Operator ('.$user.')' );
					// ignore irc operator, infact, logchan it too
				}
				else
				{
					if ( $mode == 'KICK' )
					{
						ircd::kick( core::$config->operserv->nick, $user, $chan, 'CHANKILL by '.$nick.' ('.$reason.')' );
						ircd::mode( core::$config->operserv->nick, $chan, '+b *@'.core::$nicks[$user]['host'] );
						// kick and +b them
					}
					elseif ( $mode == 'KILL' )
					{
						ircd::kill( core::$config->operserv->nick, $user, 'CHANKILL by '.$nick.' ('.$reason.')' );
					}
					elseif ( $mode == 'GLINE' )
					{
						ircd::gline( core::$config->operserv->nick, '*@'.core::$nicks[$user]['oldhost'], 604800, 'CHANKILL by '.$nick.' ('.$reason.')' );
					}
					// remove all other users.
				}
			}
			// loop through the people in the channel/
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_CHAN_INVALID, array( 'chan' => $chan ) );
		}
		// check if the channel is in use..
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