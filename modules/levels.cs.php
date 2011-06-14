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

class cs_levels implements module
{
	
	const MOD_VERSION = '0.0.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $flags;
	// valid flags.
	
	static public $set = array();
	static public $not_set = array();
	static public $already_set = array();
	
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
		modules::init_module( 'cs_levels', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_levels', 'help', chanserv::$help->CS_HELP_LEVELS_1 );
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
		$chan = core::get_chan( $ircdata, 0 );
		$target = $ircdata[2];
		$flags = $ircdata[1];
		$levels_result = chanserv::check_levels( $nick, $chan, array( 'v', 'h', 'o', 'a', 'q', 'r', 'f', 'S', 'F' ) );
		// get the channel.
		
		if ( services::chan_exists( $chan, array( 'channel' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			return false;
		}
		// make sure the channel exists.
		
		if ( $target == '' && $flags == '' && $levels_result )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LEVELS_LIST_TOP, array( 'chan' => $chan ) );
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LEVELS_LIST_DLM );
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
				
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LEVELS_LIST, array( 'num' => $x_s, 'target' => $flags->target, 'flags' => '+'.$false_flag, 'modified' => $modified ) );
				// show the flag
			}
			// loop through them
			
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LEVELS_LIST_DLM );
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LEVELS_LIST_BTM, array( 'chan' => $chan ) );
			// show other help data
			
			return false;
		}
		// no params
		// lets show the current flags.
		else if ( $target == '' && $flags == '' && !$levels_result )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// i don't think they have access to see the channel list..
		
		if ( $target == '' || $flags == '' )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'LEVELS' ) );
			return false;
		}
		// missing params?
		
		if ( services::chan_exists( $chan, array( 'channel' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			return false;
		}
		// make sure the channel exists.
		
		$flag_a = array();
		foreach ( str_split( $flags ) as $pos => $flag )
		{
			if ( strpos( self::$flags, $flag ) === false )
			{
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LEVELS_UNKNOWN, array( 'flag' => $flag ) );
				return false;
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
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_UNREGISTERED_NICK, array( 'nick' => $target ) );
				return false;
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
			// ----------- +k ----------- //
			if ( $flag == 'k' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+k' );
				// +k the target in question
			}
			// ----------- +k ----------- //
			
			// ----------- +v ----------- //
			elseif ( $flag == 'v' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+v' );
				// +v the target in question
			}
			// ----------- +v ----------- //
			
			// ----------- +h ----------- //
			elseif ( $flag == 'h' && ircd::$halfop )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+h' );
				// +h the target in question
			}
			// ----------- +h ----------- //
			
			// ----------- +o ----------- //
			elseif ( $flag == 'o' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'a', 'q', 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+o' );
				// +o the target in question
			}
			// ----------- +o ----------- //
			
			// ----------- +a ----------- //
			elseif ( $flag == 'a' && ircd::$protect )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'q', 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+a' );
				// +a the target in question
			}
			// ----------- +a ----------- //
			
			// ----------- +q ----------- //
			elseif ( $flag == 'q' && ircd::$owner )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+q' );
				// +q the target in question
			}
			// ----------- +q ----------- //
			
			// ----------- +s ----------- //
			elseif ( $flag == 's' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+s' );
				// +s the target in question
			}
			// ----------- +s ----------- //
			
			// ----------- +r ----------- //
			elseif ( $flag == 'r' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+r' );
				// +r the target in question
			}
			// ----------- +r ----------- //
			
			// ----------- +f ----------- //
			elseif ( $flag == 'f' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+f' );
				// +f the target in question
			}
			// ----------- +f ----------- //
			
			// ----------- +t ----------- //
			elseif ( $flag == 't' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+t' );
				// +t the target in question
			}
			// ----------- +t ----------- //
			
			// ----------- +i ----------- //
			elseif ( $flag == 'i' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+i' );
				// +i the target in question
			}
			// ----------- +i ----------- //

			// ----------- +R ----------- //
			elseif ( $flag == 'R' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+R' );
				// +R the target in question
			}
			// ----------- +R ----------- //

			// ----------- +S ----------- //
			elseif ( $flag == 'S' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+S' );
				// +S the target in question
			}
			// ----------- +S ----------- //
			
			// ----------- +F ----------- //
			elseif ( $flag == 'F' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '+F' );
				// +F the target in question
			}
			// ----------- +F ----------- //
			
			// ----------- +b ----------- //
			elseif ( $flag == 'b' )
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
				
				if ( chanserv::check_levels( $nick, $chan, array( 'r', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				if ( self::set_flag( $nick, $chan, $target, '+b', $reason, $expire ) !== false )
				{
					foreach ( core::$chans[$chan]['users'] as $user => $modes )
					{
						$hostname = core::get_full_hostname( $nick );
							
						if ( ( strpos( $mask, '@' ) && services::match( $hostname, $target ) ) || $user == $target )
						{
							if ( chanserv::check_levels( $nick, $channel->channel, array( 'v', 'h', 'o', 'a', 'q', 'F' ) ) )
								continue;
							// don't trigger if they are on the old access list.
							
							ircd::mode( core::$config->chanserv->nick, $chan, '+b *@'.core::$nicks[$user]['host'] );
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
				// +b the target in question
			}
			// ----------- +b ----------- //
		}
		// loop though our plus flags
		
		foreach ( str_split( $flag_array['minus'] ) as $flag )
		{
			// ----------- -k ----------- //
			if ( $flag == 'k' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-k' );
				// -k the target in question
			}
			// ----------- -k ----------- //
			
			// ----------- -v ----------- //
			elseif ( $flag == 'v' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'h', 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-v' );
				// -v the target in question
			}
			// ----------- -v ----------- //
			
			// ----------- -h ----------- //
			elseif ( $flag == 'h' && ircd::$halfop )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'o', 'a', 'q', 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-h' );
				// -h the target in question
			}
			// ----------- -h ----------- //
			
			// ----------- -o ----------- //
			elseif ( $flag == 'o' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'a', 'q', 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-o' );
				// -o the target in question
			}
			// ----------- -o ----------- //
			
			// ----------- -a ----------- //
			elseif ( $flag == 'a' && ircd::$protect )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'q', 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-a' );
				// -a the target in question
			}
			// ----------- -a ----------- //
			
			// ----------- -q ----------- //
			elseif ( $flag == 'q' && ircd::$owner )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'f', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-q' );
				// -q the target in question
			}
			// ----------- -q ----------- //
			
			// ----------- -s ----------- //
			elseif ( $flag == 's' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-s' );
				// -s the target in question
			}
			// ----------- -s ----------- //
			
			// ----------- -r ----------- //
			elseif ( $flag == 'r' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-r' );
				// -r the target in question
			}
			// ----------- -r ----------- //
			
			// ----------- -f ----------- //
			elseif ( $flag == 'f' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-f' );
				// -f the target in question
			}
			// ----------- -f ----------- //
			
			// ----------- -t ----------- //
			elseif ( $flag == 't' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-t' );
				// -t the target in question
			}
			// ----------- -t ----------- //
			
			// ----------- -i ----------- //
			elseif ( $flag == 'i' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-i' );
				// -i the target in question
			}
			// ----------- -i ----------- //

			// ----------- -R ----------- //
			elseif ( $flag == 'R' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-R' );
				// -R the target in question
			}
			// ----------- -R ----------- //
			
			// ----------- -S ----------- //
			elseif ( $flag == 'S' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-S' );
				// -S the target in question
			}
			// ----------- -S ----------- //
			
			// ----------- -F ----------- //
			elseif ( $flag == 'F' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, $target, '-F' );
				// -F the target in question
			}
			// ----------- -F ----------- //
			
			// ----------- -b ----------- //
			elseif ( $flag == 'b' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'r', 'S', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				if ( self::set_flag( $nick, $chan, $target, '-b' ) !== false )
				{
					if ( strpos( $target, '@' ) === false && $user = core::search_nick( $target ) )
						ircd::mode( core::$config->chanserv->nick, $chan, '-b *@'.$user['host'] );
					else
						ircd::mode( core::$config->chanserv->nick, $chan, '-b '.$target );
					// is the hostname in our cache? if not unban it..
				}
				// -b the target in question
			}
			// ----------- -b ----------- //
		}
		// loop through the minus flags
		
		if ( isset( self::$set[$target] ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LEVELS_SET, array( 'target' => $target, 'flag' => self::$set[$target], 'chan' => $chan ) );	
			// who do we notice?
			unset( self::$set[$target] );
		}
		// send back the target stuff..
		
		if ( isset( self::$already_set[$target] ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LEVELS_ALREADY_SET, array( 'target' => $target, 'flag' => self::$already_set[$target], 'chan' => $chan ) );
			unset( self::$already_set[$target] );
		}
		// send back the target stuff..
		
		if ( isset( self::$not_set[$target] ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LEVELS_NOT_SET, array( 'target' => $target, 'flag' => self::$not_set[$target], 'chan' => $chan ) );
			unset( self::$not_set[$target] );
		}
		// send back the target stuff..
	}
	
	/*
	* main (event)
	* 
	* @params
	* $ircdata - ''
	*/
	public function main( $ircdata, $startup = false )
	{
		$populated_chan = ircd::on_chan_create( $ircdata );
		if ( $populated_chan !== false )
		{
			$chans = explode( ',', $populated_chan );
			// the chans
			
			foreach ( $chans as $chan )
			{
				if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
					continue;
				// if the channel doesn't exist we return false, to save us the hassle of wasting
				// resources on this stuff below.
				
				self::on_create( core::$chans[$chan]['users'], $channel, true );
				// on_create event
			}
		}
		// we give out the nessicary access when a channel is created :)
		
		$populated_chan = ircd::on_join( $ircdata );
		if ( $populated_chan !== false )
		{
			$nick = $populated_chan['nick'];
			$chans = explode( ',', $populated_chan['chan'] );
			// get the channel & nick
			
			foreach ( $chans as $chan )
			{
				if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
					continue;
				// if the channel doesn't exist we return false, to save us the hassle of wasting
				// resources on this stuff below.
				
				if ( $nick == core::$config->chanserv->nick )
					continue;
				// skip us :D
				
				$hostname = core::get_full_hostname( $nick );
				// generate a hostname
				
				self::on_create( array( $nick => core::$chans[$chan]['users'][$nick] ), $channel, false );
				// on_create event
			}
		}
		// and the same when someone joins
	}
	
	/*
	* on_create (private)
	* 
	* @params
	* $nusers - array from ircd::parse_users()
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
				ircd::mode( core::$config->chanserv->nick, $channel->channel, '+b *@'.core::$nicks[$nick]['host'] );
				ircd::kick( core::$config->chanserv->nick, $nick, $channel->channel, $reason );
				continue;
			}
			// check for bans before access
			
			foreach ( $access_array as $target => $level )
			{
				if ( $target == $nick && core::$nicks[$nick]['identified'] && !isset( $new_nusers_give[$nick] ) )
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
				$temp_array[] = '5';
			
			if ( ircd::$protect && strpos( $flags->flags, 'a' ) !== false )
				$temp_array[] = '4';
			
			if ( strpos( $flags->flags, 'o' ) !== false )
				$temp_array[] = '3';
			
			if ( ircd::$halfop && strpos( $flags->flags, 'h' ) !== false )
				$temp_array[] = '2';
			
			if ( strpos( $flags->flags, 'v' ) !== false )
				$temp_array[] = '1';
				
			sort( $temp_array, SORT_NUMERIC );
			$temp_array = array_reverse( $temp_array );
			// sort the array and flip it
			
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
			
			if ( ircd::$owner && strpos( core::$chans[$chan]['users'][$nick], 'q' ) === false && strpos( $level, '5' ) !== false )
				$level = str_replace( '5', 'q', $level );
			if ( ircd::$protect && strpos( core::$chans[$chan]['users'][$nick], 'a' ) === false && strpos( $level, '4' ) !== false )
				$level = str_replace( '4', 'a', $level );
			if ( strpos( core::$chans[$chan]['users'][$nick], 'o' ) === false && strpos( $level, '3' ) !== false )
				$level = str_replace( '3', 'o', $level );
			if ( ircd::$halfop && strpos( core::$chans[$chan]['users'][$nick], 'h' ) === false && strpos( $level, '2' ) !== false )
				$level = str_replace( '2', 'h', $level );
			if ( strpos( core::$chans[$chan]['users'][$nick], 'v' ) === false && strpos( $level, '1' ) !== false )
				$level = str_replace( '1', 'v', $level );
			// replace '5' with 'q' etc, where applicable
			
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
	* $param - optional
	* $timestamp - optional
	*/
	static public function set_flag( $nick, $chan, $target, $flag, $param = '', $timestamp = 0 )
	{	
		$mode = $flag[0];
		$r_flag = $flag[1];
		// get the real flag, eg. V, v and mode
		
		if ( chanserv::check_levels( $target, $chan, array( $r_flag ), false, false, false, false ) )
		{
			$user_flag_q = database::select( 'chans_levels', array( 'id', 'channel', 'target', 'flags' ), array( 'channel', '=', $chan, 'AND', 'target', '=', $target ) );
			
			if ( $mode == '-' )
			{		
				if ( $nick == $target && $r_flag == 'F' )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LEVELS_BAD_FLAG, array( 'flag' => $flag ) );
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