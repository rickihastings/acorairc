<?php

/*
* Acora IRC Services
* modules/xcommands.cs.php: ChanServ xcommands module
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

class cs_xcommands extends module
{
	
	const MOD_VERSION = '0.0.5';
	const MOD_AUTHOR = 'Acora';
	
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
		modules::init_module( 'cs_xcommands', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		// these are standard in module constructors
		
		chanserv::add_help_fix( 'cs_xcommands', 'prefix', 'help commands', chanserv::$help->CS_XCOMMANDS_PREFIX );
		chanserv::add_help_fix( 'cs_xcommands', 'suffix', 'help commands', chanserv::$help->CS_XCOMMANDS_SUFFIX );
		chanserv::add_help( 'cs_xcommands', 'help', chanserv::$help->CS_HELP_XCOMMANDS_1, true );
		
		chanserv::add_help( 'cs_xcommands', 'help', chanserv::$help->CS_HELP_CLEAR_1, true );
		// clear command
		
		chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_KICK_1, true );
		chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_KICKBAN_1, true );
		chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_BAN_1, true );
		chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_UNBAN_1, true );
		// add them to the "help commands" category
		
		chanserv::add_help( 'cs_xcommands', 'help clear', chanserv::$help->CS_HELP_CLEAR_ALL );
		// clear command
		
		chanserv::add_help( 'cs_xcommands', 'help kick', chanserv::$help->CS_HELP_KICK_ALL );
		chanserv::add_help( 'cs_xcommands', 'help kickban', chanserv::$help->CS_HELP_KICK_ALL );
		chanserv::add_help( 'cs_xcommands', 'help ban', chanserv::$help->CS_HELP_BAN_ALL );
		chanserv::add_help( 'cs_xcommands', 'help unban', chanserv::$help->CS_HELP_BAN_ALL );
		// and add their seperate help docs
		
		if ( ircd::$owner )
		{
			chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_OWNER_1, true );
			chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_DEOWNER_1, true );
			chanserv::add_help( 'cs_xcommands', 'help owner', chanserv::$help->CS_HELP_XCOMMANDS_OWNER );
			chanserv::add_help( 'cs_xcommands', 'help deowner', chanserv::$help->CS_HELP_XCOMMANDS_OWNER );
		}
		// add help for owner commands
		
		if ( ircd::$protect )
		{
			chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_PROTECT_1, true );
			chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_DEPROTECT_1, true );
			chanserv::add_help( 'cs_xcommands', 'help protect', chanserv::$help->CS_HELP_XCOMMANDS_PROTECT );
			chanserv::add_help( 'cs_xcommands', 'help deprotect', chanserv::$help->CS_HELP_XCOMMANDS_PROTECT );
		}
		// add help for protect commands
		
		chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_OP_1, true );
		chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_DEOP_1, true );
		chanserv::add_help( 'cs_xcommands', 'help op', chanserv::$help->CS_HELP_XCOMMANDS_OP );
		chanserv::add_help( 'cs_xcommands', 'help deop', chanserv::$help->CS_HELP_XCOMMANDS_OP );
		// now op
		
		if ( ircd::$halfop )
		{
			chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_HALFOP_1, true );
			chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_DEHALFOP_1, true );
			chanserv::add_help( 'cs_xcommands', 'help halfop', chanserv::$help->CS_HELP_XCOMMANDS_HALFOP );
			chanserv::add_help( 'cs_xcommands', 'help dehalfop', chanserv::$help->CS_HELP_XCOMMANDS_HALFOP );
		}
		// halfop
		
		chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_VOICE_1, true );
		chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_DEVOICE_1, true );
		chanserv::add_help( 'cs_xcommands', 'help voice', chanserv::$help->CS_HELP_XCOMMANDS_VOICE );
		chanserv::add_help( 'cs_xcommands', 'help devoice', chanserv::$help->CS_HELP_XCOMMANDS_VOICE );
		
		chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_MODE_1, true );
		chanserv::add_help( 'cs_xcommands', 'help mode', chanserv::$help->CS_HELP_MODE_ALL );
		
		chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_SYNC_1, true );
		chanserv::add_help( 'cs_xcommands', 'help sync', chanserv::$help->CS_HELP_SYNC_ALL );
		// voice and mode & sync
		
		chanserv::add_help( 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_TYPEMASK_1, true );
		chanserv::add_help( 'cs_xcommands', 'help typemask', chanserv::$help->CS_HELP_TYPEMASK_ALL );
		// typemask
		
		chanserv::add_command( 'clear', 'cs_xcommands', 'clear_command' );
		// clear command
				
		chanserv::add_command( 'kick', 'cs_xcommands', 'kick_command' );
		chanserv::add_command( 'kickban', 'cs_xcommands', 'kickban_command' );
		chanserv::add_command( 'ban', 'cs_xcommands', 'ban_command' );
		chanserv::add_command( 'unban', 'cs_xcommands', 'unban_command' );
		// add the commands for kick/bans etc
		
		if ( ircd::$owner )
		{
			chanserv::add_command( 'owner', 'cs_xcommands', 'owner_command' );
			chanserv::add_command( 'deowner', 'cs_xcommands', 'deowner_command' );
		}
		// protect
		
		if ( ircd::$protect )
		{
			chanserv::add_command( 'protect', 'cs_xcommands', 'protect_command' );
			chanserv::add_command( 'deprotect', 'cs_xcommands', 'deprotect_command' );
		}
		// protect
		
		chanserv::add_command( 'op', 'cs_xcommands', 'op_command' );
		chanserv::add_command( 'deop', 'cs_xcommands', 'deop_command' );
		// op
		
		if ( ircd::$halfop )
		{
			chanserv::add_command( 'halfop', 'cs_xcommands', 'halfop_command' );
			chanserv::add_command( 'dehalfop', 'cs_xcommands', 'dehalfop_command' );
		}
		// halfop
		
		chanserv::add_command( 'voice', 'cs_xcommands', 'voice_command' );
		chanserv::add_command( 'devoice', 'cs_xcommands', 'devoice_command' );
		chanserv::add_command( 'mode', 'cs_xcommands', 'mode_command' );
		chanserv::add_command( 'sync', 'cs_xcommands', 'sync_command' );
		// and the rest, voice & mode & sync.
	}
	
	/*
	* clear_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function clear_command( $nick, $ircdata = array() )
	{
		self::_clear_chan( $nick, $ircdata[0] );
		// call _clear_chan
	}
	
	/*
	* sync_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function sync_command( $nick, $ircdata = array() )
	{
		self::_sync_chan( $nick, $ircdata[0] );
		// call _sync_chan
	}
	
	/*
	* mode_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function mode_command( $nick, $ircdata = array() )
	{
		self::_mode_chan( $nick, $ircdata[0], core::get_data_after( $ircdata, 1 ) );
		// call _mode_chan
	}
	
	/*
	* owner_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function owner_command( $nick, $ircdata = array() )
	{
		self::_owner_chan( $nick, $ircdata[0], '+', $ircdata[1] );
		// call _owner_chan
	}
	
	/*
	* deowner_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function deowner_command( $nick, $ircdata = array() )
	{
		self::_owner_chan( $nick, $ircdata[0], '-', $ircdata[1] );
		// call _owner_chan
	}
		
	/*
	* protect_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function protect_command( $nick, $ircdata = array() )
	{
		self::_protect_chan( $nick, $ircdata[0], '+', $ircdata[1] );
		// call _protect_chan
	}
	
	/*
	* deprotect_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function deprotect_command( $nick, $ircdata = array() )
	{
		self::_protect_chan( $nick, $ircdata[0], '-', $ircdata[1] );
		// call _protect_chan
	}
	
	/*
	* op_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function op_command( $nick, $ircdata = array() )
	{
		self::_op_chan( $nick, $ircdata[0], '+', $ircdata[1] );
		// call _op_chan
	}
	
	/*
	* deop_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function deop_command( $nick, $ircdata = array() )
	{
		self::_op_chan( $nick, $ircdata[0], '-', $ircdata[1] );
		// call _op_chan
	}
	
	/*
	* halfop_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function halfop_command( $nick, $ircdata = array() )
	{
		self::_halfop_chan( $nick, $ircdata[0], '+', $ircdata[1] );
		// call _halfop_chan
	}
	
	/*
	* dehalfop_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function dehalfop_command( $nick, $ircdata = array() )
	{
		self::_halfop_chan( $nick, $ircdata[0], '-', $ircdata[1] );
		// call _halfop_chan
	}
	
	/*
	* voice_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function voice_command( $nick, $ircdata = array() )
	{
		self::_voice_chan( $nick, $ircdata[0], '+', $ircdata[1] );
		// call _voice_chan
	}
	
	/*
	* devoice_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function devoice_command( $nick, $ircdata = array() )
	{
		self::_voice_chan( $nick, $ircdata[0], '-', $ircdata[1] );
		// call _voice_chan
	}
	
	/*
	* kick_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function kick_command( $nick, $ircdata = array() )
	{
		self::_kick_chan( $nick, $ircdata[0], $ircdata[1], core::get_data_after( $ircdata, 2 ) );
		// call _kick_chan
	}
	
	/*
	* kickban_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function kickban_command( $nick, $ircdata = array() )
	{
		self::_kickban_chan( $nick, $ircdata[0], $ircdata[1], core::get_data_after( $ircdata, 2 ) );
		// call _kickban_chan
	}
	
	/*
	* ban_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function ban_command( $nick, $ircdata = array() )
	{
		self::_ban_chan( $nick, $ircdata[0], $ircdata[1] );
		// call _ban_chan
	}
	
	/*
	* unban_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function unban_command( $nick, $ircdata = array() )
	{
		self::_unban_chan( $nick, $ircdata[0], $ircdata[1] );
		// call _unban_chan
	}
	
	/*
	* _check_channel (private)
	* 
	* @params
	* $nick - nick of who issues the command
	* $chan - check the chan entered is valid, and stuff
	* $help - should be the name of the command, in caps
	*/
	static public function _check_channel( $nick, $chan, $help )
	{
		if ( $chan == '' || $chan[0] != '#' )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => $help ) );
			return false;
			// wrong syntax
		}
		// make sure they've entered a channel
		
		if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			return false;
		}
		// make sure the channel exists.
		
		return $channel;
	}
	
	/*
	* _clear_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	*/
	static public function _clear_chan( $nick, $chan )
	{
		if ( !self::check_channel( $nick, $chan, 'CLEAR' ) )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'R', 'S', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		mode::set( core::$config->chanserv->nick, $chan, '-'.core::$chans[$chan]['modes'] );
		// remove standard modes

		mode::mass_mode( $chan, '-', core::$chans[$chan]['users'], core::$config->chanserv->nick );
		mode::mass_mode( $chan, '-', core::$chans[$chan]['p_modes'], core::$config->chanserv->nick );
		// bans etc.
		
		$modelock = chanserv::get_flags( $chan, 'm' );
		// store some flag values in variables.
		
		if ( $modelock != null )
			mode::set( core::$config->chanserv->nick, $chan, $modelock );
		else
			mode::set( core::$config->chanserv->nick, $chan, '+'.ircd::$default_c_modes );
		// reset default modes
		
		services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_CLEAR_CHAN, array( 'chan' => $chan ) );
	}
	
	/*
	* _sync_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	*/
	static public function _sync_chan( $nick, $chan )
	{
		if ( !$channel = self::check_channel( $nick, $chan, 'SYNC' ) )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'q', 'f', 'S', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		cs_levels::on_create( core::$chans[$chan]['users'], $channel );
		// execute on_create, cause we just treat it as that
		// this is kinda a shortcut, but well worth it.
		
		services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_SYNC_CHAN, array( 'chan' => $chan ) );
	}
	
	/*
	* _mode_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode_queue - Modes to set
	*/
	static public function _mode_chan( $nick, $chan, $mode_queue )
	{
		if ( !self::check_channel( $nick, $chan, 'MODE' ) )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'S', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( $mode_queue == '' )
		{
			mode::set( core::$config->chanserv->nick, $chan, '+'.ircd::$default_c_modes );
			// we reset the channel modes if there is no first value
		}
		else
		{
			$mode_queue = explode( ' ', $mode_queue );
			if ( !core::$nicks[$nick]['ircop'] )
				$mode_queue[0] = str_replace( 'O', '', $mode_queue[0] );
			$mode_queue = implode( ' ', $mode_queue );
			// don't let them MODE +O if they're not an IRCop
						
			mode::set( core::$config->chanserv->nick, $chan, $mode_queue );
			// mode has parameters so set the whole mode string
		}
	}
	
	/*
	* _owner_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode - +/-
	* $unick - The nickname to use
	*/
	static public function _owner_chan( $nick, $chan, $mode, $unick )
	{
		if ( $mode == '+' ) $type = 'OWNER';
		else $type = 'DEOWNER';
		
		if ( !self::check_channel( $nick, $chan, $type ) )
			return false;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'q', 'f', 'S', 'F' ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $unick, ':' ) !== false )
			mode::type_check( $chan, $unick, $mode.'q', core::$config->chanserv->nick );
		elseif ( $unick != '' )
			mode::set( core::$config->chanserv->nick, $chan, $mode.'q '.$unick );
		else
			mode::set( core::$config->chanserv->nick, $chan, $mode.'q '.$nick );
		// set modes
	}
	
	/*
	* _protect_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode - +/-
	* $unick - The nickname to use
	*/
	static public function _protect_chan( $nick, $chan, $mode, $unick )
	{
		if ( $mode == '+' ) $type = 'PROTECT';
		else $type = 'DEPROTECT';
		
		if ( !self::check_channel( $nick, $chan, $type ) )
			return false;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'a', 'q', 'f', 'S', 'F' ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $unick, ':' ) !== false )
			mode::type_check( $chan, $unick, $mode.'a', core::$config->chanserv->nick );
		elseif ( $unick != '' )
			mode::set( core::$config->chanserv->nick, $chan, $mode.'a '.$unick );
		else
			mode::set( core::$config->chanserv->nick, $chan, $mode.'a '.$nick );
		// set modes
	}
	
	/*
	* _op_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode - +/-
	* $unick - The nickname to use
	*/
	static public function _op_chan( $nick, $chan, $mode, $unick )
	{
		if ( $mode == '+' ) $type = 'OP';
		else $type = 'DEOP';
		
		if ( !self::check_channel( $nick, $chan, $type ) )
			return false;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'o', 'a', 'q', 'f', 'S', 'F' ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $unick, ':' ) !== false )
			mode::type_check( $chan, $unick, $mode.'o', core::$config->chanserv->nick );
		elseif ( $unick != '' )
			mode::set( core::$config->chanserv->nick, $chan, $mode.'o '.$unick );
		else
			mode::set( core::$config->chanserv->nick, $chan, $mode.'o '.$nick );
		// set modes
	}
	
	/*
	* _halfop_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode - +/-
	* $unick - The nickname to use
	*/
	static public function _halfop_chan( $nick, $chan, $mode, $unick )
	{
		if ( $mode == '+' ) $type = 'HALFOP';
		else $type = 'DEHALFOP';
		
		if ( !self::check_channel( $nick, $chan, $type ) )
			return false;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $unick, ':' ) !== false )
			mode::type_check( $chan, $unick, $mode.'h', core::$config->chanserv->nick );
		elseif ( $unick != '' )
			mode::set( core::$config->chanserv->nick, $chan, $mode.'h '.$unick );
		else
			mode::set( core::$config->chanserv->nick, $chan, $mode.'h '.$nick );
		// set modes
	}
	
	/*
	* _voice_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode - +/-
	* $unick - The nickname to use
	*/
	static public function _voice_chan( $nick, $chan, $mode, $unick )
	{
		if ( $mode == '+' ) $type = 'VOICE';
		else $type = 'DEVOICE';
		
		if ( !self::check_channel( $nick, $chan, $type ) )
			return false;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'v', 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $unick, ':' ) !== false )
			mode::type_check( $chan, $unick, $mode.'v', core::$config->chanserv->nick );
		elseif ( $unick != '' )
			mode::set( core::$config->chanserv->nick, $chan, $mode.'v '.$unick );
		else
			mode::set( core::$config->chanserv->nick, $chan, $mode.'v '.$nick );
		// set modes
	}
	
	/*
	* _kick_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $unick - The nickname to use
	*/
	static public function _kick_chan( $nick, $chan, $who, $reason )
	{
		if ( !self::check_channel( $nick, $chan, 'KICK' ) )
			return false;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'r', 'S', 'F' ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( chanserv::check_levels( $who, $channel->channel, array( 'S', 'F' ) ) )
			return false;
		// you can't k/b anyone with either +S or +F, others can be k/bed though.	
		
		if ( $user = core::search_nick( $who ) )
		{
			$who = $user['nick'];
			ircd::kick( core::$config->chanserv->nick, $who, $chan, '('.$nick.') '.( $reason != '' ) ? $reason : 'No reason' );
		}
		// kick them with the reason
	}
	
	/*
	* _kickban_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $unick - The nickname to use
	*/
	static public function _kickban_chan( $nick, $chan, $who, $reason )
	{
		if ( !self::check_channel( $nick, $chan, 'KICKBAN' ) )
			return false;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'r', 'S', 'F' ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( chanserv::check_levels( $who, $channel->channel, array( 'S', 'F' ) ) )
			return false;
		// you can't k/b anyone with either +S or +F, others can be k/bed though.	
		
		if ( $user = core::search_nick( $who ) )
		{
			mode::set( core::$config->chanserv->nick, $chan, '+b *@'.$user['host'] );			
			ircd::kick( core::$config->chanserv->nick, $user['nick'], $chan, '('.$nick.') '.( $reason != '' ) ? $reason : 'No reason' );
			// kick them with the reason
		}
		// we check if the user exists.
	}
	
	/*
	* _ban_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $unick - The nickname to use
	*/
	static public function _ban_chan( $nick, $chan, $who )
	{
		if ( !self::check_channel( $nick, $chan, 'BAN' ) )
			return false;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'r', 'S', 'F' ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( chanserv::check_levels( $who, $channel->channel, array( 'S', 'F' ) ) )
			return false;
		// you can't k/b anyone with either +S or +F, others can be k/bed though.
		
		if ( strpos( $who, '@' ) === false && $user = core::search_nick( $who ) )
			mode::set( core::$config->chanserv->nick, $chan, '+b *@'.$user['host'] );			
		else
			mode::set( core::$config->chanserv->nick, $chan, '+b '.$who );
		// +b
	}
	
	/*
	* _unban_chan (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $unick - The nickname to use
	*/
	static public function _unban_chan( $nick, $chan, $who )
	{
		if ( !self::check_channel( $nick, $chan, 'UNBAN' ) )
			return false;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'r', 'S', 'F' ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $who, '@' ) === false && $user = core::search_nick( $who ) )
			mode::set( core::$config->chanserv->nick, $chan, '-b *@'.$user['host'] );			
		else
			mode::set( core::$config->chanserv->nick, $chan, '-b '.$who );
		// -b
	}
}

// EOF;