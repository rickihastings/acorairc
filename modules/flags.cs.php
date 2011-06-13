<?php

/*
* Acora IRC Services
* modules/flags.cs.php: ChanServ flags module
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

class cs_flags implements module
{
	
	const MOD_VERSION = '0.0.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $flags;
	static public $p_flags;
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
		modules::init_module( 'cs_flags', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_flags', 'help', chanserv::$help->CS_HELP_FLAGS_1 );
		chanserv::add_help( 'cs_flags', 'help flags', chanserv::$help->CS_HELP_FLAGS_ALL );
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
		$chan = core::get_chan( $ircdata, 0 );
		$flags = $ircdata[1];
		$param = core::get_data_after( $ircdata, 2 );
		$rparams = explode( '||', $param );
		$levels_result = chanserv::check_levels( $nick, $chan, array( 's', 'S', 'F' ) );
		// get the channel.
		
		if ( $chan == '' || $chan[0] != '#' )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'FLAGS' ) );
			return false;
			// wrong syntax
		}
		// make sure they've entered a channel
		
		if ( services::chan_exists( $chan, array( 'channel' ) ) === false )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			return false;
		}
		// make sure the channel exists.
		
		if ( $target == '' && $flags == '' && $levels_result )
		{
			$flags_q = database::select( 'chans_flags', array( 'channel', 'flags' ), array( 'channel', '=', $chan ) );
			$flags_q = database::fetch( $flags_q );
			// get the flag records
			
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_FLAGS_LIST, array( 'chan' => $chan, 'flags' => $flags_q->flags ) );
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_FLAGS_LIST2, array( 'chan' => $chan ) );
			return false;
		}
		else if ( $target == '' && $flags == '' && !$levels_result )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// i don't think they have access to see the channel flags..
		// missing params?
		
		$flag_a = array();
		foreach ( str_split( $flags ) as $flag )
		{
			if ( strpos( self::$flags, $flag ) === false )
			{
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_FLAGS_UNKNOWN, array( 'flag' => $flag ) );
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
		
		$flags = '';
		foreach ( $flag_a as $flag => $count )
			$flags .= $flag;
		// reconstruct the flags
		
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
		
		foreach ( str_split( $flag_array['plus'] ) as $flag )
		{
			// paramtized flags (lowercase) ones come first
			
			// ----------- +d ----------- //
			if ( $flag == 'd' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '+S', '' );
				// +S the target in question
			}
			// ----------- +S ----------- //
			
			// ----------- +F ----------- //
			elseif ( $flag == 'F' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '+F', '' );
				// +F the target in question
			}
			// ----------- +F ----------- //
			
			// ----------- +G ----------- //
			elseif ( $flag == 'G' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				if ( self::set_flag( $nick, $chan, '+G', '' ) !== false && count( core::$chans[$chan]['users'] ) > 0 )
				{
					ircd::join_chan( core::$config->chanserv->nick, $chan );
					// join the chan.
					
					if ( ircd::$protect )
						ircd::mode( core::$config->chanserv->nick, $chan, '+ao '.core::$config->chanserv->nick.' '.core::$config->chanserv->nick, true );
						// +ao its self.
					else
						ircd::mode( core::$config->chanserv->nick, $chan, '+o '.core::$config->chanserv->nick, true );
						// +o its self.
				}
				// only join if channel has above 0 users in it.
				// +G the target in question
			}
			// ----------- +G ----------- //
			
			// ----------- +T ----------- //
			elseif ( $flag == 'T' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '+T', '' );
				// +F the target in question
			}
			// ----------- +T ----------- //
			
			// ----------- +K ----------- //
			elseif ( $flag == 'K' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '+K', '' );
				// +K the target in question
			}
			// ----------- +K ----------- //
			
			// ----------- +L ----------- //
			elseif ( $flag == 'L' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				if ( self::set_flag( $nick, $chan, '+L', '' ) != false )
				{
					self::increase_limit( $chan );
					// execute it directly.
				}
				// +L the target in question
			}
			// ----------- +L ----------- //
			
			// ----------- +I ----------- //
			elseif ( $flag == 'I' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				if ( self::set_flag( $nick, $chan, '+I', '' ) != false )
				{
					foreach ( core::$chans[$chan]['users'] as $unick => $mode )
					{
						if ( chanserv::check_levels( $unick, $chan, array( 'k', 'q', 'a', 'o', 'h', 'v', 'S', 'F' ), true, false ) === false )
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
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
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '-S', '' );
				// +S the target in question
			}
			// ----------- -S ----------- //
			
			// ----------- -F ----------- //
			elseif ( $flag == 'F' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '-F', '' );
				// -F the target in question
			}
			// ----------- -F ----------- //
			
			// ----------- -G ----------- //
			elseif ( $flag == 'G' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				if ( self::set_flag( $nick, $chan, '-G', '' ) != false )
				{
					ircd::part_chan( core::$config->chanserv->nick, $chan );
					// leave the channel
				}
				// -G the target in question
			}
			// ----------- -G ----------- //
			
			// ----------- -T ----------- //
			elseif ( $flag == 'T' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '-T', '' );
				// -T the target in question
			}
			// ----------- -T ----------- //
			
			// ----------- -K ----------- //
			elseif ( $flag == 'K' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '-K', '' );
				// -K the target in question
			}
			// ----------- -K ----------- //
			
			// ----------- -L ----------- //
			elseif ( $flag == 'L' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				if ( self::set_flag( $nick, $chan, '-L', '' ) != false )
				{
					ircd::mode( core::$config->chanserv->nick, $chan, '-l' );
					// -l the channel
				}
				// -L the target in question
			}
			// ----------- -L ----------- //
			
			// ----------- -I ----------- //
			elseif ( $flag == 'I' )
			{
				if ( $levels_result === false )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
					return false;
				}
				// do they have access to alter this?
				
				self::set_flag( $nick, $chan, '-I', '' );
				// -I the target in question
			}
			// ----------- -I ----------- //
		}
		// loop through the flags being unset, and do what we need to do with them.
		
		if ( isset( self::$set[$chan] ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_FLAGS_SET, array( 'flag' => self::$set[$chan], 'chan' => $chan ) );	
			unset( self::$set[$chan] );
		}
		// send back the target stuff..
		
		if ( isset( self::$already_set[$chan] ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_FLAGS_ALREADY_SET, array( 'flag' => self::$already_set[$chan], 'chan' => $chan ) );
			unset( self::$already_set[$chan] );
		}
		// send back the target stuff..
		
		if ( isset( self::$not_set[$chan] ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_FLAGS_NOT_SET, array( 'flag' => self::$not_set[$chan], 'chan' => $chan ) );
			unset( self::$not_set[$chan] );
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
		$return = ircd::on_mode( $ircdata );
		if ( $return !== false )
		{
			$nick = $return['nick'];
			$chan = $return['chan'];
			$mode_queue = $return['modes'];
			
			if ( strpos( $nick, '.' ) !== false && strstr(core::$config->server->ircd, 'inspircd') )
				$server = $nick;
			elseif ( strlen( $nick ) == 3 && strstr(core::$config->server->ircd, 'inspircd') )
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
				$nmodelock = explode( ' ', $modelock );
				
				foreach ( str_split( $nmodelock[0] ) as $mode )
				{
					if ( strstr( $mode_queue, $mode ) )
						ircd::mode( core::$config->chanserv->nick, $chan, $modelock );
					// reset the modes
				}
			}
			// modelock?
		}
		// we need to check for any modechanges here, for modelocking
		
		$return = ircd::on_part( $ircdata );
		if ( $return !== false )
		{
			$chan = $return['chan'];
			// get the channel
			
			if ( chanserv::check_flags( $chan, array( 'L' ) ) )
			{
				timer::add( array( 'cs_flags', 'increase_limit', array( $chan ) ), 10, 1 );
				// add a timer to update the limit, in 15 seconds
			}
			// is there auto-limit enabled?
		}
		// on part we check for
		
		if ( ircd::on_quit( $ircdata ) !== false )
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
		
		$populated_chan = ircd::on_join( $ircdata );
		if ( $populated_chan !== false )
		{
			$nick = $ircdata[0];
			$chans = explode( ',', $populated_chan );
			// find the nick & chan
			
			foreach ( $chans as $chan )
			{
				if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
					return false;	
				// channel isnt registered
				
				if ( chanserv::check_flags( $chan, array( 'I' ) ) )
				{
					if ( chanserv::check_levels( $nick, $chan, array( 'k', 'q', 'a', 'o', 'h', 'v', 'S', 'F' ), true, false ) === false )
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
		
		$populated_chan = ircd::on_chan_create( $ircdata );
		if ( $populated_chan !== false )
		{
			$chans = explode( ',', $populated_chan );
			// chan
			
			foreach ( $chans as $chan )
			{
				$nusers = core::$chans[$chan]['users'];
				
				if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
					return false;	
				// channel isnt registered
				
				if ( chanserv::check_flags( $chan, array( 'I' ) ) )
				{
					foreach ( $nusers as $nick => $mode )
					{
						if ( chanserv::check_levels( $nick, $chan, array( 'k', 'q', 'a', 'o', 'h', 'v', 'S', 'F' ), true, false ) === false )
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
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_FLAGS_NEEDS_PARAM, array( 'flag' => $flag ) );
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
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_FLAGS_INVALID_E, array( 'flag' => $flag ) );
				return false;
			}
			// is the email invalid?
			
			if ( $r_flag == 't' && strpos( $param, '*' ) === false )
			{
				services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_FLAGS_INVALID_T, array( 'flag' => $flag ) );
				return false;
			}
			// is the topicmask invalid?
			
			if ( $r_flag == 'm' )
			{
				$mode_string = explode( ' ', $param );
				
				if ( strstr( $mode_string[0], 'r' ) || strstr( $mode_string[0], 'q' ) || strstr( $mode_string[0], 'a' ) || strstr( $mode_string[0], 'o' ) || strstr( $mode_string[0], 'h' ) || strstr( $mode_string[0], 'v' ) || strstr( $mode_string[0], 'b' ) )
				{
					services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_FLAGS_INVALID_M, array( 'flag' => $flag ) );
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
				if ( strpos( self::$set[$chan], '-' ) === false )
					self::$set[$chan] .= '-';
				// ok, no + ?
				
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
				
				self::$set[$chan] .= $r_flag;
				// some magic :O
				return true;
			}
			
			if ( $mode == '+' )
			{
				if ( !in_array( $r_flag, str_split( self::$p_flags ) ) )
				{
					self::$already_set[$chan] .= $r_flag;
					// some magic :O
					return false;
				}
				
				if ( strpos( self::$set[$chan], '+' ) === false )
					self::$set[$chan] .= '+';
				// ok, no + ?
				
				$chan_flag = database::fetch( $chan_flag_q );
				// get the flag record
				
				database::update( 'chans_flags', array( $param_field => $param ), array( 'channel', '=', $chan ) );	
				// update the row with the new flags.
				
				self::$set[$chan] .= $r_flag;
				// some magic :O
				return true;
			}
			// the flag IS set, so now we check whether they are trying to -, or + it
			// if they are trying to - it, go ahead, error if they are trying to + it.
		}
		else
		{
			$chan_flag_q = database::select( 'chans_flags', array( 'id', 'channel', 'flags' ), array( 'channel', '=', $chan ) );
			
			if ( $mode == '+' )
			{
				if ( strpos( self::$set[$chan], '+' ) === false )
					self::$set[$chan] .= '+';
				// ok, no + ?
				
				$chan_flag = database::fetch( $chan_flag_q );
				// get the flag record
				
				$new_chan_flags = $chan_flag->flags.$r_flag;
				
				if ( !in_array( $r_flag, str_split( self::$p_flags ) ) )
				{
					database::update( 'chans_flags', array( 'flags' => $new_chan_flags ), array( 'channel', '=', $chan ) );	
					// update the row with the new flags.
					
					self::$set[$chan] .= $r_flag;
					// some magic :O
					return true;
				}
				else
				{
					database::update( 'chans_flags', array( 'flags' => $new_chan_flags, $param_field => $param ), array( 'channel', '=', $chan ) );	
					// update the row with the new flags.
					
					self::$set[$chan] .= $r_flag;
					// some magic :O
					return true;
				}
			}
			// the flag ISNT set, so now we check whether they are trying to -, or + it
			// if they are trying to + it, go ahead, error if they are trying to - it.
			
			if ( $mode == '-' )
			{
				self::$not_set[$chan] .= $r_flag;
				// some magic :O
				return false;
			}
		}
		// check if the flag is already set?
	}
}

// EOF;