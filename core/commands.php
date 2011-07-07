<?php

/*
* Acora IRC Services
* core/commands.php: Command and help system handler.
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

class commands
{
	
	static public $unknown_cmds = 0;
	static public $commands = array();
	static public $helpv = array();
	static public $unordered_help = array();
	static public $prefix = array();
	static public $suffix = array();
	// setup our static variables etc.
	
	public function __construct() {}
	// __construct, makes everyone happy.
		
	/*
	* ctcp
	*
	* @params
	* $ircdata - ..
	*/
	static public function ctcp( $nick, $who, $msg )
	{
		$msg = explode( ' ', substr( $msg, 1 ) );
		$part_one = preg_replace( '/[^a-zA-Z0-9\s]/', '', $msg[0] );
		
		if ( $part_one == 'VERSION' )
		{
			ircd::notice( $who, $nick, 'VERSION acora-'.core::$version.' '.ircd::$ircd.' booted: '.date( 'F j, Y, g:i a', core::$network_time ).'' );
			ircd::notice( $who, $nick, 'VERSION (C) 2009 GamerGrid #acora @ irc.ircnode.org' );
		}
		// only reply on version.
		elseif ( $part_one == 'TIME' )
		{
			ircd::notice( $who, $nick, 'TIME '.date( 'D M j G:i:s Y', core::$network_time ).'' );
		}
		// only reply on time.
		elseif ( $part_one == 'PING' )
		{
			ircd::notice( $who, $nick, 'PING 0secs' );
		}
		// only reply on ping.
		elseif ( $part_one == 'FINGER' )
		{
			ircd::notice( $who, $nick, 'FINGER Get your finger out of my socket!' );
		}
		// only reply on finger, teehee :D
	}
	
	/*
	* motd
	*
	* @params
	* $ircdata - ..
	*/
	static public function motd( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'MOTD' )
		{
			$nick = core::get_nick( $ircdata, 0 );
			
			if ( !file_exists( CONFPATH.'services.motd' ) )
			{
				ircd::push( core::$config->server->name, $nick, 'MOTD File is missing' );
				return false;
			}
				
			$lines = file( CONFPATH.'services.motd' );
			
			foreach( $lines as $num => $line )
				$lines[$num] = rtrim( $line );
			// strip the crap out of it
			
			ircd::push( core::$config->server->name, 375, $nick, array( str_replace( '{server}', core::$config->server->name, ircd::$motd_start ) ) );
			// send the start of the motd.
			
			foreach  ( $lines as $num => $line )
			{
				if ( strpos( $line, '{version}' ) !== false )
					$line = str_replace( '{version}', core::$version, $line );
				if ( strpos( $line, '{uptime}' ) !== false )
					$line = str_replace( '{uptime}', core::format_time( core::$uptime ), $line );
				// replaceable variables here.
				
				ircd::push( core::$config->server->name, 372, $nick, array( '-', $line ) );
			}
			// loop through, throwing the line at the client :D
			
			ircd::push( core::$config->server->name, 376, $nick, array( ircd::$motd_end ) );
			// send the end of the motd.
			return true;
		}
		// only triggered if someone asks us for a MOTD.
		
		return false;
	}
	
	/*
	* on_fantasy_cmd
	*
	* @params
	* $from, $chan, $msg < obvious
	* $command - The command to listen for, !op, !deop
	* $nick - The bot which listens for the command.
	*/
	static public function on_fantasy_cmd( $from, $chan, $msg, $command, $nick )
	{
		$prefix = core::$config->chanserv->fantasy_prefix;
		$command = strtolower( $command );
		$commands = explode( ' ', $msg );
		$realdata = strtolower( $commands[0] );
		
		if ( services::check_mask_ignore( $nick ) === true )
			return false;
		// this is basically to check if we have
		// an ignored user, via their hostmask, or their nickname.
		
		if ( core::$nicks[$from]['ignore'] )
			return false;
		// are they ignored w/ the flood system?
		
		if ( !isset( core::$chans[$chan]['users'][$nick] ) )
			return false;
		// the user needs to be in the channel
		
		if ( $prefix.$command == $realdata )
			return true;
		// return true on any command match.
	}
	
	/*
	* add_help_prefix
	* 
	* @params
	* $hook - What to hook to, chanserv etc.
	* $module - The name of the module.
	* $command - The command to add a prefix for.
	* $help - The prefix to add.
	*/
	static public function add_help_fix( $hook, $module, $what, $command, $help )
	{
		$command = strtolower( $command );
		// make it lowercase
		
		if ( substr( $command, 0, 4 ) != 'help' )
		{
			core::alog( 'add_help_fix(): command does not start with "help"', 'BASIC' );
			return false;
		}
		// trigger an error
		
		if ( is_array( $help ) )
		{
			foreach ( $help as $index => $line )
			{
				$line = str_replace( '{chanserv}', core::$config->chanserv->nick, $line );
				$line = str_replace( '{nickserv}', core::$config->nickserv->nick, $line );
				$line = str_replace( '{operserv}', core::$config->operserv->nick, $line );
				// replace *Serv with it's actual name
				
				$meta_data = array(
					'info' => ( $line == ' ' ) ? '' : $line,
					'module' => $module,
				);
			
				if ( $what == 'prefix' )
					self::$prefix[$hook][$command][] = serialize( $meta_data );
				// add a prefix
				if ( $what == 'suffix' )
					self::$suffix[$hook][$command][] = serialize( $meta_data );
				// add a suffix
			}
		}
		else
		{
			$help = str_replace( '{chanserv}', core::$config->chanserv->nick, $help );
			$help = str_replace( '{nickserv}', core::$config->nickserv->nick, $help );
			$help = str_replace( '{operserv}', core::$config->operserv->nick, $help );
			// replace *Serv with it's actual name
		
			$meta_data = array(
				'info' => ( $help == ' ' ) ? '' : $help,
				'module' => $module,
			);
		
			if ( $what == 'prefix' )
				self::$prefix[$hook][$command][] = serialize( $meta_data );
			// add a prefix
			if ( $what == 'suffix' )
				self::$suffix[$hook][$command][] = serialize( $meta_data );
			// add a suffix
		}
		// basically what this does is adds those prefixes
		// like, the block of text before and after when you
		// do /chanserv help, and in the middle the commands.
	}
	
	/*
	* add_help
	* 
	* @params
	* $hook - What to hook to, chanserv etc.
	* $module - The name of the module.
	* $command - The command to hook the array to.
	* $help - The array to hook.
	* $privs - oper privs
	*/
	static public function add_help( $hook, $module, $command, $help, $reorder, $privs )
	{
		$command = strtolower( $command );
		// make it lowercase
		
		if ( substr( $command, 0, 4 ) != 'help' )
		{
			core::alog( 'add_help(): command does not start with "help"', 'BASIC' );
			return false;
		}
		// trigger an error
	
		if ( is_array( $help ) )
		{
			foreach ( $help as $line )
			{
				$line = str_replace( '{chanserv}', core::$config->chanserv->nick, $line );
				$line = str_replace( '{nickserv}', core::$config->nickserv->nick, $line );
				$line = str_replace( '{operserv}', core::$config->operserv->nick, $line );
				// replace *Serv with it's actual name
			
				$meta_data = array(
					'info' => ( $line == ' ' ) ? '' : $line,
					'module' => $module,
					'privs' => $privs,
					'ordered' => $reorder,
				);
			
				self::$helpv[$hook][$command][] = serialize( $meta_data );
			}
		}
		else
		{
			$help = str_replace( '{chanserv]', core::$config->chanserv->nick, $help );
			$help = str_replace( '{nickserv}', core::$config->nickserv->nick, $help );
			$help = str_replace( '{operserv}', core::$config->operserv->nick, $help );
			// replace *Serv with it's actual name
		
			$meta_data = array(
				'info' => ( $help == ' ' ) ? '' : $help,
				'module' => $module,
				'privs' => $privs,
				'ordered' => $reorder,
			);
		
			self::$helpv[$hook][$command][] = serialize( $meta_data );
		}
		// add the help
	}
	
	/*
	* get_help
	* 
	* @params
	* $hook - What to hook to, chanserv etc.
	* $nick - Who to send the help too?
	* $command - The command to get the help for.
	*/
	static public function get_help( $hook, $nick, $command )
	{
		// not too sure how to deal with this, probably will
		// let this function do the looping and just send it
		// straight to the client?
		
		if ( services::check_mask_ignore( $nick ) )
			return false;
		// this is basically to check if we have
		// an ignored user, via their hostmask, or their nickname.
		
		if ( $hook == 'chanserv' ) $bot = core::$config->chanserv->nick;
		if ( $hook == 'nickserv' ) $bot = core::$config->nickserv->nick;
		if ( $hook == 'operserv' ) $bot = core::$config->operserv->nick;
		// what we sending from?
		
		$commands = explode( ' ', $command );
		if ( strtolower( $commands[0] ) != 'help' || trim( $command ) == '' )
			return false;
		// is it actually a help command? >.<
		
		$count = ( ( isset( self::$prefix[$hook][$command] ) ) ? count( self::$prefix[$hook][$command] ) : 0 ) + ( ( isset( self::$helpv[$hook][$command] ) ) ? count ( self::$helpv[$hook][$command] ) : 0 ) + ( ( isset( self::$suffix[$hook][$command] ) ) ? count( self::$suffix[$hook][$command] ) : 0 );
		
		if ( !isset( self::$helpv[$hook][$command] ) || $count == 0 )
		{
			services::communicate( $bot, $nick, core::$help->CORE_UNKNOWN_HELP, array( 'command' => $command ) );
			return false;
		}
		// does the array even exist?
		
		if ( isset( self::$prefix[$hook][$command] ) )
		{
			foreach ( self::$prefix[$hook][$command] as $line => $meta_data )
			{
				$meta = unserialize( $meta_data );
				services::communicate( $bot, $nick, $meta['info'] );
			}
		}
		// is there a prefix?
		
		foreach ( self::$helpv[$hook][$command] as $line => $meta_data )
		{
			$meta = unserialize( $meta_data );
			
			if ( $meta['ordered'] )
			{
				$reordered[] = $meta['info'];
				$reordered_privs[] = $meta['privs'];
				continue;
			}
			// determine whether we need to reorder, if we do mark that we save the rest for later.
			
			if ( $meta['privs'] != '' && services::oper_privs( $nick, $meta['privs'] ) || $meta['privs'] == '' )
				services::communicate( $bot, $nick, $meta['info'] );
		}
		// display the main stuff
		
		sort( $reordered );
		$privs = '';
		foreach ( $reordered as $line => $info )
		{
			$privs = $reordered_privs[$line];
			if ( $privs != '' && services::oper_privs( $nick, $privs ) || $privs == '' )
				services::communicate( $bot, $nick, $info );
		}
		// seems the stuff is reordered
		
		if ( isset( self::$suffix[$hook][$command] ) )
		{
			foreach ( self::$suffix[$hook][$command] as $line => $meta_data )
			{
				$meta = unserialize( $meta_data );
				
				services::communicate( $bot, $nick, $meta['info'] );
			}
		}
		// is there a suffix?
	}
	
	/*
	* add_command
	* 
	* @params
	* $hook - What to hook to, chanserv etc.
	* $command - The command to hook to
	* $class - The class the callback is in
	* $function - The function name of the callback
	*/
	static public function add_command( $hook, $command, $class, $function )
	{
		$command = strtolower( $command );
		// make it lowercase
		
		if ( substr( $command, 0, 4 ) == 'help' )
		{
			core::alog( 'add_command(): command cant start with "help"', 'BASIC' );
			return false;
		}
		// trigger an error if they're trying to make help commands with this..
		
		self::$commands[$hook][$command] = array(
			'command' => $command,
			'class' => $class,
			'function' => $function,
		);
		// add it into a global array
	}
	
	/*
	* get_command
	* 
	* @params
	* $hook - What to hook to, chanserv etc.
	* $nick - The nick requesting the command
	* $command - The command to hook to
	*/
	static public function get_command( $hook, $nick, $command )
	{
		// this works better than i imagined
		
		if ( services::check_mask_ignore( $nick ) )
			return false;
		// this is basically to check if we have
		// an ignored user, via their hostmask, or their nickname.
		
		if ( $hook == 'chanserv' ) $bot = core::$config->chanserv->nick;
		if ( $hook == 'nickserv' ) $bot = core::$config->nickserv->nick;
		if ( $hook == 'operserv' ) $bot = core::$config->operserv->nick;
		// what we sending from?
	
		$command = trim( $command );
		$commands = explode( ' ', $command );
		$num_cmds = count( $commands );
		$commands_r = $commands;
		// some vars..
		
		if ( strtolower( $commands[0] ) == 'help' || $command == '' || substr( $commands[0], 0, 1 ) == '' )
			return false;
		// its a command we don't need to deal with, ignore it
		
		for ( $i = $num_cmds; $i > -1; $i-- )
		{
			unset( $commands[$i] );
			$new_params[] = trim( $commands_r[$i] );
			$new = strtolower( implode( ' ', $commands ) );
			// i really cba explaining this..
			
			if ( isset( self::$commands[$hook][$new] ) )
				break;
		}
		// just a loop to.. err can't remember what this does..
		// something interesting though
		
		$new_params = array_reverse( $new_params );
		// house keeping
		
		foreach ( $new_params as $ii => $pp )
			if ( $pp == '' ) unset( $new_params[$ii] );
		// more housekeeping
		
		if ( !isset( self::$commands[$hook][$new] ) )
		{
			self::$unknown_cmds++;
			services::communicate( $bot, $nick, core::$help->CORE_UNKNOWN_COMMAND, array( 'command' => $commands_r[0] ) );
			return false;
		}
		// command don't exist, at all..
		
		$class = strtolower( self::$commands[$hook][$new]['class'] );
		$function = strtolower( self::$commands[$hook][$new]['function'] );
		// get the function stuff.
		
		if ( !is_callable( array( $class, $function ), true ) || !method_exists( $class, $function ) )
		{
			core::alog( $class.'::'.$function.'() isn\'t callable, command rejected.', 'BASIC' );
			return false;
		}
		// reject the command attempt. output an error
		
		//modules::$list[$class]['class']->$function( $nick, $new_params );
		call_user_func_array( array( $class, $function ), array( $nick, $new_params ) );
		// it does! execute the callback
	}
}

// EOF;