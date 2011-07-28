<?php

/*
* Acora IRC Services
* modules/levels.cs.php: ChanServ levels module
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

class cs_levels extends module
{
	
	const MOD_VERSION = '0.1.7';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $flags;
	// valid flags.
	
	static public $set = array();
	static public $not_set = array();
	static public $already_set = array();
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'CHAN_UNREGISTERED'	=> 2,
		'ACCESS_DENIED'		=> 3,
		'INVALID_FLAG'		=> 4,
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
		modules::init_module( 'cs_levels', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_levels', 'help', chanserv::$help->CS_HELP_LEVELS_1, true );
		// add the help
			
		chanserv::add_help( 'cs_levels', 'help levels', chanserv::$help->CS_HELP_LEVELS_ALL );
		
		if ( ircd::$halfop ) 
			chanserv::add_help( 'cs_levels', 'help levels', chanserv::$help->CS_HELP_LEVELS_ALL_HOP );
			
		chanserv::add_help( 'cs_levels', 'help levels', chanserv::$help->CS_HELP_LEVELS_ALL_OP );
			
		if ( ircd::$protect ) 
			chanserv::add_help( 'cs_levels', 'help levels', chanserv::$help->CS_HELP_LEVELS_ALL_PRO );
		if ( ircd::$owner ) 
			chanserv::add_help( 'cs_levels', 'help levels', chanserv::$help->CS_HELP_LEVELS_ALL_OWN );
		
		chanserv::add_help( 'cs_levels', 'help levels', chanserv::$help->CS_HELP_LEVELS_ALL2 );
		// the help we add is setup in parts.
		
		chanserv::add_command( 'levels', 'cs_levels', 'levels_command' );
		// add the command
		
		self::$flags = '+-kvhoaqsrftiRSFb';
		// string of valid flags
		
		if ( !ircd::$halfop )
			self::$flags = str_replace( 'h', '', self::$flags );
		// if halfop isnt enabled, remove h and H
		
		if ( !ircd::$protect )
			self::$flags = str_replace( 'a', '', self::$flags );
		// same for protect
		
		if ( !ircd::$owner )
			self::$flags = str_replace( 'q', '', self::$flags );
		// and finally, owner
	}
	
	/*
	* levels_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	* $announce - If set to true, the channel will be noticed.
	*/
	static public function levels_command( $nick, $ircdata = array(), $announce = false )
	{
		$levels_result = chanserv::check_levels( $nick, $ircdata[0], array( 'v', 'h', 'o', 'a', 'q', 'r', 'f', 'S', 'F' ) );
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		// get the channel.
		
		if ( services::chan_exists( $ircdata[0], array( 'channel' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			return false;
		}
		// make sure the channel exists.
		
		if ( !$levels_result )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// i don't think they have access to see/edit the channel list..
		
		if ( $ircdata[2] == '' && $ircdata[1] == '' )
			$return_data = self::_list_levels_chan( $input, $nick, $ircdata[0] );
		else
			$return_data = self::_set_levels_chan( $input, $nick, $ircdata[0], $ircdata[2], $ircdata[1] );
		// call the corresponding command
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _list_levels_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to list levels for
	*/
	static public function _list_levels_chan( $input, $nick, $chan )
	{
		$return_data = module::$return_data;
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LEVELS_LIST_TOP, array( 'chan' => $chan ) );
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LEVELS_LIST_DLM );
		// start of flag list
		
		$flags_q = database::select( 'chans_levels', array( 'id', 'channel', 'target', 'flags', 'reason', 'timestamp' ), array( 'channel', '=', $chan ) );
		// get the flag records
		
		$x = 0;
		while ( $flags = database::fetch( $flags_q ) )
		{
			$x++;
			$false_flag = $flags->flags;
			$modified = core::format_time( core::$network_time - $flags->timestamp );
			$x_s = $x;
			
			$y_s = strlen( $x );
			for ( $i_s = $y_s; $i_s <= 5; $i_s++ )
				$x_s .= ' ';
			
			if ( !isset( $flags->flags[15] ) )
			{
				$y = strlen( $flags->flags );
				for ( $i = $y; $i <= 14; $i++ )
					$false_flag .= ' ';
			}
			// this is just a bit of fancy fancy, so everything displays neat, like so:
			// +ao  N0valyfe
			// +v   tool
			
			/*if ( $flags->reason != '' )
			{
				$expire = ( $flags->expire == 0 ) ? 'Never' : ( ( $flags->timestamp + $flags->expire ) - core::$network_time ).' seconds';
				$extra = '('.$flags->reason.')';
				$expired =  ' (Expires in: '.core::format_time( $expire ).')';
			}
			else
			{
				$extra = '';
				$expired = '';
			}*/
			// this could maybe be added at a later date, i'm not sure? Look into it soon - n0valyfe
			
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LEVELS_LIST, array( 'num' => $x_s, 'target' => $flags->target, 'flags' => '+'.$false_flag, 'modified' => $modified ) );
			$return_data[CMD_DATA][] = array( 'target' => $flags->target, 'flags' => $flags->flags, 'modified' => $modified );
			// show the flag
		}
		// loop through them
		
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LEVELS_LIST_DLM );
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LEVELS_LIST_BTM, array( 'chan' => $chan ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// show other help data
	}
	
	/*
	* _set_levels_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The channel to set levels for
	* $target - The target to set the levels on
	* $flags - The flags to set on the target
	*/
	static public function _set_levels_chan( $input, $nick, $chan, $target, $flags )
	{
		$return_data = module::$return_data;
		$flag_a = array();
		foreach ( str_split( $flags ) as $pos => $flag )
		{
			if ( strpos( self::$flags, $flag ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LEVELS_UNKNOWN, array( 'flag' => $flag ) );
				$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_FLAG;
				return $return_data;
			}
			// flag is invalid.
			
			$flag_a[$flag]++;
			// plus
			
			if ( $flag_a[$flag] > 1 || $flag != '-' && $flag != '+' )
				$flag_a[$flag]--;
			// check for dupes
		}
		// check if the flag is valid
		
		if ( strpos( $target, '@' ) === false )
		{
			if ( !$user = services::user_exists( $target, false, array( 'id', 'display' ) ) )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_UNREGISTERED_NICK, array( 'nick' => $target ) );
				$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
				return $return_data;
			}
			// they aint even identified..
			// were dealing with a nickname, check if it is registered
			// if not, back out
		}
		else
		{
			if ( strpos( $target, '!' ) === false )
				$target = '*!'.$target;
			// we're dealing with a mask, check if it a proper mask
			// *!*@* < like so.
		}
		
		$flags = '';
		foreach ( $flag_a as $flag => $count )
			$flags .= $flag;
		// reconstruct the flags
		
		$flag_array = mode::sort_modes( $flags, false );
		// sort our flags up
		
		foreach ( str_split( $flag_array['plus'] ) as $flag )
		{
			self::_set_levels( $nick, $chan, $target, $flag, '+', $return_data );
			if ( isset( $return_data[CMD_FAILCODE] ) )
				return $return_data;
		}
		// loop though our plus flags
		
		foreach ( str_split( $flag_array['minus'] ) as $flag )
		{
			self::_set_levels( $nick, $chan, $target, $flag, '-', $return_data );
			if ( isset( $return_data[CMD_FAILCODE] ) )
				return $return_data;
		}
		// loop through the minus flags
		
		if ( isset( self::$set[$target] ) )
		{
			$response .= services::parse( chanserv::$help->CS_LEVELS_SET, array( 'target' => $target, 'flag' => self::$set[$target], 'chan' => $chan ) );
			$response .= ( isset( self::$already_set[$target] ) || isset( self::$not_set[$target] ) ) ? ', ' : '';
			$return_data[CMD_DATA]['set'] = self::$set[$target];			
			unset( self::$set[$target] );
		}
		// send back the target stuff..
		
		if ( isset( self::$already_set[$target] ) )
		{
			$response .= services::parse( chanserv::$help->CS_LEVELS_ALREADY_SET, array( 'target' => $target, 'flag' => self::$already_set[$target], 'chan' => $chan ) );
			$response .= ( isset( self::$not_set[$target] ) ) ? ', ' : '';
			$return_data[CMD_DATA]['already_set'] = self::$already_set[$target];
			unset( self::$already_set[$target] );
		}
		// send back the target stuff..
		
		if ( isset( self::$not_set[$target] ) )
		{
			$response .= services::parse( chanserv::$help->CS_LEVELS_NOT_SET, array( 'target' => $target, 'flag' => self::$not_set[$target], 'chan' => $chan ) );
			$return_data[CMD_DATA]['not_set'] = self::$not_set[$target];
			unset( self::$not_set[$target] );
		}
		// send back the target stuff..
		
		$return_data[CMD_RESPONSE][] = $response;
		$return_data[CMD_DATA]['target'] = $target;
		$return_data[CMD_DATA]['chan'] = $chan;
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return data
	}
	
	/*
	* _set_levels
	* 
	* $nick, $unick, $mode, $params, &$return_data
	*/
	public function _set_levels( $nick, $chan, $target, $flag, $mode, &$return_data )
	{
		// ----------- k ----------- //
		if ( $flag == 'k' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'k', $return_data );
			// k the target in question
		}
		// ----------- k ----------- //
		
		// ----------- v ----------- //
		elseif ( $flag == 'v' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'v', $return_data );
			// v the target in question
		}
		// ----------- v ----------- //
		
		// ----------- h ----------- //
		elseif ( $flag == 'h' && ircd::$halfop )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'h', $return_data );
			// h the target in question
		}
		// ----------- h ----------- //
		
		// ----------- o ----------- //
		elseif ( $flag == 'o' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'a', 'q', 'f', 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'o', $return_data );
			// o the target in question
		}
		// ----------- o ----------- //
		
		// ----------- a ----------- //
		elseif ( $flag == 'a' && ircd::$protect )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'q', 'f', 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'a', $return_data );
			// a the target in question
		}
		// ----------- a ----------- //
		
		// ----------- q ----------- //
		elseif ( $flag == 'q' && ircd::$owner )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'f', 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'q', $return_data );
			// q the target in question
		}
		// ----------- q ----------- //
		
		// ----------- s ----------- //
		elseif ( $flag == 's' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'s', $return_data );
			// s the target in question
		}
		// ----------- s ----------- //
		
		// ----------- r ----------- //
		elseif ( $flag == 'r' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'r', $return_data );
			// r the target in question
		}
		// ----------- r ----------- //
		
		// ----------- f ----------- //
		elseif ( $flag == 'f' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'f', $return_data );
			// f the target in question
		}
		// ----------- f ----------- //
		
		// ----------- t ----------- //
		elseif ( $flag == 't' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'t', $return_data );
			// t the target in question
		}
		// ----------- t ----------- //
		
		// ----------- i ----------- //
		elseif ( $flag == 'i' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'i', $return_data );
			// i the target in question
		}
		// ----------- i ----------- //

		// ----------- R ----------- //
		elseif ( $flag == 'R' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'R', $return_data );
			// R the target in question
		}
		// ----------- R ----------- //
		
		// ----------- S ----------- //
		elseif ( $flag == 'S' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'S', $return_data );
			// S the target in question
		}
		// ----------- S ----------- //
		
		// ----------- F ----------- //
		elseif ( $flag == 'F' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			self::set_flag( $nick, $chan, $target, $mode.'F', $return_data );
			// F the target in question
		}
		// ----------- F ----------- //
		
		// ----------- b ----------- //
		elseif ( $flag == 'b' )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'r', 'S', 'F' ) ) === false )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
				$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
				return $return_data;
			}
			// do they have access to alter this?
			
			if ( $mode == '+' )
			{
				$rexpire = ( trim( $ircdata[3] ) == '' ) ? 0 : $ircdata[3];
				$reason = core::get_data_after( $ircdata, 4 );
				
				$reason = ( $reason == '' ) ? 'No reason' : $reason;
				$days = $hours = $minutes = $expire = 0;
				// grab the reason etc
				
				$parsed = preg_split( '/(d|h|m)/', $rexpire, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
				// format %%d%%h%%m to a timestamp
				
				$fi_ = 0;
				foreach ( $parsed as $i_ => $p_ )
				{
					$fi_++;
					if ( isset( $parsed[$fi_] ) && $parsed[$fi_] == 'd' )
					{
						$days = ( $p_ * 86400 );
						$expire = $expire + $days;
					}
					if ( isset( $parsed[$fi_] ) && $parsed[$fi_] == 'h' )
					{
						$hours = ( $p_ * 3600 );
						$expire = $expire + $hours;
					}
					if ( isset( $parsed[$fi_] ) && $parsed[$fi_] == 'm' )
					{
						$minutes = ( $p_ * 60 );
						$expire = $expire + $minutes;
					}
					// days hours and mins converted to seconds
				}
				// loop through calculating it into seconds
				
				$expire = ( count( $parsed ) == 0 ) ? 0 : $expire;
			}
			
			if ( $mode == '-' )
				$return = self::set_flag( $nick, $chan, $target, $mode.'b', $return_data );
			else
				$return = self::set_flag( $nick, $chan, $target, $mode.'b', $return_data, $reason, $expire );
			// determine whether we need to send reasons and expire?
			
			if ( $return !== false && $mode == '-' )
			{
				if ( strpos( $target, '@' ) === false && $user = core::search_nick( $target ) )
					mode::set( core::$config->chanserv->nick, $chan, '-b *@'.$user['host'] );
				else
					mode::set( core::$config->chanserv->nick, $chan, '-b '.$target );
				// is the hostname in our cache? if not unban it..
			}
			elseif ( $return !== false && $mode == '+' )
			{
				foreach ( core::$chans[$chan]['users'] as $user => $modes )
				{
					$hostname = core::get_full_hostname( $nick );
						
					if ( ( strpos( $mask, '@' ) && services::match( $hostname, $target ) ) || $user == $target )
					{
						if ( chanserv::check_levels( $nick, $channel->channel, array( 'v', 'h', 'o', 'a', 'q', 'F' ) ) )
							continue;
						// don't trigger if they are on the old access list.
						
						mode::set( core::$config->chanserv->nick, $chan, '+b *@'.core::$nicks[$user]['host'] );
						ircd::kick( core::$config->chanserv->nick, $user, $chan, $reason );
						// kickban them, but don't stop looping, because there could be more than one match.
					}
					// check for a match
				}
				// loop through the users in this channel, finding
				// matches to the +b flag thats just been set
				
				if ( $expire != 0 )
					timer::add( array( 'cs_levels', 'set_flag', array( $nick, $chan, $target, '-b' ) ), $expire, 1 );
				// if expire != 0, set up a timer
			}
			// b the target in question
		}
		// ----------- b ----------- //
	}
	
	/*
	* on_chan_create (event hook)
	*/
	static public function on_chan_create( $chan )
	{
		if ( chanserv::$chan_q[$chan] === false )
			return false;
		// if the channel doesn't exist we return false, to save us the hassle of wasting
		// resources on this stuff below.
		
		self::on_create( core::$chans[$chan]['users'], chanserv::$chan_q[$chan], true );
		// on_create event
	}
	
	/*
	* on_join (event hook)
	*/
	static public function on_join( $nick, $chan )
	{
		if ( chanserv::$chan_q[$chan] === false )
			return false;
		// if the channel doesn't exist we return false, to save us the hassle of wasting
		// resources on this stuff below.
		
		if ( $nick == core::$config->chanserv->nick )
			return false;
		// skip us :D
		
		$hostname = core::get_full_hostname( $nick );
		// generate a hostname
		
		self::on_create( array( $nick => core::$chans[$chan]['users'][$nick] ), chanserv::$chan_q[$chan], false );
		// on_create event
	}
	
	/*
	* on_create (private)
	* 
	* @params
	* $nusers - array from ircd_handle::parse_users()
	* $channel - valid channel array
	* $create - true for create, false for join.
	*/
	static public function on_create( $nusers, $channel, $create = true )
	{
		$new_nusers_give = $new_nusers_take = array();
		$access_array = self::get_access( $channel->channel );
		ksort( $access_array );
		$access_array = array_reverse( $access_array );
		$strict = ( chanserv::check_flags( $channel->channel, array( 'S' ) ) ) ? 'strict:' : ':';
		// get the access array
		
		foreach ( $nusers as $nick => $modes )
		{
			if ( $nick == core::$config->chanserv->nick )
				continue;
			// skip us :D
			
			$hostname = core::get_full_hostname( $nick );
			// get the hostname ready.
			
			$bans_q = database::select( 'chans_levels', array( 'id', 'target', 'setby', 'expire', 'timestamp' ), array( 'expire', '!=', '0', 'AND', 'channel', '=', $channel->channel ) );
			while ( $bans = database::fetch( $bans_q ) )
			{
				if ( ( ( $bans->timestamp + $bans->expire ) - core::$network_time ) <= 0 )
				{
					database::delete( 'chans_levels', array( 'id', '=', $bans->id ) );
					continue;
					// ban has expired delete it and continue
				}
				elseif ( ( ( $bans->timestamp + $bans->expire ) - core::$network_time ) > 0 )
				{
					$expire = ( $bans->timestamp + $bans->expire ) - core::$network_time;
					timer::add( array( 'cs_levels', 'set_flag', array( $bans->setby, $channel->channel, $bans->target, '-b' ) ), $expire, 1 );
					// there is a ban that isnt expired, but has an expiry time, add a timer.
				}
			}
			// is there any expired bans?
			
			if ( $reason = chanserv::check_levels( $nick, $channel->channel, array( 'b' ), true, false, true, false ) )
			{
				mode::set( core::$config->chanserv->nick, $channel->channel, '+b *@'.core::$nicks[$nick]['host'] );
				ircd::kick( core::$config->chanserv->nick, $nick, $channel->channel, $reason );
				continue;
			}
			// check for bans before access
			
			foreach ( $access_array as $target => $level )
			{
				if ( $target == core::$nicks[$nick]['account'] && core::$nicks[$nick]['identified'] && !isset( $new_nusers_give[$nick] ) )
				{
					$new_nusers_give[$nick] .= 'strict:'.implode( '', $level );
				}
				elseif ( strpos( $target, '@' ) !== false && services::match( $hostname, $target ) && !isset( $new_nusers_give[$nick] ) )
				{
					if ( in_array( 1, $level ) )
						$new_nusers_give[$nick] .= ':'.implode( '', $level );
					else
						$new_nusers_give[$nick] .= $strict.implode( '', $level );
				}
				// give them access
				
				if ( isset( $new_nusers_give[$nick] ) || $strict == ':' )
					continue;
				// if they need access just skip
				
				if ( ircd::$owner && strpos( $modes, 'q' ) !== false )
					$new_nusers_take[$nick] .= 'q';
				// they don't have access, but they have +a, remove it
				if ( ircd::$protect && strpos( $modes, 'a' ) !== false )
					$new_nusers_take[$nick] .= 'a';
				// they don't have access, but they have +a, remove it
				if ( strpos( $modes, 'o' ) !== false )
					$new_nusers_take[$nick] .= 'o';
				// they don't have access, but they have +o, remove it
				if ( ircd::$halfop && strpos( $modes, 'h' ) !== false )
					$new_nusers_take[$nick] .= 'h';
			}
			// foreach the access array
		}
		// loop through the users
		
		mode::mass_mode( $channel->channel, '-', $new_nusers_take, core::$config->chanserv->nick );
		// take access from people who shouldn't have it
		self::give_access( $channel->channel, $new_nusers_give, $access_array );
		// give access to people who should have it
	}
	
	/*
	* get_access (private)
	* 
	* @params
	* $channel - valid channel array
	*/
	static public function get_access( $channel )
	{
		$user_flags_q = database::select( 'chans_levels', array( 'id', 'channel', 'target', 'flags' ), array( 'channel', '=', $channel ) );
		// get our flags records
		
		$access_array = array();
		$temp_array = array();
		while ( $flags = database::fetch( $user_flags_q ) )
		{
			if ( ircd::$owner && strpos( $flags->flags, 'q' ) !== false )
				$temp_array[] = 'q';
			
			if ( ircd::$protect && strpos( $flags->flags, 'a' ) !== false )
				$temp_array[] = 'a';
			
			if ( strpos( $flags->flags, 'o' ) !== false )
				$temp_array[] = 'o';
			
			if ( ircd::$halfop && strpos( $flags->flags, 'h' ) !== false && !in_array( 'o', $temp_array ) )
				$temp_array[] = 'h';
			
			if ( strpos( $flags->flags, 'v' ) !== false && ( !in_array( 'o', $temp_array ) && !in_array( 'h', $temp_array ) ) )
				$temp_array[] = 'v';
				
			$access_array[$flags->target] = $temp_array;
			
			$temp_array = array();
		}
		// create an array of the access list.
		
		return $access_array;
	}
	
	/*
	* give_access (private)
	* 
	* @params
	* $chan - The channel to give the user access in
	* $nusers - The users to recieve access
	* $access - a valid resource from an access query.
	*/
	static public function give_access( $chan, $nusers, $chan_access )
	{
		$new_nusers = array();
		// preset a new array
		
		foreach ( $nusers as $nick => $data )
		{
			$parts = explode( ':', $data );
			$level = $parts[1];
			$strict = ( $parts[0] == 'strict' ) ? true : false;
			// determine whether we check strictly or not (secure +S)
			
			if ( $strict && !core::$nicks[$nick]['identified'] )
				continue;
			// else we move on
			
			$new_nusers[$nick] = $level;
		}
		
		mode::mass_mode( $chan, '+', $new_nusers, core::$config->chanserv->nick );
		// new method, saves resources and code! YEAH!!!
		
		unset( $nusers );
	}
	
	/*
	* set_flag (private)
	* 
	* @params
	* $nick - nick of who issues the command
	* $chan - channel in question
	* $target - target in question
	* $flag - +v, +V, -V etc.
	* &$return_data - a valid array from module::$return_data
	* $param - optional
	* $timestamp - optional
	*/
	static public function set_flag( $nick, $chan, $target, $flag, &$return_data, $param = '', $timestamp = 0 )
	{	
		$mode = $flag[0];
		$r_flag = $flag[1];
		// get the real flag, eg. V, v and mode
		
		if ( chanserv::check_levels( $target, $chan, array( $r_flag ), false, false, false, false, false ) )
		{
			$user_flag_q = database::select( 'chans_levels', array( 'id', 'channel', 'target', 'flags' ), array( 'channel', '=', $chan, 'AND', 'target', '=', $target ) );
			
			if ( $mode == '-' )
			{		
				if ( core::$nicks[$nick]['account'] == $target && $r_flag == 'F' )
				{
					$return_data['FALSE_RESPONSE'] = services::parse( chanserv::$help->CS_LEVELS_BAD_FLAG, array( 'flag' => $flag ) );
					return false;
				}
				// someone is trying to de-founder themselves?
				
				if ( strpos( self::$set[$target], '-' ) === false )
					self::$set[$target] .= '-';
				// ok, no - ?
				
				$user_flag = database::fetch( $user_flag_q );
				// get the flag record
					
				$new_user_flags = str_replace( $r_flag, '', $user_flag->flags );
				
				if ( $new_user_flags == '' )
					database::delete( 'chans_levels', array( 'channel', '=', $chan, 'AND', 'target', '=', $target ) );
				else
					database::update( 'chans_levels', array( 'flags' => $new_user_flags, 'timestamp' => core::$network_time, 'setby' => $nick ), array( 'channel', '=', $chan, 'AND', 'target', '=', $target ) );	
				// check if it's empty, if it is just delete the row
				
				self::$set[$target] .= $r_flag;
				// some magic :O
				return true;
			}
			else
			{
				self::$already_set[$target] .= $r_flag;
				// some magic :O
				return false;
			}
			// the user has the flag, so, if it's - remove it, if it is +
			// we send a message back.
		}
		else
		{
			$user_flag_q = database::select( 'chans_levels', array( 'id', 'channel', 'target', 'flags' ), array( 'channel', '=', $chan, 'AND', 'target', '=', $target ) );
			
			if ( $mode == '+' )
			{
				if ( strpos( self::$set[$target], '+' ) === false )
					self::$set[$target] .= '+';
				// ok, no + ?
				
				if ( database::num_rows( $user_flag_q ) > 0 )
				{
					$user_flag = database::fetch( $user_flag_q );
					$new_user_flags = $user_flag->flags.$r_flag;
					
					if ( $r_flag == 'b' && $mode == '+' )
						database::update( 'chans_levels', array( 'flags' => $new_user_flags, 'reason' => $param, 'expire' => $timestamp, 'timestamp' => core::$network_time, 'setby' => $nick ), array( 'channel', '=', $chan, 'AND', 'target', '=', $target ) );
						// update.
					else
						database::update( 'chans_levels', array( 'flags' => $new_user_flags, 'timestamp' => core::$network_time, 'setby' => $nick ), array( 'channel', '=', $chan, 'AND', 'target', '=', $target ) );
						// update.
					
					self::$set[$target] .= $r_flag;
					// some magic :O
					return true;
				}
				else
				{
					if ( $r_flag == 'b' && $mode == '+' )
						database::insert( 'chans_levels', array( 'channel' => $chan, 'target' => $target, 'flags' => $r_flag, 'reason' => $param, 'expire' => $timestamp, 'timestamp' => core::$network_time, 'setby' => $nick ) );
						// insert.
					else
						database::insert( 'chans_levels', array( 'channel' => $chan, 'target' => $target, 'flags' => $r_flag, 'timestamp' => core::$network_time, 'setby' => $nick ) );
						// insert.
					
					self::$set[$target] .= $r_flag;
					// some magic :O
					return true;
				}
			}
			else
			{
				self::$not_set[$target] .= $r_flag;
				// some magic :O
				return false;
			}
			// the user doesn't have the flag, so if it's + add it, if it is -
			// we send a message back, basically the opposite of above.
		}
	}
}

// EOF;