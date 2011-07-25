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

class cs_suspend extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'CHAN_UNREGISTERED'	=> 2,
		'ALREADY_SUSPENDED'	=> 3,
		'NOT_SUSPENDED'		=> 4,
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
		modules::init_module( 'cs_suspend', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_suspend', 'help', chanserv::$help->CS_HELP_SUSPEND_1, true, 'chanserv_op' );
		chanserv::add_help( 'cs_suspend', 'help', chanserv::$help->CS_HELP_UNSUSPEND_1, true, 'chanserv_op' );
		chanserv::add_help( 'cs_suspend', 'help suspend', chanserv::$help->CS_HELP_SUSPEND_ALL, false, 'chanserv_op' );
		chanserv::add_help( 'cs_suspend', 'help unsuspend', chanserv::$help->CS_HELP_UNSUSPEND_ALL, false, 'chanserv_op' );
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
		if ( !services::oper_privs( $nick, 'chanserv_op' ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// they've gotta be identified and opered..
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_suspend_chan( $input, $nick, $ircdata[0], core::get_data_after( $ircdata, 1 ) );
		// send to _suspend_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		if ( !services::oper_privs( $nick, 'chanserv_op' ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// they've gotta be identified.
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_unsuspend_chan( $input, $nick, $ircdata[0] );
		// send to _suspend_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _suspend_chan (command)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to suspend
	* $reason - The reason to suspend it with
	*/
	static public function _suspend_chan( $input, $nick, $chan, $reason )
	{
		$return_data = module::$return_data;
		
		if ( trim( $chan ) == '' || $chan[0] != '#' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'SUSPEND' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// make sure they've entered a channel
		
		if ( trim( $reason ) == '' ) $reason = 'No reason';
		// is there a reason? if not we set it to 'No Reason'
		
		if ( $channel = services::chan_exists( $chan, array( 'channel', 'suspended' ) ) )
		{
			if ( $channel->suspended == 1 )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_SUSPEND_2, array( 'chan' => $chan ) );
				$return_data[CMD_FAILCODE] = self::$return_codes->ALREADY_SUSPENDED;
				return $return_data;
			}
			// channel is already suspended
			
			database::update( 'chans', array( 'suspended' => 1, 'suspend_reason' => $reason ), array( 'channel', '=', $channel->channel ) );
			// channel isn't suspended, but it IS registered
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
		
		foreach ( core::$chans[$chan]['users'] as $user => $boolean )
		{
			if ( !core::$nicks[$nick]['ircop'] )
				ircd::kick( core::$config->chanserv->nick, $user, $chan, $reason );
		}
		// any users in the channel? KICK EM!! RAWR
		
		core::alog( core::$config->chanserv->nick.': ('.$input['hostname'].') ('.$input['account'].') SUSPENDED '.$chan.' with the reason ('.$reason.')' );
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_SUSPEND_3, array( 'chan' => $chan, 'reason' => $reason ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// log this and return.
	}
	
	/*
	* _unsuspend_chan (command)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to suspend
	*/
	static public function _unsuspend_chan( $input, $nick, $chan )
	{
		$return_data = module::$return_data;
		
		if ( trim( $chan ) == '' || $chan[0] != '#' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'UNSUSPEND' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// make sure they've entered a channel
		
		if ( !$channel = services::chan_exists( $chan, array( 'channel', 'suspended' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_SUSPEND_4, array( 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->CHAN_UNREGISTERED;
			return $return_data;
		}
		// chan isn't registered...
		
		if ( $channel->suspended == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_SUSPEND_4, array( 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NOT_SUSPENDED;
			return $return_data;
		}
		// channel isn't even suspended
		
		$check_row = database::select( 'chans_levels', array( 'channel' ), array( 'channel', '=', $chan ) );
		if ( database::num_rows( $check_row ) == 0 )
		{
			database::delete( 'chans', array( 'channel', '=', $chan ) );
			database::delete( 'chans_flags', array( 'channel', '=', $chan ) );
			// the channel has no access records, drop it. this means it was a suspend on a non-registered channel
		}
		else
		{
			database::update( 'chans', array( 'suspended' => 0 ), array( 'channel', '=', $chan ) );
			// channel has access rows which means it was pre-registered, just update it don't drop it
		}

		core::alog( core::$config->chanserv->nick.': ('.$input['hostname'].') ('.$input['account'].') UNSUSPENDED '.$chan );
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_SUSPEND_5, array( 'chan' => $chan ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// log this and return.
	}
	
	/*
	* on_chan_create (event hook)
	*/
	static public function on_chan_create( $chan )
	{
		$nusers = core::$chans[$chan]['users'];
		$channel = chanserv::$chan_q[$chan];
		
		if ( $channel === false )
			return false;
		if ( $channel->suspended == 0 )
			return false;
		// channel isnt registered or suspended!
		
		foreach ( $nusers as $nick => $modes )
		{
			if ( !core::$nicks[$nick]['ircop'] )
				ircd::kick( core::$config->chanserv->nick, $nick, $channel->channel, $channel->suspend_reason );
		}
		// boot
	}
	
	/*
	* on_join (event hook)
	*/
	static public function on_join( $nick, $chan )
	{
		$channel = chanserv::$chan_q[$chan];
	
		if ( $channel === false )
			return false;
		if ( $channel->suspended == 0 )
			return false;
		// channel isnt registered or suspended
			
		if ( !core::$nicks[$nick]['ircop'] )
			ircd::kick( core::$config->chanserv->nick, $nick, $channel->channel, $channel->suspend_reason );
		// boot
	}
}

// EOF;