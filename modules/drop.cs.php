<?php

/*
* Acora IRC Services
* modules/drop.cs.php: ChanServ drop module
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class cs_drop implements module
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
		modules::init_module( 'cs_drop', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_drop', 'help', &chanserv::$help->CS_HELP_DROP_1 );
		chanserv::add_help( 'cs_drop', 'help drop', &chanserv::$help->CS_HELP_DROP_ALL );
		// add the help
		
		chanserv::add_command( 'drop', 'cs_drop', 'drop_command' );
		// add the drop command
	}
	
	/*
	* drop_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function drop_command( $nick, $ircdata = array() )
	{
		$chan = core::get_chan( &$ircdata, 0 );
		// get the channel.
		
		if ( self::_drop_check( $nick, $chan ) === false )
			return false;
		// do nessicary checks
		
		if ( $channel = services::chan_exists( $chan, array( 'channel', 'suspended' ) ) )
		{
			if ( $channel->suspended == 1 )
			{
				services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_SUSPEND_1, array( 'chan' => $chan ) );
				return false;
			}
		}
		// is the channel suspended?
		
		database::delete( 'chans', "`channel` = '".database::quote( $chan )."'" );
		database::delete( 'chans_levels', "`channel` = '".database::quote( $chan )."'" );
		// delete all associated records
		
		services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_CHAN_DROPPED, array( 'chan' => $chan ) );
		// let the user know
		
		if ( isset( core::$chans[$chan] ) )
		{
			ircd::mode( core::$config->chanserv->nick, $chan, '-'.ircd::$reg_modes['chan'] );
			ircd::part_chan( core::$config->chanserv->nick, $chan );
			// now lets leave the channel if we're in it
		}
		// is the channel in existance? if so unregister mode
		// remember we DON'T unset the channel record, because the channel
		// is still there, just isnt registered, completely different things
		
		core::alog( core::$config->chanserv->nick.': '.$chan.' has been dropped by '.core::get_full_hostname( $nick ) );
		// logchan it
		
		core::alog( 'drop_command(): '.$chan.' has been dropped by '.core::get_full_hostname( $nick ), 'BASIC' );
		// log what we need to log.
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
	
	/*
	* _drop_check (private)
	* 
	* @params
	* $nick - The nick to check access for
	* $chan - The channel to check.
	*/
	static public function _drop_check( $nick, $chan )
	{
		if ( $chan == '' || $chan[0] != '#' )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'DROP' ) );
			return false;
			// wrong syntax
		}
		// make sure they've entered a channel
		
		if ( services::chan_exists( $chan, array( 'channel' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			return false;
		}
		// make sure the channel exists.
		
		if ( chanserv::_is_founder( $nick, $chan ) )
		{
			return true;
		}
		elseif ( core::$nicks[$nick]['ircop'] && services::user_exists( $nick, true, array( 'display', 'identified' ) ) )
		{
			ircd::globops( core::$config->chanserv->nick, $nick.' used DROP on '.$chan );
			return true;
		}
		
		services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
		return false;
		// do they have access?
	}
}

// EOF;