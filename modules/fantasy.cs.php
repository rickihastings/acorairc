<?php

/*
* Acora IRC Services
* modules/fantasy.cs.php: ChanServ fantasy module
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
		$return = ircd::on_msg( $ircdata );
		if ( $return !== false )
		{
			$nick = $return['nick'];
			$chan = $return['target'];
			$return['msg'] = substr( $return['msg'], 1 );
			$msgs = explode( ' ', $return['msg'] );
			
			if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
				return false;
			// channel isnt registered, halt immediatly.. 
			// either something has cocked up or someone
			// has forced us into a channel :S the cunts!
			
			if ( chanserv::check_flags( $channel->channel, array( 'F' ) ) === false )
				return false;
			// is +F enabled on $chan?
			
			if ( !isset( $msgs[1] ) && commands::on_fantasy_cmd( $return, 'help', core::$config->chanserv->nick ) )
			{
				$help = chanserv::$help->CS_HELP_FANTASY_ALL1;
			
				if ( ircd::$owner )
					$help = array_merge( $help, chanserv::$help->CS_HELP_FANTASY_ALL_OWNER );
				if ( ircd::$protect )
					$help = array_merge( $help, chanserv::$help->CS_HELP_FANTASY_ALL_PROTECT );
					
				$help = array_merge( $help, chanserv::$help->CS_HELP_FANTASY_ALL_OP );
					
				if ( ircd::$halfop )
					$help = array_merge( $help, chanserv::$help->CS_HELP_FANTASY_ALL_HALFOP );
					
				$help = array_merge( $help, chanserv::$help->CS_HELP_FANTASY_ALL2 );
				// compile a help array
				
				foreach ( $help as $line )
					services::communicate( core::$config->chanserv->nick, $nick, $line, array( 'p' => core::$config->chanserv->fantasy_prefix ) );	
			}
			// !help command (without queries)
			
			if ( isset( $msgs[1] ) && commands::on_fantasy_cmd( $return, 'help', core::$config->chanserv->nick ) )
			{
				$query = implode( ' ', $msgs );
				$query = substr( $query, 1 );
				$query = strtolower( $query );
				// convert to lower case because all the tingy wags are in lowercase
				
				chanserv::get_help( $nick, $query );
				// send help eh.
			}
			// !help command (with queries)
			
			if ( ircd::$owner && commands::on_fantasy_cmd( $return, 'owner', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'q', 'f', 'S', 'F' ) ) === false ) return false;
				
				if ( strpos( $msgs[1], ':' ) !== false )
					mode::type_check( $chan, $msgs[1], '+q', core::$config->chanserv->nick );
				elseif ( isset( $msgs[1] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '+q '.$msgs[1] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+q '.$nick );
				// check if another param is specified
			}
			// !owner command
			
			if ( ircd::$owner && commands::on_fantasy_cmd( $return, 'deowner', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'q', 'f', 'S', 'F' ) ) === false ) return false;
				
				if ( strpos( $msgs[1], ':' ) !== false )
					mode::type_check( $chan, $msgs[1], '-q', core::$config->chanserv->nick );
				elseif ( isset( $msgs[1] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '-q '.$msgs[1] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '-q '.$nick );
				// check if another param is specified
			}
			// !deowner command
						
			if ( ircd::$protect && commands::on_fantasy_cmd( $return, 'protect', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'a', 'q', 'f', 'S', 'F' ) ) === false ) return false;
				
				if ( strpos( $msgs[1], ':' ) !== false )
					mode::type_check( $chan, $msgs[1], '+a', core::$config->chanserv->nick );
				elseif ( isset( $msgs[1] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '+a '.$msgs[1] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+a '.$nick );
				// check if another param is specified
			}
			// !protect command
			
			if ( ircd::$protect && commands::on_fantasy_cmd( $return, 'deprotect', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'a', 'q', 'f', 'S', 'F' ) ) === false ) return false;
				if ( strtolower( $msgs[1] ) == strtolower( core::$config->chanserv->nick ) ) return false;
				
				if ( strpos( $msgs[1], ':' ) !== false )
					mode::type_check( $chan, $msgs[1], '-a', core::$config->chanserv->nick );
				elseif ( isset( $msgs[1] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '-a '.$msgs[1] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '-a '.$nick );
				// check if another param is specified
			}
			// !protect command
			
			if ( commands::on_fantasy_cmd( $return, 'op', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false ) return false;
				
				if ( strpos( $msgs[1], ':' ) !== false )
					mode::type_check( $chan, $msgs[1], '+o', core::$config->chanserv->nick );
				elseif ( isset( $msgs[1] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '+o '.$msgs[1] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+o '.$nick );
				// check if another param is specified
			}
			// !op command
			
			if ( commands::on_fantasy_cmd( $return, 'deop', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false ) return false;
				if ( strtolower( $msgs[1] ) == strtolower( core::$config->chanserv->nick ) ) return false;
				
				if ( strpos( $msgs[1], ':' ) !== false )
					mode::type_check( $chan, $msgs[1], '-o', core::$config->chanserv->nick );
				elseif ( isset( $msgs[1] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '-o '.$msgs[1] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '-o '.$nick );
				// check if another param is specified
			}
			// !deop command
			
			if ( ircd::$halfop && commands::on_fantasy_cmd( $return, 'halfop', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false ) return false;
				
				if ( strpos( $msgs[1], ':' ) !== false )
					mode::type_check( $chan, $msgs[1], '+h', core::$config->chanserv->nick );
				elseif ( isset( $msgs[1] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '+h '.$msgs[1] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+h '.$nick );
				// check if another param is specified
			}
			// !hop command
			
			if ( ircd::$halfop && commands::on_fantasy_cmd( $return, 'dehalfop', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false ) return false;
				if ( strtolower( $msgs[1] ) == strtolower( core::$config->chanserv->nick ) ) return false;
				
				if ( strpos( $msgs[1], ':' ) !== false )
					mode::type_check( $chan, $msgs[1], '-h', core::$config->chanserv->nick );
				elseif ( isset( $msgs[1] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '-h '.$msgs[1] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '-h '.$nick );
				// check if another param is specified
			}
			// !dehop command
			
			if ( commands::on_fantasy_cmd( $return, 'voice', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'v', 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false ) return false;
				
				if ( strpos( $msgs[1], ':' ) !== false )
					mode::type_check( $chan, $msgs[1], '+v', core::$config->chanserv->nick );
				elseif ( isset( $msgs[1] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '+v '.$msgs[1] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '+v '.$nick );
				// check if another param is specified
			}
			// !voice command
			
			if ( commands::on_fantasy_cmd( $return, 'devoice', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'v', 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false ) return false;
				
				if ( strpos( $msgs[1], ':' ) !== false )
					mode::type_check( $chan, $msgs[1], '-v', core::$config->chanserv->nick );
				elseif ( isset( $msgs[1] ) )
					ircd::mode( core::$config->chanserv->nick, $chan, '-v '.$msgs[1] );
				else
					ircd::mode( core::$config->chanserv->nick, $chan, '-v '.$nick );
				// check if another param is specified
			}
			// !devoice command
			
			if ( commands::on_fantasy_cmd( $return, 'topic', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 't', 'S', 'F' ) ) === false ) return false;
				
				if ( isset( $msgs[1] ) )
				{
					$topicmask = chanserv::get_flags( $chan, 't' );
					// get the topicmask
					
					if ( $topicmask != null )
					{
						$new_topic = core::get_data_after( $msgs, 1 );
						$new_topic = str_replace( ' *', ' '.$new_topic, $topicmask );
						$new_topic = str_replace( '\*', '*', $new_topic );
							
						ircd::topic( core::$config->chanserv->nick, $channel->channel, $new_topic );
						database::update( 'chans', array( 'topic' => $new_topic, 'topic_setter' => core::$config->chanserv->nick ), array( 'channel', '=', $channel->channel ) );
					}
					// if there is a topicmask set?
					else
					{
						$new_topic = trim( core::get_data_after( $msgs, 1 ) );
							
						ircd::topic( core::$config->chanserv->nick, $channel->channel, $new_topic );
						database::update( 'chans', array( 'topic' => $new_topic, 'topic_setter' => core::$config->chanserv->nick ), array( 'channel', '=', $channel->channel ) );
					}
					// if there isnt, just set it normally.
				}
				// make sure there is another mask x]
			}
			// !topic command
			
			if ( commands::on_fantasy_cmd( $return, 'mode', core::$config->chanserv->nick ) || commands::on_fantasy_cmd( $return, 'm', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'h', 'o', 'a', 'q', 'S', 'F' ) ) === false ) return false;
				
				if ( isset( $msgs[1] ) )
				{
					$mode_queue = core::get_data_after( $msgs, 1 );
					// get the mode queue
						
					if ( !core::$nicks[$nick]['ircop'] )
						$mode_queue = str_replace( 'O', '', $mode_queue );
					// don't let them MODE +O if they're not an IRCop
					
					ircd::mode( core::$config->chanserv->nick, $chan, $mode_queue );
					// check if there are any other parameters in the !mode command
				}
				// are we even setting a mode?
			}
			// !mode command
			
			if ( commands::on_fantasy_cmd( $return, 'kick', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'r', 'S', 'F' ) ) === false ) return false;
				// ignore if the nick doesn't have access to perform this
				
				if ( isset( $msgs[1] ) )
				{
					if ( chanserv::check_levels( $msgs[1], $channel->channel, array( 'S', 'F' ) ) )
						return false;
					// you can't k/b anyone with either +S or +F, others can be k/bed though.
					
					if ( isset( $msgs[2] ) )
					{
						$reason = core::get_data_after( $msgs, 2 );
						
						ircd::kick( core::$config->chanserv->nick, $msgs[1], $chan, '('.$nick.') '.( $reason != '' ) ? $reason : 'No reason' );
						// kick them with the reason
					}
					else
					{
						ircd::kick( core::$config->chanserv->nick, $msgs[1], $chan, $nick );
						// kick them with no reason
					}
				}
				// make sure a parameter is issued
			}
			// !kick command
			
			if ( commands::on_fantasy_cmd( $return, 'kickban', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'r', 'S', 'F' ) ) === false ) return false;
				// ignore if the nick doesn't have access to perform this
				
				if ( isset( $msgs[1] ) )
				{
					if ( chanserv::check_levels( $msgs[1], $channel->channel, array( 'S', 'F' ) ) )
						return false;
					// you can't k/b anyone with either +S or +F, others can be k/bed though.
					
					if ( $user = core::search_nick( $msgs[1] ) )
					{
						ircd::mode( core::$config->chanserv->nick, $chan, '+b *@'.$user['host'] );
						
						if ( isset( $msgs[2] ) )
						{
							$reason = core::get_data_after( $msgs, 2 );
							
							ircd::kick( core::$config->chanserv->nick, $msgs[1], $chan, '('.$nick.') '.( $reason != '' ) ? $reason : 'No reason' );
							// kick them with the reason
						}
						else
						{
							ircd::kick( core::$config->chanserv->nick, $msgs[1], $chan, $nick );
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
			
			if ( commands::on_fantasy_cmd( $return, 'ban', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'r', 'S', 'F' ) ) === false ) return false;
				// ignore if the nick doesn't have access to perform this
				
				if ( isset( $msgs[1] ) )
				{
					if ( chanserv::check_levels( $msgs[1], $channel->channel, array( 'S', 'F' ) ) )
						return false;
					// you can't k/b anyone with either +S or +F, others can be k/bed though.
					
					if ( strpos( $msgs[1], '@' ) === false && $user = core::search_nick( $msgs[1] ) )
						ircd::mode( core::$config->chanserv->nick, $chan, '+b *@'.$user['host'] );
					else
						ircd::mode( core::$config->chanserv->nick, $chan, '+b '.$msgs[1] );
					// is the hostname in our cache? if not just set a ban on it lol.
				}
			}
			// !ban command
			
			if ( commands::on_fantasy_cmd( $return, 'unban', core::$config->chanserv->nick ) )
			{
				if ( chanserv::check_levels( $nick, $channel->channel, array( 'r', 'S', 'F' ) ) === false ) return false;
				
				if ( isset( $msgs[1] ) )
				{
					if ( strpos( $msgs[1], '@' ) === false && $user = core::search_nick( $msgs[1] ) )
						ircd::mode( core::$config->chanserv->nick, $chan, '-b *@'.$user['host'] );
					else
						ircd::mode( core::$config->chanserv->nick, $chan, '-b '.$msgs[1] );
					// is the hostname in our cache? if not unban it..
				}
			}
			// !unban command
			
			if ( commands::on_fantasy_cmd( $return, 'flags', core::$config->chanserv->nick ) && isset( modules::$list['cs_flags'] ) )
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
			
			if ( commands::on_fantasy_cmd( $return, 'levels', core::$config->chanserv->nick ) && isset( modules::$list['cs_levels'] ) )
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
			
			if ( commands::on_fantasy_cmd( $return, 'sync', core::$config->chanserv->nick ) && isset( modules::$list['cs_levels'] ) )
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