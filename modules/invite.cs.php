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

class cs_invite implements module
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
		modules::init_module( 'cs_invite', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
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
		$chan = core::get_chan( $ircdata, 0 );
		$who = ( trim( $ircdata[1] ) == '' ) ? $nick : $ircdata[1];
		// get the channel.
		
		$unicks = array_change_key_case( core::$nicks, CASE_LOWER );
		if ( $chan == '' || $chan[0] != '#' || !isset( $unicks[strtolower( $who )] ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'INVITE' ) );
			return false;
			// wrong syntax
		}
		// make sure they've entered a channel and user and they're both invalid
		
		if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			return false;
		}
		// make sure the channel exists
		
		if ( !isset( core::$chans[$chan] ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_CHAN_NOEXIST, array( 'chan' => $chan ) );
			return false;
		}
		// channel is registered, but does it exist?
		
		if ( chanserv::check_levels( $nick, $channel->channel, array( 'i', 'S', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// make sure they have access to invite people +i flag

		ircd::invite( core::$config->chanserv->nick, $who, $chan );
		services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_CHAN_INVITE, array( 'nick' => $who, 'chan' => $chan ) );
		// invite the user!
	}
	
	/*
	* main (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	public function main( $ircdata, $startup = false )
	{
		return true;
		// we don't need to listen for anything in this module
		// so we just return true immediatly.
	}
}

// EOF;