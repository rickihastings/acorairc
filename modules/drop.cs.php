<?php

/*
* Acora IRC Services
* modules/drop.cs.php: ChanServ drop module
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

class cs_drop extends module
{
	
	const MOD_VERSION = '0.0.5';
	const MOD_AUTHOR = 'Acora';
	
	static public $codes = array();
	// module info and vars
	
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
		
		chanserv::add_help( 'cs_drop', 'help', chanserv::$help->CS_HELP_DROP_1, true );
		chanserv::add_help( 'cs_drop', 'help drop', chanserv::$help->CS_HELP_DROP_ALL );
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
		$code = ( $ircdata[1] == '' ) ? '' : $ircdata[1];
		self::_drop_chan( $nick,  $ircdata[0], $code );
		// drop the channel
	}
	
	/*
	* _drop_chan (private)
	* 
	* @params
	* $nick - The nickname of the person issuing the command
	* $chan - The channel to drop
	*/
	static public function _drop_chan( $nick, $chan, $code )
	{
		if ( $chan == '' || $chan[0] != '#' )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'DROP' ) );
			return false;
		}
		// wrong syntax
		
		if ( !chanserv::_is_founder( $nick, $chan ) || !services::oper_privs( $nick, 'chanserv_op' ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
	
		if ( $channel = services::chan_exists( $chan, array( 'channel', 'suspended' ) ) )
		{
			if ( $channel->suspended == 1 )
			{
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_SUSPEND_1, array( 'chan' => $chan ) );
				return false;
			}
		}
		// is the channel suspended?
		else
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			return false;
		}
		// channel isn't even registered ffs.
		
		if ( trim( $code ) == '' )
		{
			$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
			$drop_code = '';    
			for ( $p = 0; $p < 10; $p++ )
				$drop_code .= $characters[mt_rand( 0, strlen( $characters ) )];
			// generate random code
				
			self::$codes[md5( core::$nicks[$nick]['account'].$chan )] = $drop_code;
			
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_CHAN_DROP_CODE, array( 'chan' => $chan, 'code' => $drop_code ) );
			return false;
		}
		if ( trim( $code ) != '' && $code != self::$codes[md5( core::$nicks[$nick]['account'].$chan )] )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_CHAN_INVALID_CODE );
			return false;
		}
		// set a confirmation code and send it back if none is specified
		// is a code is specified, AND it's correct, continue.
		
		database::delete( 'chans', array( 'channel', '=', $chan ) );
		database::delete( 'chans_flags', array( 'channel', '=', $chan ) );
		database::delete( 'chans_levels', array( 'channel', '=', $chan ) );
		// delete all associated records
		
		services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_CHAN_DROPPED, array( 'chan' => $chan ) );
		// let the user know
		
		if ( isset( core::$chans[$chan] ) && isset( core::$chans[$chan]['users'][core::$config->chanserv->nick] ) )
		{
			ircd::part_chan( core::$config->chanserv->nick, $chan );
			// now lets leave the channel if we're in it
		}
		// is the channel in existance? if so unregister mode
		// remember we DON'T unset the channel record, because the channel
		// is still there, just isnt registered, completely different things
		
		core::alog( core::$config->chanserv->nick.': '.$chan.' has been dropped by ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].')' );
		core::alog( 'drop_command(): '.$chan.' has been dropped by '.core::get_full_hostname( $nick ), 'BASIC' );
		// log what we need to log.
		
		unset( self::$codes[md5( core::$nicks[$nick]['account'].$chan )] );
		unset( chanserv::$chan_q[$chan] );
		// remove chanserv::$chan_q[$chan] just incase
	}
}

// EOF;