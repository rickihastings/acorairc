<?php

/*
* Acora IRC Services
* modules/chankill.os.php: OperServ chankill module
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

class os_chanclear extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'CHAN_INVALID'		=> 2,
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
	static public function modload()
	{
		modules::init_module( 'os_chanclear', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		operserv::add_help( 'os_chanclear', 'help', operserv::$help->OS_HELP_CHANCLEAR_1, true, 'global_op' );
		operserv::add_help( 'os_chanclear', 'help chanclear', operserv::$help->OS_HELP_CHANCLEAR_ALL, false, 'global_op' );
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
		if ( !services::oper_privs( $nick, 'global_op' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// dont have access
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );		
		$return_data = self::_chan_clear( $input, $nick, strtoupper( $ircdata[0] ), core::get_chan( $ircdata, 1 ), core::get_data_after( $ircdata, 2 ) );
		// send to a subfunction
		
		services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _chan_clear (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $mode - Mode, such as BAN, KILL, KICK
	* $chan - The channel
	* $reason - The reason to use
	*/
	static public function _chan_clear( $input, $nick, $mode, $chan, $reason = '' )
	{
		$return_data = module::$return_data;
		if ( trim( $chan ) == '' || trim( $reason ) == '' || !in_array( $mode, array( 'KICK', 'KILL', 'BAN' ) ) || $chan[0] != '#' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'CHANCLEAR' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
		
		if ( !isset( core::$chans[$chan] ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_CHAN_INVALID, array( 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->CHAN_INVALID;
			return $return_data;
		}
		// check if the channel is in use..
		
		foreach ( core::$chans[$chan]['users'] as $user => $umode )
		{
			if ( core::$nicks[$user]['ircop'] )
			{
				core::alog( core::$config->operserv->nick.': CHANCLEAR: Ignoring IRC Operator ('.$user.')' );
				continue;
			}
			// ignore irc operator, infact, logchan it too
			
			if ( $mode == 'KICK' )
			{
				ircd::kick( core::$config->operserv->nick, $user, $chan, 'CHANKILL by '.$nick.' ('.$reason.')' );
				mode::set( core::$config->operserv->nick, $chan, '+b *@'.core::$nicks[$user]['host'] );
				// kick and +b them
			}
			elseif ( $mode == 'KILL' )
				ircd::kill( core::$config->operserv->nick, $user, 'CHANKILL by '.$nick.' ('.$reason.')' );
			elseif ( $mode == 'BAN' )
				ircd::global_ban( core::$config->operserv->nick, '*@'.core::$nicks[$user]['oldhost'], 604800, 'CHANKILL by '.$nick.' ('.$reason.')' );
			// remove all other users.
		}
		// loop through the people in the channel
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
}

// EOF;