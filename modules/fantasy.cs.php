<?php

/*
* Acora IRC Services
* modules/fantasy.cs.php: ChanServ fantasy module
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

class cs_fantasy implements module
{
	
	const MOD_VERSION = '0.0.7';
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
		modules::init_module( 'cs_fantasy', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		// these are standard in module constructors
	}
	
	/*
	* main (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	public function main( $ircdata, $startup = false )
	{
		if ( ircd::on_msg( $ircdata ) )
		{
			$nick = core::get_nick( $ircdata, 0 );
			$chan = core::get_chan( $ircdata, 2 );
			
			//if ( core::search_nick( $chan ) !== false )
				//return false;
			// bail if it thinks chan == nick.
			
			if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
				return false;
			// channel isnt registered, halt immediatly.. 
			// either something has cocked up or someone
			// has forced us into a channel :S
			
			if ( commands::on_fantasy_cmd( $ircdata, 'help', core::$config->chanserv->nick ) )
			{
				if ( ircd::$halfop )
					$help = chanserv::$help->CS_HELP_FANTASY_ALL1;
				else
					$help = chanserv::$help->CS_HELP_FANTASY_ALL2;
				
				foreach ( $help as $line )
					services::communicate( core::$config->chanserv->nick, $nick, $line, array( 'p' => core::$config->chanserv->fantasy_prefix ) );	
			}
			// !help command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'owner', core::$config->chanserv->nick ) && ircd::$owner )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'q', 'f', 'F' ) ) === false ) return false;
				
				if ( strpos( $ircdata[4], ':' ) !== false )
					mode::type_check( $chan, $ircdata[4], '+q', core::$config->chanserv->nick );
				elseif ( isset( $ircdata[4] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '+q '.$ircdata[4] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+q '.$nick );
				// check if another param is specified
			}
			// !owner command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'deowner', core::$config->chanserv->nick ) && ircd::$owner )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'q', 'f', 'F' ) ) === false ) return false;
				
				if ( strpos( $ircdata[4], ':' ) !== false )
					mode::type_check( $chan, $ircdata[4], '-q', core::$config->chanserv->nick );
				elseif ( isset( $ircdata[4] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '-q '.$ircdata[4] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '-q '.$nick );
				// check if another param is specified
			}
			// !deowner command
						
			if ( commands::on_fantasy_cmd( $ircdata, 'protect', core::$config->chanserv->nick ) && ircd::$protect )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'a', 'q', 'f', 'F' ) ) === false ) return false;
				
				if ( strpos( $ircdata[4], ':' ) !== false )
					mode::type_check( $chan, $ircdata[4], '+a', core::$config->chanserv->nick );
				elseif ( isset( $ircdata[4] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '+a '.$ircdata[4] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+a '.$nick );
				// check if another param is specified
			}
			// !protect command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'deprotect', core::$config->chanserv->nick ) && ircd::$protect )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'a', 'q', 'f', 'F' ) ) === false ) return false;
				if ( strtolower( $ircdata[4] ) == strtolower( core::$config->chanserv->nick ) ) return false;
				
				if ( strpos( $ircdata[4], ':' ) !== false )
					mode::type_check( $chan, $ircdata[4], '-a', core::$config->chanserv->nick );
				elseif ( isset( $ircdata[4] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '-a '.$ircdata[4] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '-a '.$nick );
				// check if another param is specified
			}
			// !protect command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'op', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'o', 'a', 'q', 'f', 'F' ) ) === false ) return false;
				
				if ( strpos( $ircdata[4], ':' ) !== false )
					mode::type_check( $chan, $ircdata[4], '+o', core::$config->chanserv->nick );
				elseif ( isset( $ircdata[4] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '+o '.$ircdata[4] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+o '.$nick );
				// check if another param is specified
			}
			// !op command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'deop', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'o', 'a', 'q', 'f', 'F' ) ) === false ) return false;
				if ( strtolower( $ircdata[4] ) == strtolower( core::$config->chanserv->nick ) ) return false;
				
				if ( strpos( $ircdata[4], ':' ) !== false )
					mode::type_check( $chan, $ircdata[4], '-o', core::$config->chanserv->nick );
				elseif ( isset( $ircdata[4] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '-o '.$ircdata[4] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '-o '.$nick );
				// check if another param is specified
			}
			// !deop command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'halfop', core::$config->chanserv->nick ) && ircd::$halfop )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'h', 'o', 'a', 'q', 'f', 'F' ) ) === false ) return false;
				
				if ( strpos( $ircdata[4], ':' ) !== false )
					mode::type_check( $chan, $ircdata[4], '+h', core::$config->chanserv->nick );
				elseif ( isset( $ircdata[4] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '+h '.$ircdata[4] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+h '.$nick );
				// check if another param is specified
			}
			// !hop command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'dehalfop', core::$config->chanserv->nick ) && ircd::$halfop )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'h', 'o', 'a', 'q', 'f', 'F' ) ) === false ) return false;
				if ( strtolower( $ircdata[4] ) == strtolower( core::$config->chanserv->nick ) ) return false;
				
				if ( strpos( $ircdata[4], ':' ) !== false )
					mode::type_check( $chan, $ircdata[4], '-h', core::$config->chanserv->nick );
				elseif ( isset( $ircdata[4] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '-h '.$ircdata[4] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '-h '.$nick );
				// check if another param is specified
			}
			// !dehop command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'voice', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'v', 'h', 'o', 'a', 'q', 'f', 'F' ) ) === false ) return false;
				
				if ( strpos( $ircdata[4], ':' ) !== false )
					mode::type_check( $chan, $ircdata[4], '+v', core::$config->chanserv->nick );
				elseif ( isset( $ircdata[4] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '+v '.$ircdata[4] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+v '.$nick );
				// check if another param is specified
			}
			// !voice command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'devoice', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'v', 'h', 'o', 'a', 'q', 'f', 'F' ) ) === false ) return false;
				
				if ( strpos( $ircdata[4], ':' ) !== false )
					mode::type_check( $chan, $ircdata[4], '-v', core::$config->chanserv->nick );
				elseif ( isset( $ircdata[4] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '-v '.$ircdata[4] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '-v '.$nick );
				// check if another param is specified
			}
			// !devoice command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'topic', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 't', 'F' ) ) === false ) return false;
				
				if ( isset( $ircdata[4] ) )
				{
					$topicmask = chanserv::get_flags( $chan, 't' );
					// get the topicmask
					
					if ( $topicmask != null )
					{
						$new_topic = core::get_data_after( $ircdata, 4 );
						$new_topic = str_replace( ' *', ' '.$new_topic, $topicmask );
						$new_topic = str_replace( '\*', '*', $new_topic );
							
						ircd::topic( core::$config->chanserv->nick, $channel->channel, $new_topic );
						database::update( 'chans', array( 'topic' => $new_topic, 'topic_setter' => core::$config->chanserv->nick ), array( 'channel', '=', $channel->channel ) );
					}
					// if there is a topicmask set?
					else
					{
						$new_topic = trim( core::get_data_after( $ircdata, 4 ) );
							
						ircd::topic( core::$config->chanserv->nick, $channel->channel, $new_topic );
						database::update( 'chans', array( 'topic' => $new_topic, 'topic_setter' => core::$config->chanserv->nick ), array( 'channel', '=', $channel->channel ) );
					}
					// if there isnt, just set it normally.
				}
				// make sure there is another mask x]
			}
			// !topic command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'mode', core::$config->chanserv->nick ) || commands::on_fantasy_cmd( $ircdata, 'm', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'h', 'o', 'a', 'q', 'F' ) ) === false ) return false;
				
				if ( isset( $ircdata[4] ) )
				{
					$mode_queue = core::get_data_after( $ircdata, 4 );
					// get the mode queue
						
					if ( !core::$nicks[$nick]['ircop'] )
						$mode_queue[0] = str_replace( 'O', '', $mode_queue[0] );
					// don't let them MODE +O if they're not an IRCop
						
					ircd::mode( core::$config->chanserv->nick, $chan, $mode_queue );
					// check if there are any other parameters in the !mode command
				}
				// are we even setting a mode?
			}
			// !mode command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'kick', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'r', 'F' ) ) === false ) return false;
				// ignore if the nick doesn't have access to perform this
				
				if ( isset( $ircdata[4] ) )
				{
					if ( chanserv::check_levels( $nick, $channel->channel, array( 'o', 'F' ) ) && chanserv::check_levels( $nick, $channel->channel, array( 'o', 'F' ) ) === false )
						return false;
					// check if the user kicking, has the access to kick them. that doesn't make sense, but yeah.
					
					if ( isset( $ircdata[5] ) )
					{
						$reason = core::get_data_after( $ircdata, 5 );
						
						ircd::kick( core::$config->chanserv->nick, $ircdata[4], $chan, '('.$nick.') '.( $reason != '' ) ? $reason : 'No reason' );
						// kick them with the reason
					}
					else
					{
						ircd::kick( core::$config->chanserv->nick, $ircdata[4], $chan, $nick );
						// kick them with no reason
					}
				}
				// make sure a parameter is issued
			}
			// !kick command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'kickban', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'r', 'F' ) ) === false ) return false;
				// ignore if the nick doesn't have access to perform this
				
				if ( isset( $ircdata[4] ) )
				{
					if ( chanserv::check_levels( $nick, $channel->channel, array( 'o', 'F' ) ) && chanserv::check_levels( $nick, $channel->channel, array( 'o', 'F' ) ) === false )
						return false;
					// check if the user kicking, has the access to kick them. that doesn't make sense, but yeah.
					
					if ( $user = core::search_nick( $ircdata[4] ) )
					{
						ircd::mode( core::$config->chanserv->nick, $chan, '+b *@'.$user['host'] );
						
						if ( isset( $ircdata[5] ) )
						{
							$reason = core::get_data_after( $ircdata, 5 );
							
							ircd::kick( core::$config->chanserv->nick, $ircdata[4], $chan, '('.$nick.') '.( $reason != '' ) ? $reason : 'No reason' );
							// kick them with the reason
						}
						else
						{
							ircd::kick( core::$config->chanserv->nick, $ircdata[4], $chan, $nick );
							// kick them with no reason
						}
						// check if there is a reason etc.
					}
					else
					{
						return false;
					}
				}
				// make sure a parameter is issued
			}
			// !ban command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'ban', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'r', 'F' ) ) === false ) return false;
				// ignore if the nick doesn't have access to perform this
				
				if ( isset( $ircdata[4] ) )
				{
					if ( chanserv::check_levels( $nick, $channel->channel, array( 'o', 'F' ) ) && chanserv::check_levels( $nick, $channel->channel, array( 'o', 'F' ) ) === false )
						return false;
					// check if the user kicking, has the access to kick them. that doesn't make sense, but yeah.
					
					if ( strpos( $ircdata[4], '@' ) === false && $user = core::search_nick( $ircdata[4] ) )
						ircd::mode( core::$config->chanserv->nick, $chan, '+b *@'.$user['host'] );
					else
						ircd::mode( core::$config->chanserv->nick, $chan, '+b '.$ircdata[4] );
					// is the hostname in our cache? if not just set a ban on it lol.
				}
			}
			// !ban command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'unban', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'r', 'F' ) ) === false ) return false;
				
				if ( isset( $ircdata[4] ) )
				{
					if ( strpos( $ircdata[4], '@' ) === false && $user = core::search_nick( $ircdata[4] ) )
						ircd::mode( core::$config->chanserv->nick, $chan, '-b *@'.$user['host'] );
					else
						ircd::mode( core::$config->chanserv->nick, $chan, '-b '.$ircdata[4] );
					// is the hostname in our cache? if not unban it..
				}
			}
			// !unban command
			
			if ( commands::on_fantasy_cmd( $ircdata, 'flags', core::$config->chanserv->nick ) && isset( modules::$list['cs_flags'] ) )
			{
				$n_ircdata = $ircdata;
				unset( $n_ircdata[0], $n_ircdata[1], $n_ircdata[2], $n_ircdata[3] );
				array_unshift( $n_ircdata, $chan );
				// construct a new ircdata array
				
				cs_flags::flags_command( $nick, $n_ircdata, true );
				// execute the flags command with the new data
				
				unset( $n_ircdata );
				// get rid of this, isn't longer needed
			}
			// !flags command (experimental)
			
			if ( commands::on_fantasy_cmd( $ircdata, 'levels', core::$config->chanserv->nick ) && isset( modules::$list['cs_levels'] ) )
			{
				$n_ircdata = $ircdata;
				unset( $n_ircdata[0], $n_ircdata[1], $n_ircdata[2], $n_ircdata[3] );
				array_unshift( $n_ircdata, $chan );
				// construct a new ircdata array
				
				cs_levels::levels_command( $nick, $n_ircdata, true );
				// execute the flags command with the new data
				
				unset( $n_ircdata );
				// get rid of this, isn't longer needed
			}
			// !levels command (experimental)
			
			if ( commands::on_fantasy_cmd( $ircdata, 'sync', core::$config->chanserv->nick ) && isset( modules::$list['cs_levels'] ) )
			{
				cs_levels::on_create( core::$chans[$chan]['users'], $channel );
				// execute on_create, cause we just treat it as that
				// this is kinda a shortcut, but well worth it.
				
				ircd::notice( core::$config->chanserv->nick, $chan, ''.$nick.' used SYNC' );
			}
			// !sync command (experimental)
		}
		// only trigger on channel messages
	}
}

// EOF;