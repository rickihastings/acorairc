<?php

/*
* Acora IRC Services
* modules/topic.cs.php: ChanServ topic module
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

class cs_topic extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'CHAN_UNREGISTERED'	=> 2,
		'ACCESS_DENIED'		=> 3,
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
		modules::init_module( 'cs_topic', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_topic', 'help commands', chanserv::$help->CS_HELP_TOPIC_1, true );
		chanserv::add_help( 'cs_topic', 'help topic', chanserv::$help->CS_HELP_TOPIC_ALL );
		// add the help
		
		chanserv::add_command( 'topic', 'cs_topic', 'topic_command' );
		// add the commands
	}
	
	/*
	* topic_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function topic_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_change_topic( $input, $ircdata[0], core::get_data_after( $ircdata, 1 ) );
		// call _change_topic
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _change_topic (command)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - Channel to change topic of
	* $topic - New topic (uses topicmask)
	*/
	static public function _change_topic( $input, $nick, $chan, $topic )
	{
		$return_data = module::$return_data;
	
		if ( $chan == '' || $chan[0] != '#' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'TOPIC' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// make sure they've entered a channel
		
		if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->CHAN_UNREGISTERED;
			return $return_data;
		}
		// make sure the channel exists.
		
		if ( chanserv::check_levels( $nick, $chan, array( 't', 'S', 'F' ) ) === false )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// do they have access?
		
		$topicmask = chanserv::get_flags( $chan, 't' );
		// get the topicmask
		
		if ( $topicmask != null )
		{
			$topic = str_replace( ' *', ' '.$new_topic, $topicmask );
			$topic = str_replace( '\*', '*', $topic );
						
			ircd::topic( core::$config->chanserv->nick, $channel->channel, $topic );
			database::update( 'chans', array( 'topic' => $topic, 'topic_setter' => core::$config->chanserv->nick ), array( 'channel', '=', $channel->channel ) );
		}
		// is there a topic mask?
		// if not just set a normal topic i reckon
		else
		{
			$topic = trim( $topic );
			
			if ( trim( $topic ) == '' )
			{
				ircd::topic( core::$config->chanserv->nick, $chan, '' );
				database::update( 'chans', array( 'topic' => '', 'topic_setter' => core::$config->chanserv->nick ), array( 'channel', '=', $chan ) );
				// set us an empty topic
			}
			else
			{
				ircd::topic( core::$config->chanserv->nick, $chan, $topic );
				database::update( 'chans', array( 'topic' => $topic, 'topic_setter' => core::$config->chanserv->nick ), array( 'channel', '=', $chan ) );
				// change the topic
			}
		}
		// we gotta get the topicmask etc
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// log this and return.
	}
	
	/*
	* on_topic (event hook)
	*/
	static public function on_topic( $setter, $chan, $topic )
	{
		if ( $channel = services::chan_exists( $chan, array( 'channel', 'topic' ) ) )
		{
			if ( chanserv::check_flags( $chan, array( 'T' ) ) && chanserv::check_flags( $chan, array( 'K' ) ) && $channel->topic != $topic )
			{
				ircd::topic( core::$config->chanserv->nick, $channel->channel, $channel->topic );
				database::update( 'chans', array( 'topic_setter' => core::$config->chanserv->nick ), array( 'channel', '=', $chan ) );
				return false;
				// reset it i reckon.
			}
			elseif ( $channel->topiclock == 0 )
			{
				database::update( 'chans', array( 'topic' => $topic, 'topic_setter' => $setter ), array( 'channel', '=', $chan ) );
				// OTHERWISE, update it.
			}
			// some housekeepin.
		}
		// make sure the channel exists.
	}
}

// EOF;