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
	
	const MOD_VERSION = '0.1.6';
	const MOD_AUTHOR = 'Acora';
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'NICK_UNREGISTERED'	=> 2,
		'INVALID_HOSTNAME'	=> 3,
		'NO_VHOST'			=> 4,
		'NO_VHOST_REQUEST'	=> 5,
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
		modules::init_module( __CLASS__, self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		commands::add_help_fix( 'chanserv', 'cs_xcommands', 'prefix', 'help commands', chanserv::$help->CS_XCOMMANDS_PREFIX );
		commands::add_help_fix( 'chanserv', 'cs_xcommands', 'suffix', 'help commands', chanserv::$help->CS_XCOMMANDS_SUFFIX );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help', chanserv::$help->CS_HELP_XCOMMANDS_1, true );
		
		commands::add_help( 'chanserv', 'cs_xcommands', 'help', chanserv::$help->CS_HELP_CLEAR_1, true );
		// clear command
		
		commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_KICK_1, true );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_KICKBAN_1, true );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_BAN_1, true );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_UNBAN_1, true );
		// add them to the "help commands" category
		
		commands::add_help( 'chanserv', 'cs_xcommands', 'help clear', chanserv::$help->CS_HELP_CLEAR_ALL );
		// clear command
		
		commands::add_help( 'chanserv', 'cs_xcommands', 'help kick', chanserv::$help->CS_HELP_KICK_ALL );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help kickban', chanserv::$help->CS_HELP_KICK_ALL );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help ban', chanserv::$help->CS_HELP_BAN_ALL );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help unban', chanserv::$help->CS_HELP_BAN_ALL );
		// and add their seperate help docs
		
		if ( ircd::$owner )
		{
			commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_OWNER_1, true );
			commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_DEOWNER_1, true );
			commands::add_help( 'chanserv', 'cs_xcommands', 'help owner', chanserv::$help->CS_HELP_XCOMMANDS_OWNER );
			commands::add_help( 'chanserv', 'cs_xcommands', 'help deowner', chanserv::$help->CS_HELP_XCOMMANDS_OWNER );
		}
		// add help for owner commands
		
		if ( ircd::$protect )
		{
			commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_PROTECT_1, true );
			commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_DEPROTECT_1, true );
			commands::add_help( 'chanserv', 'cs_xcommands', 'help protect', chanserv::$help->CS_HELP_XCOMMANDS_PROTECT );
			commands::add_help( 'chanserv', 'cs_xcommands', 'help deprotect', chanserv::$help->CS_HELP_XCOMMANDS_PROTECT );
		}
		// add help for protect commands
		
		commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_OP_1, true );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_DEOP_1, true );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help op', chanserv::$help->CS_HELP_XCOMMANDS_OP );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help deop', chanserv::$help->CS_HELP_XCOMMANDS_OP );
		// now op
		
		if ( ircd::$halfop )
		{
			commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_HALFOP_1, true );
			commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_DEHALFOP_1, true );
			commands::add_help( 'chanserv', 'cs_xcommands', 'help halfop', chanserv::$help->CS_HELP_XCOMMANDS_HALFOP );
			commands::add_help( 'chanserv', 'cs_xcommands', 'help dehalfop', chanserv::$help->CS_HELP_XCOMMANDS_HALFOP );
		}
		// halfop
		
		commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_VOICE_1, true );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_DEVOICE_1, true );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help voice', chanserv::$help->CS_HELP_XCOMMANDS_VOICE );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help devoice', chanserv::$help->CS_HELP_XCOMMANDS_VOICE );
		
		commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_MODE_1, true );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help mode', chanserv::$help->CS_HELP_MODE_ALL );
		
		commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_SYNC_1, true );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help sync', chanserv::$help->CS_HELP_SYNC_ALL );
		// voice and mode & sync
		
		commands::add_help( 'chanserv', 'cs_xcommands', 'help commands', chanserv::$help->CS_HELP_TYPEMASK_1, true );
		commands::add_help( 'chanserv', 'cs_xcommands', 'help typemask', chanserv::$help->CS_HELP_TYPEMASK_ALL );
		// typemask
		
		commands::add_command( 'chanserv', 'clear', 'cs_xcommands', 'clear_command' );
		// clear command
				
		commands::add_command( 'chanserv', 'kick', 'cs_xcommands', 'kick_command' );
		commands::add_command( 'chanserv', 'kickban', 'cs_xcommands', 'kickban_command' );
		commands::add_command( 'chanserv', 'ban', 'cs_xcommands', 'ban_command' );
		commands::add_command( 'chanserv', 'unban', 'cs_xcommands', 'unban_command' );
		// add the commands for kick/bans etc
		
		if ( ircd::$owner )
		{
			commands::add_command( 'chanserv', 'owner', 'cs_xcommands', 'owner_command' );
			commands::add_command( 'chanserv', 'deowner', 'cs_xcommands', 'deowner_command' );
		}
		// protect
		
		if ( ircd::$protect )
		{
			commands::add_command( 'chanserv', 'protect', 'cs_xcommands', 'protect_command' );
			commands::add_command( 'chanserv', 'deprotect', 'cs_xcommands', 'deprotect_command' );
		}
		// protect
		
		commands::add_command( 'chanserv', 'op', 'cs_xcommands', 'op_command' );
		commands::add_command( 'chanserv', 'deop', 'cs_xcommands', 'deop_command' );
		// op
		
		if ( ircd::$halfop )
		{
			commands::add_command( 'chanserv', 'halfop', 'cs_xcommands', 'halfop_command' );
			commands::add_command( 'chanserv', 'dehalfop', 'cs_xcommands', 'dehalfop_command' );
		}
		// halfop
		
		commands::add_command( 'chanserv', 'voice', 'cs_xcommands', 'voice_command' );
		commands::add_command( 'chanserv', 'devoice', 'cs_xcommands', 'devoice_command' );
		commands::add_command( 'chanserv', 'mode', 'cs_xcommands', 'mode_command' );
		commands::add_command( 'chanserv', 'sync', 'cs_xcommands', 'sync_command' );
		// and the rest, voice & mode & sync.
		
		$level_structure = array( 'array' => &chanserv::$levels, 'module' => __CLASS__, 'command' => array( 'help levels' ), 'type' => 'cslevels' );
		services::add_flag( $level_structure, 'i', chanserv::$help->CS_LEVELS_i, null, null, array( 'S', 'F' ) );
		services::add_flag( $level_structure, 'R', chanserv::$help->CS_LEVELS_R, null, null, array( 'S', 'F' ) );
		services::add_flag( $level_structure, 'r', chanserv::$help->CS_LEVELS_r, null, null, array( 'S', 'F' ) );
		// and finally add any flags
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_clear_chan( $input, $nick, $ircdata[0] );
		// call _clear_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_sync_chan( $input, $nick, $ircdata[0] );
		// call _sync_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_mode_chan( $input, $nick, $ircdata[0], core::get_data_after( $ircdata, 1 ) );
		// call _mode_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_owner_chan( $input, $nick, $ircdata[0], '+', $ircdata[1] );
		// call _owner_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_owner_chan( $input, $nick, $ircdata[0], '-', $ircdata[1] );
		// call _owner_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_protect_chan( $input, $nick, $ircdata[0], '+', $ircdata[1] );
		// call _protect_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_protect_chan( $input, $nick, $ircdata[0], '-', $ircdata[1] );
		// call _protect_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_op_chan( $input, $nick, $ircdata[0], '+', $ircdata[1] );
		// call _op_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_op_chan( $input, $nick, $ircdata[0], '-', $ircdata[1] );
		// call _op_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_halfop_chan( $input, $nick, $ircdata[0], '+', $ircdata[1] );
		// call _halfop_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_halfop_chan( $input, $nick, $ircdata[0], '-', $ircdata[1] );
		// call _halfop_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_voice_chan( $input, $nick, $ircdata[0], '+', $ircdata[1] );
		// call _voice_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_voice_chan( $input, $nick, $ircdata[0], '-', $ircdata[1] );
		// call _voice_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_kick_chan( $input, $nick, $ircdata[0], $ircdata[1], core::get_data_after( $ircdata, 2 ) );
		// call _kick_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_kickban_chan( $input, $nick, $ircdata[0], $ircdata[1], core::get_data_after( $ircdata, 2 ) );
		// call _kickban_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_ban_chan( $input, $nick, $ircdata[0], $ircdata[1] );
		// call _ban_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_unban_chan( $input, $nick, $ircdata[0], $ircdata[1] );
		// call _unban_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _check_channel (private)
	* 
	* @params
	* $nick - nick of who issues the command
	* $chan - check the chan entered is valid, and stuff
	* $help - should be the name of the command, in caps
	* &$return_data - a valid module::$return_data array
	*/
	static public function _check_channel( $nick, $chan, $help, &$return_data )
	{
		if ( $chan == '' || $chan[0] != '#' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => $help ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return false;
		}
		// make sure they've entered a channel
		
		if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->CHAN_UNREGISTERED;
			return false;
		}
		// make sure the channel exists.
		
		return $channel;
	}
	
	/*
	* _clear_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	*/
	static public function _clear_chan( $input, $nick, $chan )
	{
		$return_data = module::$return_data;
		if ( !self::_check_channel( $nick, $chan, 'CLEAR', $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'R', 'S', 'F' ) ) === false )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
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
		
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_CLEAR_CHAN, array( 'chan' => $chan ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return back
	}
	
	/*
	* _sync_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	*/
	static public function _sync_chan( $input, $nick, $chan )
	{
		$return_data = module::$return_data;
		if ( !$channel = self::_check_channel( $nick, $chan, 'SYNC', $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'q', 'f', 'S', 'F' ) ) === false )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// do they have access?
		
		cs_levels::on_create( core::$chans[$chan]['users'], $channel );
		// execute on_create, cause we just treat it as that
		// this is kinda a shortcut, but well worth it.
		
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_SYNC_CHAN, array( 'chan' => $chan ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return back
	}
	
	/*
	* _mode_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode_queue - Modes to set
	*/
	static public function _mode_chan( $input, $nick, $chan, $mode_queue )
	{
		$return_data = module::$return_data;
		if ( !self::_check_channel( $nick, $chan, 'MODE', $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'S', 'F' ) ) === false )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
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
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return back
	}
	
	/*
	* _owner_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode - +/-
	* $unick - The nickname to use
	*/
	static public function _owner_chan( $input, $nick, $chan, $mode, $unick )
	{
		$return_data = module::$return_data;
		if ( $mode == '+' ) $type = 'OWNER';
		else $type = 'DEOWNER';
		
		if ( !self::_check_channel( $nick, $chan, $type, $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'q', 'f', 'S', 'F' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// do they have access?
		
		if ( strpos( $unick, ':' ) !== false )
			mode::type_check( $chan, $unick, $mode.'q', core::$config->chanserv->nick );
		elseif ( $unick != '' )
			mode::set( core::$config->chanserv->nick, $chan, $mode.'q '.$unick );
		else
			mode::set( core::$config->chanserv->nick, $chan, $mode.'q '.$nick );
		// set modes
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return back
	}
	
	/*
	* _protect_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode - +/-
	* $unick - The nickname to use
	*/
	static public function _protect_chan( $input, $nick, $chan, $mode, $unick )
	{
		$return_data = module::$return_data;
		if ( $mode == '+' ) $type = 'PROTECT';
		else $type = 'DEPROTECT';
		
		if ( !self::_check_channel( $nick, $chan, $type, $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'a', 'q', 'f', 'S', 'F' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// do they have access?
		
		if ( strpos( $unick, ':' ) !== false )
			mode::type_check( $chan, $unick, $mode.'a', core::$config->chanserv->nick );
		elseif ( $unick != '' )
			mode::set( core::$config->chanserv->nick, $chan, $mode.'a '.$unick );
		else
			mode::set( core::$config->chanserv->nick, $chan, $mode.'a '.$nick );
		// set modes
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return back
	}
	
	/*
	* _op_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode - +/-
	* $unick - The nickname to use
	*/
	static public function _op_chan( $input, $nick, $chan, $mode, $unick )
	{
		$return_data = module::$return_data;
		if ( $mode == '+' ) $type = 'OP';
		else $type = 'DEOP';
		
		if ( !self::_check_channel( $nick, $chan, $type, $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'o', 'a', 'q', 'f', 'S', 'F' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// do they have access?
		
		if ( strpos( $unick, ':' ) !== false )
			mode::type_check( $chan, $unick, $mode.'o', core::$config->chanserv->nick );
		elseif ( $unick != '' )
			mode::set( core::$config->chanserv->nick, $chan, $mode.'o '.$unick );
		else
			mode::set( core::$config->chanserv->nick, $chan, $mode.'o '.$nick );
		// set modes
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return back
	}
	
	/*
	* _halfop_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode - +/-
	* $unick - The nickname to use
	*/
	static public function _halfop_chan( $input, $nick, $chan, $mode, $unick )
	{
		$return_data = module::$return_data;
		if ( $mode == '+' ) $type = 'HALFOP';
		else $type = 'DEHALFOP';
		
		if ( !self::_check_channel( $nick, $chan, $type, $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// do they have access?
		
		if ( strpos( $unick, ':' ) !== false )
			mode::type_check( $chan, $unick, $mode.'h', core::$config->chanserv->nick );
		elseif ( $unick != '' )
			mode::set( core::$config->chanserv->nick, $chan, $mode.'h '.$unick );
		else
			mode::set( core::$config->chanserv->nick, $chan, $mode.'h '.$nick );
		// set modes
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return back
	}
	
	/*
	* _voice_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $mode - +/-
	* $unick - The nickname to use
	*/
	static public function _voice_chan( $input, $nick, $chan, $mode, $unick )
	{
		$return_data = module::$return_data;
		if ( $mode == '+' ) $type = 'VOICE';
		else $type = 'DEVOICE';
		
		if ( !self::_check_channel( $nick, $chan, $type, $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'v', 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// do they have access?
		
		if ( strpos( $unick, ':' ) !== false )
			mode::type_check( $chan, $unick, $mode.'v', core::$config->chanserv->nick );
		elseif ( $unick != '' )
			mode::set( core::$config->chanserv->nick, $chan, $mode.'v '.$unick );
		else
			mode::set( core::$config->chanserv->nick, $chan, $mode.'v '.$nick );
		// set modes
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return back
	}
	
	/*
	* _kick_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $unick - The nickname to use
	*/
	static public function _kick_chan( $input, $nick, $chan, $who, $reason )
	{
		$return_data = module::$return_data;
		if ( !self::_check_channel( $nick, $chan, 'KICK', $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'r', 'S', 'F' ) ) && !chanserv::check_levels( $who, $channel->channel, array( 'S', 'F' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// do they have access?
		// you can't k/b anyone with either +S or +F, others can be k/bed though.	
		
		if ( $user = core::search_nick( $who ) )
		{
			ircd::kick( core::$config->chanserv->nick, $user['nick'], $chan, '('.$nick.') '.( $reason != '' ) ? $reason : 'No reason' );
			$return_data[CMD_SUCCESS] = true;
		}
		// kick them with the reason
		
		return $return_data;
		// return back
	}
	
	/*
	* _kickban_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $unick - The nickname to use
	*/
	static public function _kickban_chan( $input, $nick, $chan, $who, $reason )
	{
		$return_data = module::$return_data;
		if ( !self::_check_channel( $nick, $chan, 'KICKBAN', $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'r', 'S', 'F' ) ) && !chanserv::check_levels( $who, $channel->channel, array( 'S', 'F' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// do they have access?
		// you can't k/b anyone with either +S or +F, others can be k/bed though.
		
		if ( $user = core::search_nick( $who ) )
		{
			mode::set( core::$config->chanserv->nick, $chan, '+b *@'.$user['host'] );			
			ircd::kick( core::$config->chanserv->nick, $user['nick'], $chan, '('.$nick.') '.( $reason != '' ) ? $reason : 'No reason' );
			// kick them with the reason
			$return_data[CMD_SUCCESS] = true;
		}
		// we check if the user exists.
		
		return $return_data;
		// return back
	}
	
	/*
	* _ban_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $unick - The nickname to use
	*/
	static public function _ban_chan( $input, $nick, $chan, $who )
	{
		$return_data = module::$return_data;
		if ( !self::_check_channel( $nick, $chan, 'BAN', $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'r', 'S', 'F' ) ) && !chanserv::check_levels( $who, $channel->channel, array( 'S', 'F' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// do they have access?
		// you can't k/b anyone with either +S or +F, others can be k/bed though.
		
		if ( strpos( $who, '@' ) === false && $user = core::search_nick( $who ) )
			mode::set( core::$config->chanserv->nick, $chan, '+b *@'.$user['host'] );			
		else
			mode::set( core::$config->chanserv->nick, $chan, '+b '.$who );
		// +b
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return back
	}
	
	/*
	* _unban_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to use
	* $unick - The nickname to use
	*/
	static public function _unban_chan( $input, $nick, $chan, $who )
	{
		$return_data = module::$return_data;
		if ( !self::_check_channel( $nick, $chan, 'UNBAN', $return_data ) )
			return $return_data;
		// check if the channel exists and stuff
		
		if ( !chanserv::check_levels( $nick, $chan, array( 'r', 'S', 'F' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// do they have access?
		
		if ( strpos( $who, '@' ) === false && $user = core::search_nick( $who ) )
			mode::set( core::$config->chanserv->nick, $chan, '-b *@'.$user['host'] );			
		else
			mode::set( core::$config->chanserv->nick, $chan, '-b '.$who );
		// -b
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return back
	}
}

// EOF;
