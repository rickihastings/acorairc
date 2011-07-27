<?php

/*
* Acora IRC Services
* modules/register.cs.php: ChanServ register module
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

class cs_register extends module
{
	
	const MOD_VERSION = '0.1.6';
	const MOD_AUTHOR = 'Acora';
	
	static public $flags = 'FSRiktfrsqao';
	// module info and vars
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'NICK_UNREGISTERED'	=> 2,
		'NOT_IN_USE'		=> 3,
		'CANT_RECOVER_SELF' => 4,
		'NO_HOLD'			=> 5,
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
		modules::init_module( 'cs_register', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_register', 'help', chanserv::$help->CS_HELP_REGISTER_1, true );
		chanserv::add_help( 'cs_register', 'help register', chanserv::$help->CS_HELP_REGISTER_ALL );
		// add the help
		
		chanserv::add_command( 'register', 'cs_register', 'register_command' );
		// add the command
	}
	
	/*
	* register_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function register_command( $nick, $ircdata = array() )
	{
		if ( !core::$nicks[$nick]['identified'] )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_UNREGISTERED );
			return false;
		}
		// ph00s aint even registered..
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_register_chan( $input, $nick, $ircdata[0], core::get_data_after( $ircdata, 1 ) );
		// send data to _register_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _register_chan (command)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - Channel to register
	* $desc - Description
	*/
	static public function _register_chan( $input, $nick, $chan, $desc )
	{
		$return_data = module::$return_data;
		if ( trim( $desc ) == '' || $chan == '' || $chan[0] != '#' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'REGISTER' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
		
		if ( services::chan_exists( $chan, array( 'channel' ) ) !== false )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_REGISTERED_CHAN, array( 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->CHAN_REGISTERED;
			return $return_data;
		}
		// check if its registered?
		
		if ( !strstr( core::$chans[$chan]['users'][$nick], 'o' ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_NEED_CHAN_OP, array( 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NEED_CHAN_OP;
			return $return_data;
		}
		// we need to check if the user trying to register it has +o
		// if not we tell them to GET IT! YEAH, ALRIGHT!!
		
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
		database::insert( 'chans_levels', array( 'channel' => $chan, 'target' => $input['account'], 'flags' => self::$flags, 'setby' => $input['account'], 'timestamp' => core::$network_time ) );
		database::insert( 'chans_flags', array( 'channel' => $chan, 'flags' => $rflags.'d', 'desc' => $desc ) );
		// create the channel! WOOOH
		
		core::alog( core::$config->chanserv->nick.': '.$chan.' registered by ('.$input['hostname'].') ('.$input['account'].')' );
		core::alog( 'register_command(): '.$chan.' registered by '.$input['hostname'].' under: '.$input['account'], 'BASIC' );
		// log what we need to log.
		
		chanserv::$chan_q[$chan] = services::chan_exists( $chan, array( 'channel', 'timestamp', 'last_timestamp',  'topic', 'topic_setter', 'suspended', 'suspend_reason' ) );
			
		if ( chanserv::$chan_q[$chan] !== false )
			chanserv::_join_channel( chanserv::$chan_q[$chan] );
		// join the channel
		
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_CHAN_REGISTERED, array( 'chan' => $chan ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
	}
}

// EOF;