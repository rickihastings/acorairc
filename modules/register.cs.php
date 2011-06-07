<?php

/*
* Acora IRC Services
* modules/register.cs.php: ChanServ register module
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

class cs_register implements module
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
		modules::init_module( 'cs_register', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_register', 'help', chanserv::$help->CS_HELP_REGISTER_1 );
		chanserv::add_help( 'cs_register', 'help register', chanserv::$help->CS_HELP_REGISTER_ALL );
		// add the help
		
		chanserv::add_command( 'register', 'cs_register', 'register_command' );
		// add the command
	}
	
	/*
	* unsuspend_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function register_command( $nick, $ircdata = array() )
	{
		$chan = core::get_chan( $ircdata, 0 );
		$desc = core::get_data_after( $ircdata, 1 );
		// get the channel.
		
		if ( $user = services::user_exists( $nick, true, array( 'display', 'id' ) ) )
		{
			if ( trim( $desc ) == '' || $chan == '' || $chan[0] != '#' || stristr( $channel, ' ' ) )
			{
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'INFO' ) );
				// wrong syntax
				return false;
			}
			
			if ( services::chan_exists( $chan, array( 'channel' ) ) !== false )
			{
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_REGISTERED_CHAN, array( 'chan' => $chan ) );
				return false;
			}
			// check if its registered?
			
			
			if ( !strstr( core::$chans[$chan]['users'][$nick], 'o' ) )
			{
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_NEED_CHAN_OP, array( 'chan' => $chan ) );	
				return false;
			}
			// we need to check if the user trying to register it has +o
			// if not we tell them to GET IT!
			
			$chan_info = array(
				'channel' 		=> 	$chan,
				'timestamp' 	=> 	core::$network_time,
				'last_timestamp'=> 	core::$network_time,
				'topic' 		=> 	core::$chans[$chan]['topic'],
				'topic_setter' 	=> 	core::$chans[$chan]['topic_setter'],
			);
			
			$rflags = core::$config->chanserv->default_flags;
			$rflags = str_replace( 'd', '', $rflags );
			$rflags = str_replace( 'u', '', $rflags );
			$rflags = str_replace( 'e', '', $rflags );
			$rflags = str_replace( 'w', '', $rflags );
			$rflags = str_replace( 'm', '', $rflags );
			$rflags = str_replace( 't', '', $rflags );
			// ignore parameter flags
			
			database::insert( 'chans', $chan_info );
			database::insert( 'chans_levels', array( 'channel' => $chan, 'target' => $user->display, 'flags' => 'Ftfrsqao' ) );
			database::insert( 'chans_flags', array( 'channel' => $chan, 'flags' => $rflags.'d', 'desc' => $desc ) );
			// create the channel! WOOOH
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_CHAN_REGISTERED, array( 'chan' => $chan ) );
			core::alog( core::$config->chanserv->nick.': '.$chan.' registered by '.core::get_full_hostname( $nick ) );
			// logchan
			
			core::alog( 'register_command(): '.$chan.' registered by '.core::get_full_hostname( $nick ), 'BASIC' );
			// log what we need to log.
			
			if ( $channel = services::chan_exists( $chan, array( 'channel', 'topic', 'suspended' ) ) )
			{
				chanserv::_join_channel( $channel );
				// join the channel
			}
			// does the channel exist?
		}
		else
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_UNREGISTERED );
			return false;
			// ph00s aint even registered..
		}
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
		// the main core registered stuff is in chanserv core
		// incase people want to unload register for web registration
		// or something, and still allow chanserv to be properly functional
		//
		// so instead we just return true.
	}
}

// EOF;