<?php

/*
* Acora IRC Services
* modules/suspend.cs.php: ChanServ suspend module
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

class cs_suspend implements module
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
		modules::init_module( 'cs_suspend', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_suspend', 'help', chanserv::$help->CS_HELP_SUSPEND_1, true );
		chanserv::add_help( 'cs_suspend', 'help', chanserv::$help->CS_HELP_UNSUSPEND_1, true );
		chanserv::add_help( 'cs_suspend', 'help suspend', chanserv::$help->CS_HELP_SUSPEND_ALL, true );
		chanserv::add_help( 'cs_suspend', 'help unsuspend', chanserv::$help->CS_HELP_UNSUSPEND_ALL, true );
		// add the help
		
		chanserv::add_command( 'suspend', 'cs_suspend', 'suspend_command' );
		chanserv::add_command( 'unsuspend', 'cs_suspend', 'unsuspend_command' );
		// add the commands
	}
	
	/*
	* suspend_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function suspend_command( $nick, $ircdata = array() )
	{
		$chan = core::get_chan( $ircdata, 0 );
		$reason = core::get_data_after( $ircdata, 1 );
		$chan_info = array();
		// get the channel.
		
		if ( !core::$nicks[$nick]['ircop'] || services::user_exists( $nick, true, array( 'display', 'identified' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// they've gotta be identified and opered..
		
		if ( $chan == '' || $chan[0] != '#' )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'SUSPEND' ) );
			return false;
			// wrong syntax
		}
		// make sure they've entered a channel
		
		if ( trim( $reason ) == '' ) $reason = 'No reason';
		// is there a reason? if not we set it to 'No Reason'
		
		if ( $channel = services::chan_exists( $chan, array( 'channel', 'suspended' ) ) )
		{
			if ( $channel->suspended == 1 )
			{
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_SUSPEND_2, array( 'chan' => $chan ) );
				return false;
				// channel is already suspended lol
			}
			else
			{
				database::update( 'chans', array( 'suspended' => 1, 'suspend_reason' => $reason ), array( 'channel', '=', $channel->channel ) );
				// channel isn't suspended, but it IS registered
			}
		}
		else
		{
			$chan_info = array(
				'channel' 		=> 	$chan,
				'timestamp' 	=> 	core::$network_time,
				'last_timestamp'=> 	core::$network_time,
				'suspended' 	=> 	1,
				'suspend_reason'=> 	$reason,
			);
			
			database::insert( 'chans', $chan_info );
			database::insert( 'chans_flags', array( 'channel' => $chan, 'flags' => 'd', 'desc' => $reason ) );
			// if the channel isn't registered, we register it, with a founder value of 0
			// so we can check when it's unsuspended THAT if the founder value is 0, we'll
			// just drop it as well, this way nobody actually gets the founder status.
		}
		
		services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_SUSPEND_3, array( 'chan' => $chan, 'reason' => $reason ) );
		core::alog( core::$config->chanserv->nick.': '.$nick.' SUSPENDED '.$chan.' with the reason: '.$reason );
		ircd::globops( core::$config->chanserv->nick, $nick.' SUSPENDED '.$chan );
		
		if ( !empty( core::$chans[$chan]['users'] ) )
		{
			foreach ( core::$chans[$chan]['users'] as $user => $boolean )
			{
				if ( !core::$nicks[$nick]['ircop'] )
					ircd::kick( core::$config->chanserv->nick, $user, $chan, $reason );
			}
		}
		// any users in the channel? KICK EM!! RAWR
	}
	
	/*
	* unsuspend_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function unsuspend_command( $nick, $ircdata = array() )
	{
		$chan = core::get_chan( $ircdata, 0 );
		// get the channel.
		
		if ( !core::$nicks[$nick]['ircop'] || services::user_exists( $nick, true, array( 'display', 'identified' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// they've gotta be identified.
		
		if ( $chan == '' || $chan[0] != '#' )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'UNSUSPEND' ) );
			return false;
			// wrong syntax
		}
		// make sure they've entered a channel
		
		if ( $channel = services::chan_exists( $chan, array( 'channel', 'suspended' ) ) )
		{
			if ( $channel->suspended == 0 )
			{
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_SUSPEND_4, array( 'chan' => $chan ) );
				return false;	
			}
			// channel isn't even suspended
			
			$check_row = database::select( 'chans_levels', array( 'channel' ), array( 'channel', '=', $chan ) );
			
			if ( database::num_rows( $check_row ) == 0 )
			{
				database::delete( 'chans', array( 'channel', '=', $chan ) );
				database::delete( 'chans_flags', array( 'channel', '=', $chan ) );
			}
			// the channel has no access records, drop it.
		}
		else
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_SUSPEND_4, array( 'chan' => $chan ) );
			return false;
		}
		
		services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_SUSPEND_5, array( 'chan' => $chan ) );
		core::alog( core::$config->chanserv->nick.': '.$nick.' UNSUSPENDED '.$chan );
		ircd::globops( core::$config->chanserv->nick, $nick.' UNSUSPENDED '.$chan );
		// oh well, was fun while it lasted eh?
		// unsuspend it :P
	}
	
	/*
	* main (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	public function main( $ircdata, $startup = false )
	{
		$populated_chan = ircd::on_join( $ircdata );
		if ( $populated_chan !== false )
		{
			$nick = core::get_nick( $ircdata, 0 );
			$chans = explode( ',', $populated_chan );
			// find the nick & chan
			
			foreach ( $chans as $chan )
			{
				if ( $channel = services::chan_exists( $chan, array( 'channel', 'suspended', 'suspend_reason' ) ) )
				{
					if ( $channel->suspended == 1 )
					{
						if ( !core::$nicks[$nick]['ircop'] )
							ircd::kick( core::$config->chanserv->nick, $nick, $channel->channel, $channel->suspend_reason );
							// boot
					}
					// it's also suspended
				}
				// channel is registered
			}
		}
		// on_join trigger for forbidden channels
		
		$populated_chan = ircd::on_chan_create( $ircdata );
		if ( $populated_chan !== false )
		{
			$chans = explode( ',', $populated_chan );
			// chans
			
			foreach ( $chans as $chan )
			{
				$nusers = core::$chans[$chan]['users'];
				
				if ( $channel = services::chan_exists( $chan, array( 'channel', 'suspended', 'suspend_reason' ) ) )
				{
					if ( $channel->suspended == 1 )
					{
						foreach ( $nusers as $nick => $modes )
						{
							if ( !core::$nicks[$nick]['ircop'] )
								ircd::kick( core::$config->chanserv->nick, $nick, $channel->channel, $channel->suspend_reason );
						}
						// boot
					}
					// it's also suspended
				}
				// channel is registered
			}
		}
		// and same with channels being created
	}
}

// EOF;