<?php

/*
* Acora IRC Services
* src/services/chanserv.php: ChanServ initiation class
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

class chanserv extends service
{
	
	const SERV_VERSION = '0.1.2';
	const SERV_AUTHOR = 'Acora';
	// service info
	
	static public $nick;
	static public $user;
	static public $real;
	static public $host;
	// user vars
	
	static public $flags = array();
	static public $levels = array();
	static public $help;
	// help
	
	static public $chans = array();
	static public $chan_q = array();
	// store the last queries in an internal array, cause i've
	// noticed the same query is being called like 5 times cause the data
	// is used in 5 different places.

	/*
	* __construct
	* 
	* @params
	* void
	*/
	public function __construct()
	{
		modules::init_service( 'chanserv', self::SERV_VERSION, self::SERV_AUTHOR );
		// these are standard in service constructors
	
		require( BASEPATH.'/lang/'.core::$config->server->lang.'/chanserv.php' );
		self::$help = $help;
		// load the help file
		
		if ( isset( core::$config->chanserv ) )
		{
			self::$nick = core::$config->chanserv->nick = ( core::$config->chanserv->nick != '' ) ? core::$config->chanserv->nick : 'ChanServ';
			self::$user = core::$config->chanserv->user = ( core::$config->chanserv->user != '' ) ? core::$config->chanserv->user : 'chanserv';
			self::$real = core::$config->chanserv->real = ( core::$config->chanserv->real != '' ) ? core::$config->chanserv->real : 'Channel Services';
			self::$host = core::$config->chanserv->host = ( core::$config->chanserv->host != '' ) ? core::$config->chanserv->host : core::$config->conn->server;
			// check if nickname and stuff is specified, if not use defaults
		}
		// check if chanserv is enabled
		
		ircd::introduce_client( core::$config->chanserv->nick, core::$config->chanserv->user, core::$config->chanserv->host, core::$config->chanserv->real );
		// connect the bot
		
		foreach ( core::$config->chanserv_modules as $id => $module )
			modules::load_module( 'cs_'.$module, $module.'.cs.php' );
		// load the chanserv modules
		
		timer::add( array( 'chanserv', 'check_expire', array() ), 300, 0 );
		// set a timer!
	}
	
	/*
	* on_rehash (event)
	* 
	* @params
	* void
	*/
	static public function on_rehash()
	{
		if ( isset( core::$config->chanserv ) )
		{
			core::$config->chanserv->nick = ( core::$config->chanserv->nick != '' ) ? core::$config->chanserv->nick : 'ChanServ';
			core::$config->chanserv->user = ( core::$config->chanserv->user != '' ) ? core::$config->chanserv->user : 'chanserv';
			core::$config->chanserv->real = ( core::$config->chanserv->real != '' ) ? core::$config->chanserv->real : 'Channel Services';
			core::$config->chanserv->host = ( core::$config->chanserv->host != '' ) ? core::$config->chanserv->host : core::$config->conn->server;
			// check if nickname and stuff is specified, if not use defaults
			
			if ( self::$nick != core::$config->chanserv->nick || self::$user != core::$config->chanserv->user || self::$real != core::$config->chanserv->real || self::$host != core::$config->chanserv->host )
			{
				ircd::remove_client( self::$nick, 'Rehashing' );
				ircd::introduce_client( core::$config->chanserv->nick, core::$config->chanserv->user, core::$config->chanserv->host, core::$config->chanserv->real );
				// reintroduce client
				foreach ( self::$chans as $chan => $channel )
					self::_join_channel( $channel );
				// rejoin chans
			}
			// check for changes and reintroduce the client
			
			self::$nick = core::$config->chanserv->nick;
			self::$user = core::$config->chanserv->user;
			self::$real = core::$config->chanserv->real;
			self::$host = core::$config->chanserv->host;
		}
		// check if chanserv is enabled
	}
	
	/*
	* on_msg (event_hook)
	*/
	static public function on_msg( $nick, $target, $msg )
	{
		if ( $target != core::$config->chanserv->nick )
			return false;
		
		$command = substr( $msg, 1 );
		// convert to lower case because all the tingy wags are in lowercase
		
		commands::get_command( 'chanserv', $nick, $command );
	}
	
	/*
	* part_chan_callback (timer)
	* 
	* @params
	* void
	*/
	public function part_chan_callback( $chan )
	{
		if ( count( core::$chans[$chan]['users'] ) == 1 && isset( core::$chans[$chan]['users'][core::$config->chanserv->nick] ) )
		{
			unset( self::$chans[$channel->channel] );
			ircd::part_chan( core::$config->chanserv->nick, $chan );
		}
		// if we're the only person in the channel, leave it.
	}
	
	/*
	* on_chan_create (event hook)
	*/
	static public function on_chan_create( $chan )
	{
		self::$chan_q[$chan] = services::chan_exists( $chan, array( 'id', 'channel', 'timestamp', 'last_timestamp', 'topic', 'topic_setter', 'suspended', 'suspend_reason' ) );
				
		if ( self::$chan_q[$chan] )
			self::_join_channel( self::$chan_q[$chan] );
		// join the channel
	}
	
	/*
	* on_part (event hook)
	*/
	static public function on_part( $nick, $chan )
	{
		if ( count( core::$chans[$chan]['users'] ) == 1 && isset( core::$chans[$chan]['users'][core::$config->chanserv->nick] ) )
		{
			timer::add( array( 'chanserv', 'part_chan_callback', array( $chan ) ), 1, 1 );
			// instead of leaving straight away we add it to a timer, to be checked and
			// left in the next loop, incase someone has joined.
		}
		// we're the only person in the channel..
	}
	
	/*
	* on_join (event hook)
	*/
	static public function on_join( $nick, $chan )
	{
		self::$chan_q[$chan] = services::chan_exists( $chan, array( 'id', 'channel', 'timestamp', 'last_timestamp', 'topic', 'topic_setter', 'suspended', 'suspend_reason' ) );
			
		if ( self::$chan_q[$chan] !== false )
		{
			database::update( 'chans', array( 'last_timestamp' => core::$network_time ), array( 'channel', '=', $chan ) );
			// lets update the last used timestamp
		}
		// is the channel registered?
	}
	
	/*
	* on_quit (event hook)
	*/
	static public function on_quit( $nick )
	{
		while ( list( $chan, $data ) = each( core::$chans ) )
		{
			if ( count( core::$chans[$chan]['users'] ) == 1 && isset( core::$chans[$chan]['users'][core::$config->chanserv->nick] ) )
			{
				unset( self::$chans[$channel->channel] );
				ircd::part_chan( core::$config->chanserv->nick, $chan );
				// leave the channel.
			}
			// ok now we check whos left in the channel, if its only us lets leave
		}
		// are they in any channels?
		reset( core::$chans );
	}
	
	/*
	* on_mode (event hook)
	*/
	static public function on_mode( $nick, $chan, $mode_queue )
	{
		$a_mode = strpos( $mode_queue, 'a' );
		$o_mode = strpos( $mode_queue, 'o' );
		$cs_uid = array_search( core::$config->chanserv->nick, core::$uids );
		// bleh.
		
		if ( ( strpos( $mode_queue, core::$config->chanserv->nick ) || strpos( $mode_queue, $cs_uid ) ) && ( $a_mode !== false || $o_mode !== false ) )
		{
			if ( ircd::$protect )
				mode::set( core::$config->chanserv->nick, $chan, '+ao '.core::$config->chanserv->nick.' '.core::$config->chanserv->nick, true );
				// +ao its self.
			else
				mode::set( core::$config->chanserv->nick, $chan, '+o '.core::$config->chanserv->nick, true );
				// +o its self.
		}
		// we're being deopped! WHAT THE FUCK! :D
	}
	
	/*
	* on_kick (event hook)
	*/
	static public function on_kick( $nick, $chan, $who )
	{
		if ( $who == core::$config->server->name || str_replace( ':', '', $ircdata[0] ) == array_search( core::$config->chanserv->nick, core::$uids ) )
		{
			ircd::join_chan( core::$config->chanserv->nick, $chan );
			// join the chan.
			core::$chans[$chan]['users'][core::$config->chanserv->nick] = '';
			// add chanserv to the users array, housekeeping it :D
			
			if ( ircd::$protect )
				mode::set( core::$config->chanserv->nick, $chan, '+ao '.core::$config->chanserv->nick.' '.core::$config->chanserv->nick, true );
				// +ao its self.
			else
				mode::set( core::$config->chanserv->nick, $chan, '+o '.core::$config->chanserv->nick, true );
				// +o its self.
		}
		// what the fuck is this tool doing.. kicking us!!?! we SHOULD kick their ass
		// but we're not gonna x]
		
		if ( count( core::$chans[$chan]['users'] ) == 1 && isset( core::$chans[$chan]['users'][core::$config->chanserv->nick] ) )
		{
			unset( self::$chans[$channel->channel] );
			ircd::part_chan( core::$config->chanserv->nick, $chan );
			// leave the channel.
		}
		// we're the only person in the channel.. hopefully, lets leave x]
	}
	
	/*
	* check_expire (private)
	* 
	* @params
	* void
	*/
	static public function check_expire()
	{
		if ( core::$config->chanserv->expire == 0 )
			return false;
		// skip channels if config is set to no expire.
		
		$expiry_time = core::$config->chanserv->expire * 86400;
		$check_time = core::$network_time - $expiry_time;
		// set up our times
		
		$channel_q = database::select( 'chans', array( 'channel', 'last_timestamp' ), array( 'last_timestamp', '<', $check_time ) );
		
		if ( database::num_rows( $channel_q ) == 0 )
			return false;
		// no registered channels
		
		while ( $channel = database::fetch( $channel_q ) )
		{
			database::delete( 'chans', array( 'channel', '=', $channel->channel ) );
			database::delete( 'chans_levels', array( 'channel', '=', $channel->channel ) );
				
			core::alog( core::$config->chanserv->nick.': '.$channel->channel.' has expired. Last used on '.date( 'F j, Y, g:i a', $channel->last_timestamp ) );
			// logchan it
				
			if ( isset( core::$chans[$channel->channel] ) )
			{
				unset( self::$chans[$channel->channel] );
				ircd::part_chan( core::$config->chanserv->nick, $channel->channel );
				// now lets leave the channel if we're in it
			}
			// unset some modes, leave the channel if its in use.. i know this shouldn't
			// be even thought about.. but maybe somebody idled in it and never even did
			// anything for the whole expiry period? :P
		}
		// channel is old i'm afraid, expire it
	}
	
	/*
	* _join_channel (private)
	* 
	* @params
	* $chan - The channel chanserv is to join
	*/
	static public function _join_channel( $channel )
	{
		self::$chans[$channel->channel] = $channel;
		database::update( 'chans', array( 'last_timestamp' => core::$network_time ), array( 'channel', '=', $channel->channel ) );
		// lets update the last used timestamp
		
		ircd::set_registered_mode( core::$config->chanserv->nick, $channel->channel );
		// just set that for the crack.
	
		if ( self::check_flags( $channel->channel, array( 'G' ) ) && $channel->suspended == 0 && isset( modules::$list['cs_fantasy'] ) && !isset( core::$chans[$channel->channel]['users'][core::$config->chanserv->nick] ) )
		{
			ircd::join_chan( core::$config->chanserv->nick, $channel->channel );
			// join the chan.
			
			if ( ircd::$protect )
				mode::set( core::$config->chanserv->nick, $channel->channel, '+ao '.core::$config->chanserv->nick.' '.core::$config->chanserv->nick, true );
				// +ao its self.
			else
				mode::set( core::$config->chanserv->nick, $channel->channel, '+o '.core::$config->chanserv->nick, true );
				// +o its self.
		}
		// check if guard is on
		
		$modelock = self::get_flags( $channel->channel, 'm' );
		// store some flag values in variables.
		
		if ( $modelock != null && $channel->suspended == 0 )
		{
			mode::set( core::$config->chanserv->nick, $channel->channel, $modelock );
			
			// Going to have to do some fuffing around here, basically if the channel
			// in question is mlocked +i, and somebody has joined it, while its empty
			// +i will be set after they have joined the channel, so here we're gonna
			// have to kick them out, same applies for +O and +k
			$mode_array = mode::sort_modes( $modelock );
			
			if ( strstr( $mode_array['plus'], 'i' ) || strstr( $mode_array['plus'], 'k' ) )
			{
				foreach ( core::$chans[$channel->channel]['users'] as $nick => $modes )
				{
					if ( count( core::$chans[$channel->channel]['users'] ) == 2 && isset( core::$chans[$channel->channel]['users'][core::$config->chanserv->nick] ) )
					{
						if ( self::check_levels( $nick, $channel->channel, array( 'k', 'v', 'h', 'o', 'a', 'q', 'S', 'F' ), true, false ) === false )
						{
							if ( strstr( $mode_array['plus'], 'i' ) && $nick != core::$config->chanserv->nick )
							{
								ircd::kick( core::$config->chanserv->nick, $nick, $channel->channel, 'Invite only channel' );
								timer::add( array( 'chanserv', 'part_chan_callback', array( $channel->channel ) ), 1, 1 );
							}
							if ( strstr( $mode_array['plus'], 'k' ) && $nick != core::$config->chanserv->nick )
							{
								ircd::kick( core::$config->chanserv->nick, $nick, $channel->channel, 'Passworded channel' );
								timer::add( array( 'chanserv', 'part_chan_callback', array( $channel->channel ) ), 1, 1 );
							}
						}
					}
					// if the user isn't on the access list
					// we kick them out ^_^
				}
			}
			// is mode i in the modelock?
			
			if ( strstr( $mode_array['plus'], 'O' ) )
			{
				foreach ( core::$chans[$channel->channel]['users'] as $nick => $modes )
				{
					if ( !core::$nicks[$nick]['ircop'] )
					{
						ircd::kick( core::$config->chanserv->nick, $nick, $channel->channel, 'IRCop only channel' );
						timer::add( array( 'chanserv', 'part_chan_callback', array( $channel->channel ) ), 1, 1 );
					}
					// if the user isn't on the access list
					// we kick them out ^_^
				}
			}
			// how about +O?
		}
		// any modelocks?
		
		if ( self::check_flags( $channel->channel, array( 'K' ) ) && !self::check_flags( $channel->channel, array( 'T' ) ) && isset( modules::$list['cs_flags'] ) && isset( modules::$list['cs_topic'] ) )
		{
			if ( trim( $channel->topic ) != trim( core::$chans[$channel->channel]['topic'] ) || $channel->topic != '' )
				ircd::topic( core::$config->chanserv->nick, $channel->channel, $channel->topic );
			// set the previous topic
		}
		// set the topic to the last known topic
	}
	
	/*
	* _is_founder (private)
	* 
	* @params
	* $nick - The nick to check access for
	* $chan - The channel to check.
	*/
	static public function _is_founder( $nick, $chan )
	{
		// we don't check if the channel is registered, because
		// we're asuming that check has already been done (Y)
		// we just grab it.
		
		if ( self::check_levels( $nick, $chan, array( 'F' ) ) || services::oper_privs( $nick, "chanserv_op" ) )
			return true;
		else
			return false;
		// here we just check for flag F
	}
	
	/*
	* check_levels (private)
	* 
	* @params
	* $nick - The nick to check flags for
	* $chan - The channel to check.
	* $flags - an array of flags to check for.
	* $force - whether to check hostnames or not, defaults to true
	* $ident - whether to check for identifications
	* $return - whether to return the ban reason
	* $or_check - whether to check for override
	* $rnick - whether to check the account name of $nick, or actually check against $nick
	*/
	static public function check_levels( $nick, $chan, $flags, $force = true, $ident = true, $return = false, $or_check = true, $rnick = true )
	{
		if ( $ident && core::$nicks[$nick]['identified'] === false )
			return false;
		// they aint even identified..
		
		$user_flags_q = database::select( 'chans_levels', array( 'id', 'channel', 'target', 'flags', 'reason', 'timestamp', 'expire' ), array( 'channel', '=', $chan ) );
		// get our flags records
		
		if ( $rnick )
			$account_name = core::$nicks[$nick]['account'];
		else
			$account_name = $nick;
		$hostname = core::get_full_hostname( $nick );
		// generate a hostname
		
		while ( $chan_flags = database::fetch( $user_flags_q ) )
		{
			if ( $or_check && core::$nicks[$nick]['override'] )
				return true;
			// is override enabled for this user?
			
			if ( $account_name == $chan_flags->target || ( $force && ( strpos( $chan_flags->target, '@' ) !== false && services::match( $hostname, $chan_flags->target ) ) ) )
			{
				foreach ( $flags as $flag )
				{
					if ( strpos( $chan_flags->flags, $flag ) !== false )
					{
						if ( $return )
							return $chan_flags->reason;
						else
							return true;
					}
					// hurrah, we've found a match!
				}
				// loop through the flags, if we find a match, return true
				
				continue;
			}
			// only trigger if this is the user we are in question of.
		}
		// loop through the flag records
		
		return false;
	}
	
	/*
	* check_flags (private)
	* 
	* @params
	* $chan - The channel to check.
	* $flags - an array of flags to check for.
	*/
	static public function check_flags( $chan, $flags )
	{
		$chan_flags_q = database::select( 'chans_flags', array( 'id', 'channel', 'flags' ), array( 'channel', '=', $chan ) );
		$chan_flags = database::fetch( $chan_flags_q );
		// get our flags records
		
		foreach ( $flags as $flag )
		{
			if ( strpos( $chan_flags->flags, $flag ) !== false )
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
	* $chan - The channel to check.
	* $flag - a flag value to grab, eg. modelock (m)
	*/
	static public function get_flags( $chan, $flag )
	{
		if ( $flag == 'd' )
			$param_field = 'desc';
		elseif ( $flag == 'u' )
			$param_field = 'url';
		elseif ( $flag == 'e' )
			$param_field = 'email';
		elseif ( $flag == 'w' )
			$param_field = 'welcome';
		elseif ( $flag == 'm' )
			$param_field = 'modelock';
		elseif ( $flag == 't' )
			$param_field = 'topicmask';
		else
			return false;
		// translate. some craq.
		
		$chan_flags_q = database::select( 'chans_flags', array( 'id', 'channel', 'flags', $param_field ), array( 'channel', '=', $chan ) );
		$chan_flags = database::fetch( $chan_flags_q );
		// get our flags records
		
		return $chan_flags->$param_field;
	}
}

// EOF;
