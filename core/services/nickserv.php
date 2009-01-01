<?php

/*
* Acora IRC Services
* core/services/nickserv.php: NickServ initiation class
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class nickserv implements service
{
	
	static public $help;
	// help

	/*
	* __construct
	* 
	* @params
	* void
	*/
	public function __construct()
	{
		require( BASEPATH.'/lang/'.core::$config->server->lang.'/nickserv.php' );
		self::$help = &$help;
		
		if ( isset( core::$config->nickserv ) )
		{
			ircd::introduce_client( core::$config->nickserv->nick, core::$config->nickserv->user, core::$config->nickserv->host, core::$config->nickserv->real );
		}
		// connect the bot
		
		foreach ( core::$config->nickserv_modules as $id => $module )
		{
			modules::load_module( 'ns_'.$module, $module.'.ns.php' );
		}
		// load the nickserv modules
		
		timer::add( array( 'nickserv', 'check_expire', array() ), 300, 0 );
		// set a timer!
	}
	
	/*
	* main (event_hook)
	* 
	* @params
	* $ircdata - ..
	*/
	public function main( &$ircdata, $startup = false )
	{			
		foreach ( modules::$list as $module => $data )
		{
			if ( $data['type'] == 'nickserv' )
			{
				modules::$list[$module]['class']->main( &$ircdata, $startup );
				// loop through the modules for nickserv.
			}
		}
		
		if ( ircd::on_msg( &$ircdata, core::$config->nickserv->nick ) )
		{
			$nick = core::get_nick( &$ircdata, 0 );
			$command = substr( core::get_data_after( &$ircdata, 3 ), 1 );
			// convert to lower case because all the tingy wags are in lowercase
			
			self::get_command( $nick, $command );
		}
		// this is what we use to handle command listens
		// should be quite epic.
		
		if ( ircd::on_mode( &$ircdata ) && core::$config->server->help_chan )
		{
			$chan = core::get_chan( &$ircdata, 2 );
			
			if ( $chan == strtolower( core::$config->server->help_chan ) )
			{
				$re_data = $ircdata;
				unset( $re_data[0], $re_data[1], $re_data[2], $re_data[3] );
				
				foreach ( $re_data as $nick )
				{
					// we're going to guess that it's a nick here, lol.
					if ( strstr( core::$chans[$chan]['users'][$nick], 'o' ) )
						ircd::umode( core::$config->nickserv->nick, $nick, '+h' );
						// user has +o, lets give em +h!
				}
			}
			// only deal with it if we're talking about the help chan
		}
		// here we deal with giving umode +h to ops :D
		
		if ( ircd::on_chan_create( &$ircdata ) && core::$config->server->help_chan )
		{
			$chans = explode( ',', $ircdata[2] );
			// chans
			
			foreach ( $chans as $chan )
			{
				if ( $chan == strtolower( core::$config->server->help_chan ) )
				{
					// the chan
					$nusers_str = implode( ' ', $ircdata );
					$nusers_str = explode( ':', $nusers_str );
					// right here we need to find out where the thing is
					$nusers = ircd::parse_users( $chan, $nusers_str, 1 );
					
					foreach ( $nusers as $nick => $modes )
					{
						if ( strstr( $modes, 'o' ) )
							ircd::umode( core::$config->nickserv->nick, $nick, '+h' );
							// user has +o, lets give em +h!
					}
				}
				// only deal with it if we're talking about the help chan
			}
		}
		// and on_chan_create
	}
	
	/*
	* check_expire (private)
	* 
	* @params
	* void
	*/
	static public function check_expire()
	{
		if ( core::$config->nickserv->expire == 0 )
			return false;
		// skip nicknames if config is set to no expire.
		
		$expiry_time = core::$config->nickserv->expire * 86400;
		$check_time = core::$network_time - $expiry_time;
		// set up our times.
		
		$nick_q = database::select( 'users', array( 'id', 'display', 'last_timestamp' ), "`last_timestamp` != '0' AND `last_timestamp` < '".$check_time."'" );
		
		if ( database::num_rows( $nick_q ) == 0 )
			return false;
		// no expiring nicknames
		
		while ( $nick = database::fetch( $nick_q ) )
		{
			// Mikeh gets most of the credit for helping
			// me code this function
			
			database::delete( 'users', "`display` = '".$nick->display."'" );
			database::delete( 'users_flags', "`nickname` = '".$user->display."'" );
			// delete the users record
				
			$chan_q = database::select( 'chans', array( 'channel', 'founder' ), "`founder` = '".$nick->id."'" );
				
			if ( database::num_rows( $chan_q ) != 0 )
			{
				while ( $channel = database::fetch( $chan_q ) )
				{
					database::delete( 'chans', "`channel` = '".$channel->channel."'" );
					database::delete( 'chans_levels', "`channel` = '".$channel->channel."'" );
						
					if ( isset( core::$chans[$channel->channel] ) )
					{
						unset( core::$chans[$channel->channel]['users'][core::$config->chanserv->nick] );
						// unsetunsetunset
						ircd::mode( core::$config->chanserv->nick, $channel->channel, '-'.ircd::$reg_modes['chan']);
						ircd::part_chan( core::$config->chanserv->nick, $channel->channel );
						// now lets leave the channel if we're in it
							
						core::alog( core::$config->chanserv->nick.': '.$channel->channel.' dropped because founder expired' );
					}
				}
			}
			// now we need to check if they own any channels, if they do..
			// unregister it i guess.
				
			database::delete( 'chans_levels', "`target` = '".$nick->display."'" );
			// also delete this users channel access.
				
			core::alog( core::$config->nickserv->nick.': '.$nick->display.' has expired. Last used on '.date( 'F j, Y, g:i a', $nick->last_timestamp ) );
			// logchan it
				
			if ( isset( core::$nicks[$nick->display] ) )
				ircd::umode( core::$config->nickserv->nick, $nick->display, '-'.ircd::$reg_modes['nick'] );
			// if the nick is being used unregister it, even though it shouldn't be, just to be safe.
		}
		// loop through all expiring nicks.
	}
	
	/*
	* check_flags (private)
	* 
	* @params
	* $nickname - The nickname to check.
	* $flags - an array of flags to check for.
	*/
	static public function check_flags( $nick, $flags )
	{
		$nick_flags_q = database::select( 'users_flags', array( 'id', 'nickname', 'flags' ), "`nickname` = '".database::quote( $nick )."'" );
		$nick_flags = database::fetch( $nick_flags_q );
		// get our flags records
		
		foreach ( $flags as $flag )
		{
			if ( strpos( $nick_flags->flags, $flag ) !== false )
				return true;
			// hurrah, we've found a match!
		}
		// loop through the flags, if we find a match, return true
		
		return false;
	}
	
	/*
	* get_flags (private)
	* 
	* @params
	* $nickname - The nickname to check.
	* $flag - a flag value to grab, eg. modelock (m)
	*/
	static public function get_flags( $nick, $flag )
	{
		if ( $flag == 'e' )
			$param_field = 'email';
		elseif ( $flag == 'u' )
			$param_field = 'url';
		elseif ( $flag == 'm' )
			$param_field = 'msn';
		else
			return false;
		// translate. some craq.
		
		$nick_flags_q = database::select( 'users_flags', array( 'id', 'nickname', 'flags', $param_field ), "`nickname` = '".database::quote( $nick )."'" );
		$nick_flags = database::fetch( $nick_flags_q );
		// get our flags records
		
		return $nick_flags->$param_field;
	}
	
	/*
	* add_help_prefix
	* 
	* @params
	* $command - The command to add a prefix for.
	* $module - The name of the module.
	* $help - The prefix to add.
	*/
	static public function add_help_fix( $module, $what, $command, &$help )
	{
		commands::add_help_fix( 'nickserv', $module, $what, $command, &$help );
	}
	
	/*
	* add_help
	* 
	* @params
	* $command - The command to hook the array to.
	* $module - The name of the module.
	* $help - The array to hook.
	*/
	static public function add_help( $module, $command, &$help, $oper_help = false )
	{
		commands::add_help( 'nickserv', $module, $command, &$help, $oper_help );
	}
	
	/*
	* get_help
	* 
	* @params
	* $nick - Who to send the help too?
	* $command - The command to get the help for.
	*/
	static public function get_help( &$nick, &$command )
	{
		commands::get_help( 'nickserv', &$nick, &$command );
	}
	
	/*
	* add_command
	* 
	* @params
	* $command - The command to hook to
	* $class - The class the callback is in
	* $function - The function name of the callback
	*/
	static public function add_command( $command, $class, $function )
	{
		commands::add_command( 'nickserv', $command, $class, $function );
	}
	
	/*
	* get_command
	* 
	* @params
	* $nick - The nick requesting the command
	* $command - The command to hook to
	*/
	static public function get_command( &$nick, &$command )
	{
		commands::get_command( 'nickserv', &$nick, &$command );
	}
}

// EOF;