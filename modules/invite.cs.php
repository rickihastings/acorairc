<?php

/*
* Acora IRC Services
* modules/invite.cs.php: ChanServ invite module
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

class cs_invite extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'CHAN_UNREGISTERED'	=> 2,
		'CHAN_NOEXIST'		=> 3,
		'ALREADY_IN_CHAN'	=> 4,
		'ACCESS_DENIED'		=> 5,
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
		modules::init_module( 'cs_invite', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_invite', 'help commands', chanserv::$help->CS_HELP_INVITE_1 );
		chanserv::add_help( 'cs_invite', 'help invite', chanserv::$help->CS_HELP_INVITE_ALL );
		// add the help
		
		chanserv::add_command( 'invite', 'cs_invite', 'invite_command' );
		// add the invite command
	}
	
	/*
	* invite_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function invite_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$who = ( trim( $ircdata[1] ) == '' ) ? $nick : $ircdata[1];
		$return_data = self::_invite_user( $input, $nick, $ircdata[0], $who );
		// call _invite_user, wheey.
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _invite_user (command)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel
	* $who - Who to invite to the channel
	*/
	static public function _invite_user( $input, $nick, $chan, $who )
	{
		$return_data = module::$return_data;
		
		if ( $chan == '' || $chan[0] != '#' || !core::search_nick( $who ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'INVITE' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
		
		if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->CHAN_UNREGISTERED;
			return $return_data;
		}
		// make sure the channel exists
		
		if ( !isset( core::$chans[$chan] ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_CHAN_NOEXIST, array( 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->CHAN_NOEXIST;
			return $return_data;
		}
		// channel is registered, but does it exist?
		
		if ( isset( core::$chans[$chan]['users'][$who] ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INVITE_IN_CHAN, array( 'nick' => $who, 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->ALREADY_IN_CHAN;
			return $return_data;
		}
		// the user is already in the channel
		
		if ( chanserv::check_levels( $nick, $channel->channel, array( 'i', 'S', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
			
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// make sure they have access to invite people +i flag

		ircd::invite( core::$config->chanserv->nick, $who, $chan );
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_CHAN_INVITE, array( 'nick' => $who, 'chan' => $chan ) );
		// invite the user!
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
}

// EOF;