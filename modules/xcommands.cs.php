<?php

/*
* Acora IRC Services
* modules/xcommands.cs.php: ChanServ xcommands module
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

class cs_xcommands implements module
{
	
	const MOD_VERSION = '0.0.3';
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
		
		chanserv::add_help_fix( 'cs_xcommands', 'prefix', 'help commands', &chanserv::$help->CS_XCOMMANDS_PREFIX );
		chanserv::add_help_fix( 'cs_xcommands', 'suffix', 'help commands', &chanserv::$help->CS_XCOMMANDS_SUFFIX );
		chanserv::add_help( 'cs_xcommands', 'help', &chanserv::$help->CS_HELP_XCOMMANDS_1 );
		
		chanserv::add_help( 'cs_xcommands', 'help', &chanserv::$help->CS_HELP_CLEAR_1 );
		// clear command
		
		chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_KICK_1 );
		chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_KICKBAN_1 );
		chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_BAN_1 );
		chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_UNBAN_1 );
		// add them to the "help commands" category
		
		chanserv::add_help( 'cs_xcommands', 'help clear', &chanserv::$help->CS_HELP_CLEAR_ALL );
		// clear command
		
		chanserv::add_help( 'cs_xcommands', 'help kick', &chanserv::$help->CS_HELP_KICK_ALL );
		chanserv::add_help( 'cs_xcommands', 'help kickban', &chanserv::$help->CS_HELP_KICK_ALL );
		chanserv::add_help( 'cs_xcommands', 'help ban', &chanserv::$help->CS_HELP_BAN_ALL );
		chanserv::add_help( 'cs_xcommands', 'help unban', &chanserv::$help->CS_HELP_BAN_ALL );
		// and add their seperate help docs
		
		if ( ircd::$owner )
		{
			chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_OWNER_1 );
			chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_DEOWNER_1 );
			chanserv::add_help( 'cs_xcommands', 'help owner', &chanserv::$help->CS_HELP_XCOMMANDS_OWNER );
			chanserv::add_help( 'cs_xcommands', 'help deowner', &chanserv::$help->CS_HELP_XCOMMANDS_OWNER );
		}
		// add help for owner commands
		
		if ( ircd::$protect )
		{
			chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_PROTECT_1 );
			chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_DEPROTECT_1 );
			chanserv::add_help( 'cs_xcommands', 'help protect', &chanserv::$help->CS_HELP_XCOMMANDS_PROTECT );
			chanserv::add_help( 'cs_xcommands', 'help deprotect', &chanserv::$help->CS_HELP_XCOMMANDS_PROTECT );
		}
		// add help for protect commands
		
		chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_OP_1 );
		chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_DEOP_1 );
		chanserv::add_help( 'cs_xcommands', 'help op', &chanserv::$help->CS_HELP_XCOMMANDS_OP );
		chanserv::add_help( 'cs_xcommands', 'help deop', &chanserv::$help->CS_HELP_XCOMMANDS_OP );
		// now op
		
		if ( ircd::$halfop )
		{
			chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_HALFOP_1 );
			chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_DEHALFOP_1 );
			chanserv::add_help( 'cs_xcommands', 'help halfop', &chanserv::$help->CS_HELP_XCOMMANDS_HALFOP );
			chanserv::add_help( 'cs_xcommands', 'help dehalfop', &chanserv::$help->CS_HELP_XCOMMANDS_HALFOP );
		}
		// halfop
		
		chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_VOICE_1 );
		chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_DEVOICE_1 );
		chanserv::add_help( 'cs_xcommands', 'help voice', &chanserv::$help->CS_HELP_XCOMMANDS_VOICE );
		chanserv::add_help( 'cs_xcommands', 'help devoice', &chanserv::$help->CS_HELP_XCOMMANDS_VOICE );
		
		chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_MODE_1 );
		chanserv::add_help( 'cs_xcommands', 'help mode', &chanserv::$help->CS_HELP_MODE_ALL );
		// voice and mode
		
		chanserv::add_help( 'cs_xcommands', 'help commands', &chanserv::$help->CS_HELP_TYPEMASK_1 );
		chanserv::add_help( 'cs_xcommands', 'help typemask', &chanserv::$help->CS_HELP_TYPEMASK_ALL );
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
		// and the rest, voice & mode.
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'CLEAR' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		ircd::mode( core::$config->chanserv->nick, $chan, '-'.core::$chans[$chan]['modes'] );
		// remove standard modes
		foreach ( core::$chans[$chan]['users'] as $user => $modes )
		{
			if ( $modes == '' )
				continue;
			// if modes is empty skip
			if ( $user == core::$config->chanserv->nick || $user == core::$config->global->nick )
				continue;
			// if its chanserv or global, skip
			
			$c_modes = count( str_split( $modes ) );
			$mode_string = '-'.$modes;
			
			for ( $i = 0; $i < $c_modes; $i++ )
				$mode_string .= ' '.$user;
			// generate a mode string.
			
			ircd::mode( core::$config->chanserv->nick, $chan, $mode_string );
			// send the mode string
		}
		// status modes
		foreach ( core::$chans[$chan]['p_modes'] as $mask => $modes )
		{
			$c_modes = count( str_split( $modes ) );
			$mode_string = '-'.$modes;
			
			for ( $i = 0; $i < $c_modes; $i++ )
				$mode_string .= ' '.$mask;
			// generate a mode string.
			
			ircd::mode( core::$config->chanserv->nick, $chan, $mode_string );
			// send the mode string
		}
		// bans etc.
		
		$modelock = self::get_flags( $chan, 'm' );
		// store some flag values in variables.
		
		if ( $modelock != null )
			ircd::mode( core::$config->chanserv->nick, $chan, $modelock );
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '+'.ircd::$default_c_modes );
		// reset default modes
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'MODE' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( $ircdata[1] == '' )
		{
			ircd::mode( core::$config->chanserv->nick, $chan, '-'.str_replace( 'r', '', core::$chans[$chan]['modes'] ) );
			ircd::mode( core::$config->chanserv->nick, $chan, '+'.ircd::$default_c_modes );
			// we reset the channel modes if there is no first value
		}
		else
		{
			$mode_queue = core::get_data_after( &$ircdata, 1 );
			// get the mode queue
			
			if ( !core::$nicks[$nick]['ircop'] )
				$mode_queue[0] = str_replace( 'O', '', $mode_queue[0] );
			// don't let them MODE +O if they're not an IRCop
						
			ircd::mode( core::$config->chanserv->nick, $chan, $mode_queue );
			// mode has parameters so set the whole mode string
		}
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'OWNER' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'q', 'f', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $ircdata[1], ':' ) !== false )
			mode::type_check( $chan, $ircdata[1], '+q', core::$config->chanserv->nick );
		elseif ( isset( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '+q '.$ircdata[1] );
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '+q '.$nick );
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'DEOWNER' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'q', 'f', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
			
		if ( strtolower( $ircdata[1] ) == strtolower( core::$config->chanserv->nick ) ) return false;
		
		if ( strpos( $ircdata[1], ':' ) !== false )
			mode::type_check( $chan, $ircdata[1], '-q', core::$config->chanserv->nick );
		elseif ( isset( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '-q '.$ircdata[1] );
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '-q '.$nick );
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'PROTECT' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'a', 'q', 'f', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $ircdata[1], ':' ) !== false )
			mode::type_check( $chan, $ircdata[1], '+a', core::$config->chanserv->nick );
		elseif ( isset( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '+a '.$ircdata[1] );
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '+a '.$nick );
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'DEPROTECT' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'a', 'q', 'f', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
			
		if ( strtolower( $ircdata[1] ) == strtolower( core::$config->chanserv->nick ) ) return false;
		
		if ( strpos( $ircdata[1], ':' ) !== false )
			mode::type_check( $chan, $ircdata[1], '-a', core::$config->chanserv->nick );
		elseif ( isset( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '-a '.$ircdata[1] );
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '-a '.$nick );
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'OP' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'o', 'a', 'q', 'f', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $ircdata[1], ':' ) !== false )
			mode::type_check( $chan, $ircdata[1], '+o', core::$config->chanserv->nick );
		elseif ( isset( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '+o '.$ircdata[1] );
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '+o '.$nick );
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'DEOP' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'o', 'a', 'q', 'f', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strtolower( $ircdata[1] ) == strtolower( core::$config->chanserv->nick ) ) return false;
		
		if ( strpos( $ircdata[1], ':' ) !== false )
			mode::type_check( $chan, $ircdata[1], '-o', core::$config->chanserv->nick );
		elseif ( isset( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '-o '.$ircdata[1] );
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '-o '.$nick );
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'HALFOP' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'f', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $ircdata[1], ':' ) !== false )
			mode::type_check( $chan, $ircdata[1], '+h', core::$config->chanserv->nick );
		elseif ( isset( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '+h '.$ircdata[1] );
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '+h '.$nick );
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'DEHALFOP' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'f', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $ircdata[1], ':' ) !== false )
			mode::type_check( $chan, $ircdata[1], '-h', core::$config->chanserv->nick );
		elseif ( isset( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '-h '.$ircdata[1] );
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '-h '.$nick );
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'VOICE' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'v', 'h', 'o', 'a', 'q', 'f', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $ircdata[1], ':' ) !== false )
			mode::type_check( $chan, $ircdata[1], '+v', core::$config->chanserv->nick );
		elseif ( isset( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '+v '.$ircdata[1] );
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '+v '.$nick );
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
		$chan = $ircdata[0];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'DEVOICE' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'v', 'h', 'o', 'a', 'q', 'f', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $ircdata[1], ':' ) !== false )
			mode::type_check( $chan, $ircdata[1], '-v', core::$config->chanserv->nick );
		elseif ( isset( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '-v '.$ircdata[1] );
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '-v '.$nick );
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
		$chan = $ircdata[0];
		$who = $ircdata[1];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'KICK' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'r', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( chanserv::check_levels( $nick, $channel->channel, array( 'o', 'F' ) ) && chanserv::check_levels( $nick, $channel->channel, array( 'o', 'F' ) ) === false )
			return false;
		// check if the user kicking, has the access to kick them. that doesn't make sense, but yeah.		
		
		$reason = core::get_data_after( &$ircdata, 2 );
					
		ircd::kick( core::$config->chanserv->nick, $who, $chan, '('.$nick.') '.( $reason != '' ) ? $reason : 'No reason' );
		// kick them with the reason
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
		$chan = $ircdata[0];
		$who = $ircdata[1];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'KICKBAN' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'r', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( chanserv::check_levels( $nick, $chan, array( 'o', 'F' ) ) && chanserv::check_levels( $nick, $chan, array( 'o', 'F' ) ) === false )
			return false;
		// check if the user kicking, has the access to kick them. that doesn't make sense, but yeah.		
		
		$reason = core::get_data_after( &$ircdata, 2 );
		
		if ( $user = core::search_nick( $ircdata[1] ) )
		{
			ircd::mode( core::$config->chanserv->nick, $chan, '+b *@'.$user['host'] );			
			ircd::kick( core::$config->chanserv->nick, $who, $chan, '('.$nick.') '.( $reason != '' ) ? $reason : 'No reason' );
			// kick them with the reason
		}
		else
		{
			return false;
		}
		// we check if the user exists.
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
		$chan = $ircdata[0];
		$who = $ircdata[1];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'BAN' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'r', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( chanserv::check_levels( $nick, $chan, array( 'o', 'F' ) ) && chanserv::check_levels( $nick, $chan, array( 'o', 'F' ) ) === false )
			return false;
		// check if the user kicking, has the access to kick them. that doesn't make sense, but yeah.		
		
		if ( strpos( $ircdata[1], '@' ) === false && $user = core::search_nick( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '+b *@'.$user['host'] );			
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '+b '.$ircdata[1] );
		// +b
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
		$chan = $ircdata[0];
		$who = $ircdata[1];
		// standard data here.
		
		if ( self::check_channel( $nick, $chan, 'UNBAN' ) === false )
			return false;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'r', 'F' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// do they have access?
		
		if ( strpos( $ircdata[1], '@' ) === false && $user = core::search_nick( $ircdata[1] ) )
			ircd::mode( core::$config->chanserv->nick, $chan, '-b *@'.$user['host'] );			
		else
			ircd::mode( core::$config->chanserv->nick, $chan, '-b '.$ircdata[1] );
		// -b
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
		// straight out of this function
		// as we don't have to listen for anything..
		// triggers etc.
	}
	
	/*
	* check_channel (private)
	* 
	* @params
	* $nick - nick of who issues the command
	* $chan - check the chan entered is valid, and stuff
	* $help - should be the name of the command, in caps
	*/
	static public function check_channel( $nick, $chan, $help )
	{
		if ( $chan == '' || $chan[0] != '#' )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => $help ) );
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
	}
}

// EOF;