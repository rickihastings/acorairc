<?php

/*
* Acora IRC Services
* modules/flags.cs.php: ChanServ flags module
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

class cs_flags implements module
{
	
	const MOD_VERSION = '0.0.2';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $flags;
	static public $p_flags;
	// valid flags.
	
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
		modules::init_module( 'cs_flags', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_flags', 'help', &chanserv::$help->CS_HELP_FLAGS_1 );
		chanserv::add_help( 'cs_flags', 'help flags', &chanserv::$help->CS_HELP_FLAGS_ALL );
		// add the help
		
		chanserv::add_command( 'flags', 'cs_flags', 'flags_command' );
		// add the command
		
		self::$flags = '+-duewmtSFGTKLI';
		self::$p_flags = 'duewmt';
		// flags WITH parameters
	}
	
	/*
	* flags_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	* $announce - If set to true, the channel will be noticed.
	*/
	static public function flags_command( $nick, $ircdata = array(), $announce = false )
	{
		$chan = core::get_chan( &$ircdata, 0 );
		$flags = $ircdata[1];
		$param = core::get_data_after( &$ircdata, 2 );
		$rparams = explode( '||', $param );
		// get the channel.
		
		if ( services::chan_exists( $chan, array( 'channel' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			return false;
		}
		// make sure the channel exists.
		
		if ( $flags == '' )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'FLAGS' ) );
			return false;
		}
		// missing params?
		
		foreach ( str_split( $flags ) as $flag )
		{
			if ( strpos( self::$flags, $flag ) === false )
			{
				services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_FLAGS_UNKNOWN, array( 'flag' => $flag ) );
				return false;
			}
			// flag is invalid.
		}
		// check if the flag is valid
		
		$flag_array = mode::sort_modes( $flags, false );
		// sort our flags up
		
		foreach ( str_split( self::$p_flags ) as $flag )
		{
			$param_num = strpos( $flag_array['plus'], $flag );
			
			if ( $param_num !== false )
				$params[$flag] = trim( $rparams[$param_num] );
			// we do!
		}
		// check if we have any paramtized flags, eg +mw
		
		$presult = '';
		$mresult = '';
		
		foreach ( str_split( $flag_array['plus'] ) as $flag )
		{
			// paramtized flags (lowercase) ones come first
			
			// ----------- +d ----------- //
			if ( $flag == 'd' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '+d', $params['d'] );
				// +d the target in question
			}
			// ----------- +d ----------- //
			
			// ----------- +u ----------- //
			elseif ( $flag == 'u' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '+u', $params['u'] );
				// +u the target in question
			}
			// ----------- +u ----------- //
			
			// ----------- +e ----------- //
			elseif ( $flag == 'e' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '+e', $params['e'] );
				// +e the target in question
			}
			// ----------- +e ----------- //
			
			// ----------- +w ----------- //
			elseif ( $flag == 'w' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '+w', $params['w'] );
				// +w the target in question
			}
			// ----------- +w ----------- //
			
			// ----------- +m ----------- //
			elseif ( $flag == 'm' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '+m', $params['m'] );
				// +m the target in question
			}
			// ----------- +m ----------- //
			
			// ----------- +t ----------- //
			elseif ( $flag == 't' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '+t', $params['t'] );
				// +t the target in question
			}
			// ----------- +t ----------- //
			
			// non paramtized modes go here.
			
			// ----------- +S ----------- //
			elseif ( $flag == 'S' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$presult .= self::set_flag( $nick, $chan, '+S', '' );
				// +S the target in question
			}
			// ----------- +S ----------- //
			
			// ----------- +F ----------- //
			elseif ( $flag == 'F' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$presult .= self::set_flag( $nick, $chan, '+F', '' );
				// +F the target in question
			}
			// ----------- +F ----------- //
			
			// ----------- +G ----------- //
			elseif ( $flag == 'G' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$rresult = self::set_flag( $nick, $chan, '+G', '' );
				
				if ( $rresult !== false && count( core::$chans[$chan]['users'] ) > 0 )
				{
					$presult .= $rresult;
					
					ircd::join_chan( core::$config->chanserv->nick, $chan );
					// join the chan.
					
					if ( ircd::$protect )
						ircd::mode( core::$config->chanserv->nick, $chan, '+ao '.core::$config->chanserv->nick.' '.core::$config->chanserv->nick );
						// +ao its self.
					else
						ircd::mode( core::$config->chanserv->nick, $chan, '+o '.core::$config->chanserv->nick );
						// +o its self.
				}
				// only join if channel has above 0 users in it.
				// +G the target in question
			}
			// ----------- +G ----------- //
			
			// ----------- +T ----------- //
			elseif ( $flag == 'T' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$presult .= self::set_flag( $nick, $chan, '+T', '' );
				// +F the target in question
			}
			// ----------- +T ----------- //
			
			// ----------- +K ----------- //
			elseif ( $flag == 'K' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$presult .= self::set_flag( $nick, $chan, '+K', '' );
				// +K the target in question
			}
			// ----------- +K ----------- //
			
			// ----------- +L ----------- //
			elseif ( $flag == 'L' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$rresult = self::set_flag( $nick, $chan, '+L', '' );
				
				if ( $rresult != false )
				{
					$presult .= $rresult;
					
					self::increase_limit( $chan );
					// execute it directly.
				}
				// +L the target in question
			}
			// ----------- +L ----------- //
			
			// ----------- +I ----------- //
			elseif ( $flag == 'I' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$rresult = self::set_flag( $nick, $chan, '+I', '' );
				
				if ( $rresult != false )
				{
					$presult .= $rresult;
					
					foreach ( core::$chans[$chan]['users'] as $unick => $mode )
					{
						if ( chanserv::check_levels( $unick, $chan, array( 'k', 'q', 'a', 'o', 'h', 'v', 'F' ), true, false ) === false )
						{
							ircd::mode( core::$config->chanserv->nick, $chan, '+b *@'.core::$nicks[$unick]['host'] );
							ircd::kick( core::$config->chanserv->nick, $unick, $chan, '+k only channel.' );
						}
						// they don't have +k, KICKEM
					}
				}
				// +I the target in question
			}
			// ----------- +I ----------- //
		}
		// loop through the flags being set, and do what we need to do with them.
		
		foreach ( str_split( $flag_array['minus'] ) as $flag )
		{
			// paramtized flags (lowercase) ones come first
			
			// ----------- -d ----------- //
			if ( $flag == 'd' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '-d', $params['d'] );
				// -d the target in question
			}
			// ----------- -d ----------- //
			
			// ----------- -u ----------- //
			elseif ( $flag == 'u' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '-u', $params['u'] );
				// -u the target in question
			}
			// ----------- -u ----------- //
			
			// ----------- -e ----------- //
			elseif ( $flag == 'e' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '-e', $params['e'] );
				// -e the target in question
			}
			// ----------- -e ----------- //
			
			// ----------- -w ----------- //
			elseif ( $flag == 'w' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '-w', $params['w'] );
				// -w the target in question
			}
			// ----------- -w ----------- //
			
			// ----------- -m ----------- //
			elseif ( $flag == 'm' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '-m', $params['m'] );
				// -m the target in question
			}
			// ----------- -m ----------- //
			
			// ----------- -t ----------- //
			elseif ( $flag == 't' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '-t', $params['t'] );
				// -t the target in question
			}
			// ----------- -t ----------- //
			
			// non paramatized modes go here
			
			// ----------- -S ----------- //
			elseif ( $flag == 'S' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$mresult .= self::set_flag( $nick, $chan, '-S', '' );
				// +S the target in question
			}
			// ----------- -S ----------- //
			
			// ----------- -F ----------- //
			elseif ( $flag == 'F' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$mresult .= self::set_flag( $nick, $chan, '-F', '' );
				// -F the target in question
			}
			// ----------- -F ----------- //
			
			// ----------- -G ----------- //
			elseif ( $flag == 'G' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$rresult = self::set_flag( $nick, $chan, '-G', '' );
				
				if ( $rresult != false )
				{
					$mresult .= $rresult;
					
					ircd::part_chan( core::$config->chanserv->nick, $chan );
					// leave the channel
				}
				// -G the target in question
			}
			// ----------- -G ----------- //
			
			// ----------- -T ----------- //
			elseif ( $flag == 'T' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$mresult .= self::set_flag( $nick, $chan, '-T', '' );
				// -T the target in question
			}
			// ----------- -T ----------- //
			
			// ----------- -K ----------- //
			elseif ( $flag == 'K' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$mresult .= self::set_flag( $nick, $chan, '-K', '' );
				// -K the target in question
			}
			// ----------- -K ----------- //
			
			// ----------- -L ----------- //
			elseif ( $flag == 'L' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$rresult = self::set_flag( $nick, $chan, '-L', '' );
				
				if ( $rresult != false )
				{
					$mresult .= $rresult;
					
					ircd::mode( core::$config->chanserv->nick, $chan, '-l' );
					// -l the channel
				}
				// -L the target in question
			}
			// ----------- -L ----------- //
			
			// ----------- -I ----------- //
			elseif ( $flag == 'I' )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 's', 'F' ) ) === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				$mresult .= self::set_flag( $nick, $chan, '-I', '' );
				// -I the target in question
			}
			// ----------- -I ----------- //
		}
		// loop through the flags being unset, and do what we need to do with them.
		
		if ( $mresult != '' || $presult != '' )
		{
			$result = '';
			
			if ( $presult != '' )
				$result = '+'.$presult;
			if ( $mresult != '' )
				$result = '-'.$mresult;
			// prepend with +/-
			
			if ( $announce )
				services::communicate( core::$config->chanserv->nick, $chan, &chanserv::$help->CS_FLAGS_SET_CHAN, array( 'target' => $target, 'flag' => $result, 'nick' => $nick ) );
			else
				services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_FLAGS_SET, array( 'target' => $target, 'flag' => $result, 'chan' => $chan ) );	
			// who do we notice?
		}
		// send the results
	}
	
	/*
	* main (event)
	* 
	* @params
	* $ircdata - ''
	*/
	public function main( &$ircdata, $startup = false )
	{
		if ( ircd::on_mode( &$ircdata ) )
		{
			$nick = core::get_nick( &$ircdata, 0 );
			$chan = core::get_chan( &$ircdata, 2 );
			$mode_queue = core::get_data_after( &$ircdata, 4 );
			
			if ( strpos( $nick, '.' ) !== false && core::$config->server->ircd != 'inspircd12' )
				$server = $nick;
			elseif ( strlen( $nick ) == 3 && core::$config->server->ircd == 'inspircd12' )
				$server = core::$servers[$nick]['sid'];
				// UNTESTED
			else
				$server = '';
			// we've found a.in nick, which means it's a server? And it's NOT insp1.2
			// OR we've noticed $nick is 3 chars long, which is a SID and it's insp1.2
			
			if ( $server == core::$config->ulined_servers || ( is_array( core::$config->ulined_servers ) && in_array( $server, core::$config->ulined_servers ) ) )
				return false;
			// ignore mode changing from ulined servers.
			
			if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
				return false;	
			// channel isnt registered
			
			$modelock = chanserv::get_flags( $chan, 'm' );
			// get the modelock
			
			if ( $modelock != null )
			{
				$modelock = explode( ' ', $modelock );
				
				foreach ( str_split( $modelock[0] ) as $mode )
				{
					if ( strstr( $mode_queue, $mode ) )
						ircd::mode( core::$config->chanserv->nick, $chan, $modelock );
					// reset the modes
				}
			}
			// modelock?
		}
		// we need to check for any modechanges here, for modelocking
		
		if ( ircd::on_part( &$ircdata ) )
		{
			$chan = core::get_chan( &$ircdata, 2 );
			// get the channel
			
			if ( chanserv::check_flags( $chan, array( 'L' ) ) )
			{
				timer::add( array( 'cs_flags', 'increase_limit', array( $chan ) ), 10, 1 );
				// add a timer to update the limit, in 15 seconds
			}
			// is there auto-limit enabled?
		}
		// on part we check for
		
		if ( ircd::on_quit( &$ircdata ) )
		{
			foreach ( core::$chans as $chan => $data )
			{
				if ( chanserv::check_flags( $chan, array( 'L' ) ) )
				{
					timer::add( array( 'cs_flags', 'increase_limit', array( $chan ) ), 10, 1 );
					// add a timer to update the limit, in 15 seconds
				}
				// is there auto-limit enabled?
			}
		}
		// on part we check for
		
		if ( ircd::on_join( &$ircdata ) )
		{
			$nick = core::get_nick( &$ircdata, 0 );
			$chans = explode( ',', $ircdata[2] );
			// find the nick & chan
			
			foreach ( $chans as $chan )
			{
				if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
					return false;	
				// channel isnt registered
				
				if ( chanserv::check_flags( $chan, array( 'I' ) ) )
				{
					if ( chanserv::check_levels( $nick, $chan, array( 'k', 'q', 'a', 'o', 'h', 'v', 'F' ), true, false ) === false )
					{
						ircd::mode( core::$config->chanserv->nick, $chan, '+b *@'.core::$nicks[$nick]['host'] );
						ircd::kick( core::$config->chanserv->nick, $nick, $chan, '+k only channel.' );
						return false;
					}
					// they don't have +k, KICKEM
				}
				// is the channel +I, eg, +k users only?
				
				$welcome = chanserv::get_flags( $chan, 'w' );
				// get the welcome msg
				
				if ( $welcome != null )
				{
					ircd::notice( core::$config->chanserv->nick, $nick, '('.$chan.') '.$welcome );
					// we give them the welcome msg
				}
				// is there any welcome msg? notice it to them
				
				if ( chanserv::check_flags( $chan, array( 'L' ) ) )
				{
					timer::add( array( 'cs_flags', 'increase_limit', array( $chan ) ), 10, 1 );
					// add a timer to update the limit, in 15 seconds
				}
				// is there auto-limit enabled?
			}
		}
		// on_join entry msg
		// this is just a basic JOIN trigger
		
		if ( ircd::on_chan_create( &$ircdata ) )
		{
			$chans = explode( ',', $ircdata[2] );
			// chan
			
			foreach ( $chans as $chan )
			{
				$nusers_str = implode( ' ', $ircdata );
				$nusers_str = explode( ':', $nusers_str );
				// right here we need to find out where the thing is
				$nusers = ircd::parse_users( $chan, $nusers_str, 1 );
				
				if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
					return false;	
				// channel isnt registered
				
				if ( chanserv::check_flags( $chan, array( 'I' ) ) )
				{
					foreach ( $nusers as $nick => $mode )
					{
						if ( chanserv::check_levels( $nick, $chan, array( 'k', 'q', 'a', 'o', 'h', 'v', 'F' ), true, false ) === false )
						{
							ircd::mode( core::$config->chanserv->nick, $chan, '+b *@'.core::$nicks[$nick]['host'] );
							ircd::kick( core::$config->chanserv->nick, $nick, $chan, '+k only channel.' );
						}
						// they don't have +k, KICKEM
					}
				}
				// is the channel +I, eg, +k users only?
				
				$welcome = chanserv::get_flags( $chan, 'w' );
				// get the welcome msg
				
				if ( $welcome != null )
				{
					foreach ( $nusers as $nick => $mode )
					{
						if ( $nick == core::$config->chanserv->nick ) continue;
						// skip if it's chanserv
						ircd::notice( core::$config->chanserv->nick, $nick, '('.$chan.') '.$welcome );
						// we give them the entrymsg
					}
				}
				// check for a welcome msg, if so
				// message it to the joining users.
				
				if ( chanserv::check_flags( $chan, array( 'L' ) ) )
				{
					cs_flags::increase_limit( $chan, 1 );
					// add a timer to update the limit, in 15 seconds
				}
				// is there auto-limit enabled?
			}
		}
		// on channel create, we send out the welcome message
		// if there is one.
	}
	
	/*
	* increase_limit (private)
	*
	* @params
	* $chan - channel to increase limit
	* $force - forces a number onto the limit, eg 1 will make it users + 2 + 1
	*/
	static public function increase_limit( $chan, $force = 0 )
	{
		$current_users = count( core::$chans[$chan]['users'] );
		$new_limit = $current_users + 2 + $force;
		// plus 3
		
		ircd::mode( core::$config->chanserv->nick, $chan, '+l '.$new_limit );
		// mode change.
	}
	
	/*
	* set_flag (private)
	* 
	* @params
	* $nick - nick
	* $chan - channel
	* $flag - flag
	* $param - optional flag parameter.
	*/
	static public function set_flag( $nick, $chan, $flag, $param )
	{
		$mode = $flag[0];
		$r_flag = $flag[1];
		// get the real flag, eg. V, v and mode
		
		if ( in_array( $r_flag, str_split( self::$p_flags ) ) && $param == '' && $mode == '+' )
		{
			services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_FLAGS_NEEDS_PARAM, array( 'flag' => $flag ) );
			return false;
		}
		// are they issuing a flag, that HAS to have a parameter?
		// only if mode is + and parameter is empty.
		
		if ( $r_flag == 'd' )
			$param_field = 'desc';
		if ( $r_flag == 'u' )
			$param_field = 'url';
		if ( $r_flag == 'e' )
			$param_field = 'email';
		if ( $r_flag == 'w' )
			$param_field = 'welcome';
		if ( $r_flag == 'm' )
			$param_field = 'modelock';
		if ( $r_flag == 't' )
			$param_field = 'topicmask';
		// translate. some craq.
		
		if ( in_array( $r_flag, str_split( self::$p_flags ) ) && $mode == '+' )
		{
			if ( $r_flag == 'e' && services::valid_email( $param ) === false )
			{
				services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_FLAGS_INVALID_E, array( 'flag' => $flag ) );
				return false;
			}
			// is the email invalid?
			
			if ( $r_flag == 't' && strpos( $param, '*' ) === false )
			{
				services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_FLAGS_INVALID_T, array( 'flag' => $flag ) );
				return false;
			}
			// is the topicmask invalid?
			
			if ( $r_flag == 'm' )
			{
				$mode_string = explode( ' ', $param );
				
				if ( strstr( $mode_string[0], 'r' ) || strstr( $mode_string[0], 'q' ) || strstr( $mode_string[0], 'a' ) || strstr( $mode_string[0], 'o' ) || strstr( $mode_string[0], 'h' ) || strstr( $mode_string[0], 'v' ) || strstr( $mode_string[0], 'b' ) )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_FLAGS_INVALID_M, array( 'flag' => $flag ) );
					return false;
				}
			}
			// is the modelock invalid?
		}
		// check for invalid values
		
		if ( chanserv::check_flags( $chan, array( $r_flag ) ) )
		{
			$chan_flag_q = database::select( 'chans_flags', array( 'id', 'channel', 'flags' ), array( 'channel', '=', $chan ) );
			
			if ( $mode == '-' )
			{
				$chan_flag = database::fetch( $chan_flag_q );
				// get the flag record
				
				$new_chan_flags = str_replace( $r_flag, '', $chan_flag->flags );
				
				if ( in_array( $r_flag, str_split( self::$p_flags ) ) )
				{
					database::update( 'chans_flags', array( 'flags' => $new_chan_flags, $param_field => $param ), array( 'channel', '=', $chan ) );	
					// update the row with the new flags.
				}
				else
				{
					database::update( 'chans_flags', array( 'flags' => $new_chan_flags ), array( 'channel', '=', $chan ) );	
					// update the row with the new flags.
				}
				
				return $r_flag;
			}
			
			if ( $mode == '+' )
			{
				if ( !in_array( $r_flag, str_split( self::$p_flags ) ) )
				{
					services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_FLAGS_ALREADY_SET, array( 'flag' => $flag, 'chan' => $chan ) );
					return false;
				}
				
				$chan_flag = database::fetch( $chan_flag_q );
				// get the flag record
				
				database::update( 'chans_flags', array( $param_field => $param ), array( 'channel', '=', $chan ) );	
				// update the row with the new flags.
				
				return $r_flag;
			}
			// the flag IS set, so now we check whether they are trying to -, or + it
			// if they are trying to - it, go ahead, error if they are trying to + it.
		}
		else
		{
			$chan_flag_q = database::select( 'chans_flags', array( 'id', 'channel', 'flags' ), array( 'channel', '=', $chan ) );
			
			if ( $mode == '+' )
			{
				$chan_flag = database::fetch( $chan_flag_q );
				// get the flag record
				
				$new_chan_flags = $chan_flag->flags.$r_flag;
				
				if ( !in_array( $r_flag, str_split( self::$p_flags ) ) )
				{
					database::update( 'chans_flags', array( 'flags' => $new_chan_flags ), array( 'channel', '=', $chan ) );	
					// update the row with the new flags.
					
					return $r_flag;
				}
				else
				{
					database::update( 'chans_flags', array( 'flags' => $new_chan_flags, $param_field => $param ), array( 'channel', '=', $chan ) );	
					// update the row with the new flags.
					
					return $r_flag;
				}
			}
			// the flag ISNT set, so now we check whether they are trying to -, or + it
			// if they are trying to + it, go ahead, error if they are trying to _ it.
			
			if ( $mode == '-' )
			{
				services::communicate( core::$config->chanserv->nick, $nick, &chanserv::$help->CS_FLAGS_NOT_SET, array( 'flag' => $flag, 'chan' => $chan ) );
				return false;
			}
		}
		// check if the flag is already set?
	}
}

// EOF;