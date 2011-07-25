<?php

/*
* Acora IRC Services
* modules/info.cs.php: ChanServ info module
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

class cs_info extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'CHAN_UNREGISTERED'	=> 2,
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
		modules::init_module( 'cs_info', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_info', 'help', chanserv::$help->CS_HELP_INFO_1, true );
		chanserv::add_help( 'cs_info', 'help info', chanserv::$help->CS_HELP_INFO_ALL );
		// add the help
		
		chanserv::add_command( 'info', 'cs_info', 'info_command' );
		// add the info command
	}
	
	/*
	* info_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function info_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_chan_info( $input, $nick, $ircdata[0] );
		// $who is the user we're adding REMEMBER!
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _chan_info (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick commandeeer
	* $chan - The channel
	*/
	static public function _chan_info( $input, $nick, $chan )
	{
		$return_data = module::$return_data;
		
		if ( $chan == '' || $chan[0] != '#' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'INFO' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// make sure they've entered a channel
		
		if ( !$channel = services::chan_exists( $chan, array( 'channel', 'timestamp', 'last_timestamp', 'suspended', 'suspend_reason' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->CHAN_UNREGISTERED;
			return $return_data;
		}
		// make sure the channel exists
		
		if ( $channel->suspended == 1 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_SUSPENDED_1, array( 'chan' => $channel->channel ) );
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_SUSPENDED_2, array( 'reason' => $channel->suspend_reason ) );
			$return_data[CMD_DATA] = array( 'suspended' => 1, 'chan' => $channel->channel, 'reason' => $channel->suspend_reason );
			$return_data[CMD_SUCCESS] = true;
			return $return_data;
		}
		
		$founder = database::select( 'chans_levels', array( 'id', 'channel', 'target', 'flags' ), array( 'channel', '=', $chan ) );
		$founders = '';
		
		while ( $f_row = database::fetch( $founder ) )
		{
			if ( strpos( $f_row->flags, 'F' ) !== false )
				$founders .= $f_row->target.', ';
		}
		// get the founder(s)
		
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_1, array( 'chan' => $channel->channel ) );
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_2, array( 'nicks' => substr( $founders, 0, -2 ) ) );
		$return_data[CMD_DATA]['suspended'] = 0;
		$return_data[CMD_DATA]['chan'] = $channel->channel;
		$return_data[CMD_DATA]['nicks'] = substr( $founders, 0, -2 );
		
		$desc = chanserv::get_flags( $channel->channel, 'd' );
		if ( $desc != null )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_3, array( 'desc' => $desc ) );
			$return_data[CMD_DATA]['desc'] = $desc;
		}
		// description?
		
		if ( core::$chans[$chan]['topic'] != '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_4, array( 'topic' => core::$chans[$chan]['topic'] ) );
			$return_data[CMD_DATA]['topic'] = core::$chans[$chan]['topic'];
		}
		// topic
		
		$email = chanserv::get_flags( $channel->channel, 'e' );
		if ( $email != null )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_5, array( 'email' => $email ) );
			$return_data[CMD_DATA]['email'] = $email;
		}
		// is there an email?
		
		$url = chanserv::get_flags( $channel->channel, 'u' );
		if ( $url != null )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_6, array( 'url' => $url ) );
			$return_data[CMD_DATA]['url'] = $url;
		}
		// or a url?
		
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_7, array( 'time' => date( "F j, Y, g:i a", $channel->timestamp ) ) );
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_8, array( 'time' => date( "F j, Y, g:i a", $channel->last_timestamp ) ) );
		$return_data[CMD_DATA]['timestamp'] = $channel->timestamp;
		$return_data[CMD_DATA]['last_timestamp'] = $channel->last_timestamp;
		// timestamps
		
		$modelock = chanserv::get_flags( $channel->channel, 'm' );
		if ( $modelock != null )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_9, array( 'mode_lock' => $modelock ) );
			$return_data[CMD_DATA]['entrymsg'] = $modelock;
		}
		// is there a mode lock?
		
		$entrymsg = chanserv::get_flags( $channel->channel, 'w' );
		if ( $entrymsg != null )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_10, array( 'entrymsg' => $entrymsg ) );
			$return_data[CMD_DATA]['entrymsg'] = $entrymsg;
		}
		// is there an entry msg?
		
		$list = '';
		if ( chanserv::check_flags( $channel->channel, array( 'T' ) ) )
			$list .= 'Topiclock, ';
		if ( chanserv::check_flags( $channel->channel, array( 'K' ) ) )
			$list .= 'Keeptopic, ';
		if ( chanserv::check_flags( $channel->channel, array( 'G' ) ) )
			$list .= 'Guard, ';
		if ( chanserv::check_flags( $channel->channel, array( 'S' ) ) )
			$list .= 'Secure, ';
		if ( chanserv::check_flags( $channel->channel, array( 'F' ) ) )
			$list .= 'Fantasy';
		
		if ( substr( $list, -2, 2 ) == ', ' ) 
			$list = substr( $list, 0 ,-2 );
		// compile our list of options
		
		if ( $list != '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_11, array( 'options' => $list ) );
			$return_data[CMD_DATA]['options'] = $list;
		}
		// if our list doesn't equal '', eg. empty show the info.
			
		$expiry_time = core::$config->chanserv->expire * 86400;
		$return_data[CMD_DATA]['expiry_time'] = $channel->last_timestamp + $expiry_time;
			
		if ( core::$nicks[$nick]['ircop'] && core::$nicks[$nick]['identified'] && core::$config->chanserv->expire != 0 )
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INFO_12, array( 'time' => date( "F j, Y, g:i a", $channel->last_timestamp + $expiry_time ) ) );
		// if the nick in question has staff powers, we show the expiry times.
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
}

// EOF;