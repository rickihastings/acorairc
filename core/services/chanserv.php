
<?php

/*
* Acora IRC Services
* core/services/chanserv.php: ChanServ initiation class
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

class chanserv implements service
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
		require( BASEPATH.'/lang/'.core::$config->server->lang.'/chanserv.php' );
		self::$help = $help;
		// load the help file
		
		if ( isset( core::$config->chanserv ) )
			ircd::introduce_client( core::$config->chanserv->nick, core::$config->chanserv->user, core::$config->chanserv->host, core::$config->chanserv->real );
		else
			return;
		// connect the bot
		
		foreach ( core::$config->chanserv_modules as $id => $module )
			modules::load_module( 'cs_'.$module, $module.'.cs.php' );
		// load the chanserv modules
		
		timer::add( array( 'chanserv', 'check_expire', array() ), 300, 0 );
		// set a timer!
	}
	
	/*
	* main (event_hook)
	* 
	* @params
	* $ircdata - ..
	*/
	public function main( $ircdata, $startup = false )
	{
		self::on_chan_create( $ircdata );
		// when a channel is created, NOT registered :)
		
		self::on_join( $ircdata );
		// onjoin hook
		
		self::on_part( $ircdata );
		// is the chan empty? gtfo.
			
		self::on_quit( $ircdata );
		// is the channel empty?
			
		self::on_mode( $ircdata );
		// check mode changes
			
		self::on_kick( $ircdata );
		// on kick event.
		
		foreach ( modules::$list as $module => $data )
			if ( $data['type'] == 'chanserv' )
				modules::$list[$module]['class']->main( $ircdata, $startup );
				// loop through the modules for chanserv.

		if ( ircd::on_msg( $ircdata, core::$config->chanserv->nick ) )
		{
			$nick = core::get_nick( $ircdata, 0 );
			$command = substr( core::get_data_after( $ircdata, 3 ), 1 );
			// convert to lower case because all the tingy wags are in lowercase
			
			self::get_command( $nick, $command );
		}
		// this is what we use to handle command listens
		// should be quite epic.
	}
	
	/*
	* part_chan_callback (timer)
	* 
	* @params
	* void
	*/
	static public function part_chan_callback( $chan )
	{
		if ( count( core::$chans[$chan]['users'] ) == 1 && isset( core::$chans[$chan]['users'][core::$config->chanserv->nick] ) )
			ircd::part_chan( core::$config->chanserv->nick, $chan );
		// if we're the only person in the channel, leave it.
	}
	
	/*
	* on_chan_create (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	static public function on_chan_create( $ircdata )
	{
		if ( ircd::on_chan_create( $ircdata ) )
		{
			$chans = explode( ',', $ircdata[2] );
			// chans
			
			foreach ( $chans as $chan )
			{
				if ( $channel = services::chan_exists( $chan, array( 'channel', 'topic', 'suspended' ) ) )
				{
					self::_join_channel( $channel );
					// join the channel
				}
				// does the channel exist?
			}
		}
		// hook to the event when a channel is created.
	}
	
	/*
	* on_part (event hook)
	* 
	* @params
	* $ircdata - ..
	*/
	static public function on_part( $ircdata )
	{
		if ( ircd::on_part( $ircdata ) )
		{
			$chan = core::get_chan( $ircdata, 2 );
			// get the channel
			
			if ( count( core::$chans[$chan]['users'] ) == 1 && isset( core::$chans[$chan]['users'][core::$config->chanserv->nick] ) )
			{
				timer::add( array( 'chanserv', 'part_chan_callback', array( $chan ) ), 1, 1 );
				// instead of leaving straight away we add it to a timer, to be checked and
				// left in the next loop, incase someone has joined.
			}
			// we're the only person in the channel..
		}
	}
	
	/*
	* on_join (event hook)
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_join( $ircdata )
	{
		if ( ircd::on_join( $ircdata ) )
		{
			$chans = explode( ',', $ircdata[2] );
			// find the chans.
			
			foreach ( $chans as $chan )
			{
				if ( services::chan_exists( $chan, array( 'channel', 'topic' ) ) !== false )
				{
					database::update( 'chans', array( 'last_timestamp' => core::$network_time ), array( 'channel', '=', $chan ) );
					// lets update the last used timestamp
				}
				// is the channel registered?
			}
			// we have to do this shit, because unreal has JOINs made up of #chan1,#chan2 craq
			// seriously, in server 2 server? have a laugh son.
		}
	}
	
	/*
	* on_quit (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	static public function on_quit( $ircdata )
	{
		if ( ircd::on_quit( $ircdata ) )
		{
			$nick = core::get_nick( $ircdata, 0 );
			
			foreach ( core::$chans as $chan => $data )
			{
				if ( count( core::$chans[$chan]['users'] ) == 1 && isset( core::$chans[$chan]['users'][core::$config->chanserv->nick] ) )
				{
					ircd::part_chan( core::$config->chanserv->nick, $chan );
					// leave the channel.
				}
				// ok now we check whos left in the channel, if its only us lets leave
			}
			// are they in any channels?
		}
		// only trigger on_quit
	}
	
	/*
	* on_mode (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	static public function on_mode( $ircdata )
	{
		if ( ircd::on_mode( $ircdata ) )
		{
			$nick = core::get_nick( $ircdata, 0 );
			$chan = core::get_chan( $ircdata, 2 );
			$mode_queue = core::get_data_after( $ircdata, 4 );
			
			$a_mode = strpos( $mode_queue, 'a' );
			$o_mode = strpos( $mode_queue, 'o' );
			$cs_uid = array_search( core::$config->chanserv->nick, core::$uids );
			// bleh.
			
			if ( ( strpos( $mode_queue, core::$config->chanserv->nick ) || strpos( $mode_queue, $cs_uid ) ) && ( $a_mode !== false || $o_mode !== false ) )
			{
				if ( ircd::$protect )
					ircd::mode( core::$config->chanserv->nick, $chan, '+ao '.core::$config->chanserv->nick.' '.core::$config->chanserv->nick );
					// +ao its self.
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+o '.core::$config->chanserv->nick );
					// +o its self.
			}
			// we're being deopped! WHAT THE FUCK! :D
		}
	}
	
	/*
	* on_kick (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	static public function on_kick( $ircdata )
	{
		if ( ircd::on_kick( $ircdata ) )
		{
			$nick = core::get_nick( $ircdata, 0 );
			$chan = core::get_chan( $ircdata, 2 );
			$who = core::get_nick( $ircdata, 3 );
			
			if ( $who == core::$config->chanserv->nick || str_replace( ':', '', $ircdata[0] ) == array_search( core::$config->chanserv->nick, core::$uids ) )
			{
				ircd::join_chan( core::$config->chanserv->nick, $chan );
				// join the chan.
				core::$chans[$chan]['users'][core::$config->chanserv->nick] = '';
				// add chanserv to the users array, housekeeping it :D
				
				if ( ircd::$protect )
					ircd::mode( core::$config->chanserv->nick, $chan, '+ao '.core::$config->chanserv->nick.' '.core::$config->chanserv->nick );
					// +ao its self.
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+o '.core::$config->chanserv->nick );
					// +o its self.
			}
			// what the fuck is this tool doing.. kicking us!!?! we SHOULD kick their ass
			// but we're not gonna x]
			
			if ( count( core::$chans[$chan]['users'] ) == 1 && isset( core::$chans[$chan]['users'][core::$config->chanserv->nick] ) )
			{
				ircd::part_chan( core::$config->chanserv->nick, $chan );
				// leave the channel.
			}
			// we're the only person in the channel.. hopefully, lets leave x]
		}
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
		database::update( 'chans', array( 'last_timestamp' => core::$network_time ), array( 'channel', '=', $channel->channel ) );
		// lets update the last used timestamp
	
		if ( self::check_flags( $channel->channel, array( 'G' ) ) && $channel->suspended == 0 && isset( modules::$list['cs_fantasy'] ) && !isset( core::$chans[$channel->channel]['users'][core::$config->chanserv->nick] ) )
		{
			ircd::join_chan( core::$config->chanserv->nick, $channel->channel );
			// join the chan.
			
			if ( ircd::$protect )
				ircd::mode( core::$config->chanserv->nick, $channel->channel, '+ao '.core::$config->chanserv->nick.' '.core::$config->chanserv->nick );
				// +ao its self.
			else
				ircd::mode( core::$config->chanserv->nick, $channel->channel, '+o '.core::$config->chanserv->nick );
				// +o its self.
		}
		// check if guard is on
		
		$modelock = self::get_flags( $channel->channel, 'm' );
		// store some flag values in variables.
		
		if ( $modelock != null && $channel->suspended == 0 )
		{
			ircd::mode( core::$config->chanserv->nick, $channel->channel, $modelock );
			
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
						if ( self::check_levels( $nick, $channel->channel, array( 'k', 'v', 'h', 'o', 'a', 'q', 'F' ), true, false ) === false )
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
		
		if ( self::check_levels( $nick, $chan, array( 'F' ) ) || ( core::$nicks[$nick]['ircop'] && services::user_exists( $nick, true, array( 'id', 'display' ) ) !== false ) )
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
	*/
	static public function check_levels( $nick, $chan, $flags, $force = true, $ident = true, $return = false, $or_check = true )
	{
		if ( $ident && !$user = services::user_exists( $nick, true, array( 'id', 'display' ) ) )
			return false;
		// they aint even identified..
		
		$user_flags_q = database::select( 'chans_levels', array( 'id', 'channel', 'target', 'flags', 'reason' ), array( 'channel', '=', $chan ) );
		// get our flags records
		
		$hostname = core::get_full_hostname( $nick );
		// generate a hostname
		
		while ( $chan_flags = database::fetch( $user_flags_q ) )
		{
			if ( $or_check && core::$nicks[$nick]['override'] )
				return true;

			// is override enabled for this user?
			
			if ( $nick == $chan_flags->target || ( $force && ( strpos( $chan_flags->target, '@' ) !== false && services::match( $hostname, $chan_flags->target ) ) ) )
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
		print ' WILL FIND U';
	}
	
	/*
	* add_help_prefix
	* 
	* @params
	* $command - The command to add a prefix for.
	* $module - The name of the module.
	* $help - The prefix to add.
	*/
	static public function add_help_fix( $module, $what, $command, $help )
	{
		commands::add_help_fix( 'chanserv', $module, $what, $command, $help );
	}
	
	/*
	* add_help
	* 
	* @params
	* $command - The command to hook the array to.
	* $module - The name of the module.
	* $help - The array to hook.
	*/
	static public function add_help( $module, $command, $help, $oper_help = false )
	{
		commands::add_help( 'chanserv', $module, $command, $help, $oper_help );
	}
	
	/*
	* get_help
	* 
	* @params
	* $nick - Who to send the help too?
	* $command - The command to get the help for.
	*/
	static public function get_help( $nick, $command )
	{
		commands::get_help( 'chanserv', $nick, $command );
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
		commands::add_command( 'chanserv', $command, $class, $function );
	}
	
	/*
	* get_command
	* 
	* @params
	* $nick - The nick requesting the command
	* $command - The command to hook to
	*/
	static public function get_command( $nick, $command )
	{
		commands::get_command( 'chanserv', $nick, $command );
	}
}

// EOF;