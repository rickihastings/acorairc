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

class cs_flags extends module
{
	
	const MOD_VERSION = '0.1.7';
	const MOD_AUTHOR = 'Acora';
	// module info
	
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
		modules::init_module( __CLASS__, self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		commands::add_help( 'chanserv', 'cs_flags', 'help', chanserv::$help->CS_HELP_FLAGS_1, true );
		commands::add_help_fix( 'chanserv', 'cs_flags', 'prefix', 'help flags', chanserv::$help->CS_HELP_FLAGS_ALL_PRE );
		commands::add_help_fix( 'chanserv', 'cs_flags', 'suffix', 'help flags', chanserv::$help->CS_HELP_FLAGS_ALL_SUF );
		// add the help
		
		commands::add_command( 'chanserv', 'flags', 'cs_flags', 'flags_command' );
		// add the command

		$level_structure = array( 'array' => &chanserv::$levels, 'module' => __CLASS__, 'command' => array( 'help levels' ), 'type' => 'cslevels' );
		services::add_flag( $level_structure, 's', chanserv::$help->CS_LEVELS_s, null, null, array( 'S', 'F' ) );
		// add our access flags
		
		$flag_structure = array( 'array' => &chanserv::$flags, 'module' => __CLASS__, 'command' => array( 'help flags' ), 'type' => 'csflags' );
		services::add_flag( $flag_structure, 'd', chanserv::$help->CS_FLAGS_d );
		services::add_flag( $flag_structure, 'u', chanserv::$help->CS_FLAGS_u );
		services::add_flag( $flag_structure, 'e', chanserv::$help->CS_FLAGS_e );
		services::add_flag( $flag_structure, 'w', chanserv::$help->CS_FLAGS_w );
		services::add_flag( $flag_structure, 'm', chanserv::$help->CS_FLAGS_m );
		services::add_flag( $flag_structure, 'G', chanserv::$help->CS_FLAGS_G, array( __CLASS__, '_set_flag_g' ), array( __CLASS__, '_unset_flag_g' ) );
		services::add_flag( $flag_structure, 'L', chanserv::$help->CS_FLAGS_L, array( __CLASS__, '_set_flag_l' ), array( __CLASS__, '_unset_flag_l' )  );
		services::add_flag( $flag_structure, 'I', chanserv::$help->CS_FLAGS_I, array( __CLASS__, '_set_flag_i' ) );
		// add our flags :3
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_set_flags_chan( $input, $nick, $ircdata[0], $ircdata[1], core::get_data_after( $ircdata, 2 ) );
		// call _set_flags_chan
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _set_flags_chan (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $chan - The chan to set flags on
	* $flags - The flags, like '+ei'
	* $params - The params, like 'email@addr.com'
	*/
	static public function _set_flags_chan( $input, $nick, $chan, $flags, $param )
	{
		$return_data = module::$return_data;
		$rparams = explode( '||', $param );
		$levels_result = chanserv::check_levels( $nick, $chan, array( 's', 'S', 'F' ) );
		// get the levels result.
	
		if ( $chan == '' || $chan[0] != '#' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'FLAGS' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
		
		if ( !services::chan_exists( $chan, array( 'channel' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_UNREGISTERED_CHAN, array( 'chan' => $chan ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->CHAN_UNREGISTERED;
			return $return_data;
		}
		// make sure the channel exists.
		
		if ( $target == '' && $flags == '' && $levels_result )
		{
			$flags_q = database::select( 'chans_flags', array( 'channel', 'flags' ), array( 'channel', '=', $chan ) );
			$flags_q = database::fetch( $flags_q );
			// get the flag records
			
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_FLAGS_LIST, array( 'chan' => $chan, 'flags' => $flags_q->flags ) );
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_FLAGS_LIST2, array( 'chan' => $chan ) );
			$return_data[CMD_DATA] = array( 'chan' => $chan, 'flags' => $flags_q->flags );
			$return_data[CMD_SUCCESS] = true;
			return $return_data;
			// return some banter back
		}
		elseif ( $target == '' && $flags == '' && !$levels_result )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// i don't think they have access to see the channel flags..
		// missing params?
		
		$flag_a = array();
		foreach ( str_split( $flags ) as $flag )
		{
			if ( $flag != '-' && $flag != '+' && !isset( chanserv::$flags[$flag] ) )
			{
				$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_FLAGS_UNKNOWN, array( 'flag' => $flag ) );
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
		
		$flags = '';
		foreach ( $flag_a as $flag => $count )
			$flags .= $flag;
		// reconstruct the flags
		
		$flag_array = mode::sort_modes( $flags, false );
		// sort our flags up
		
		$param_num = 0;
		foreach ( str_split( $flags ) as $flag )
		{
			if ( !chanserv::$flags[$flag]['has_param'] )
				continue;
			// not a parameter-ized flag
			
			$params[$flag] = trim( $rparams[$param_num] );
			$param_num++;
			// we do!
		}
		// check if we have any paramtized flags, eg +mw
		
		if ( !$levels_result )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// they dont even have access
		
		foreach ( str_split( $flag_array['plus'] ) as $flag )
			self::_set_flags( $nick, $chan, $flag, '+', $params, $return_data );
		// loop through the flags being set, and do what we need to do with them.
		
		foreach ( str_split( $flag_array['minus'] ) as $flag )
			self::_set_flags( $nick, $chan, $flag, '-', $params, $return_data );
		// loop through the flags being unset, and do what we need to do with them.
		
		if ( isset( self::$set[$chan] ) )
		{
			$response .= services::parse( chanserv::$help->CS_FLAGS_SET, array( 'flag' => self::$set[$chan], 'chan' => $chan ) );
			$response .= ( isset( self::$already_set[$chan] ) || isset( self::$not_set[$chan] ) || isset( $return_data['FALSE_RESPONSE'] ) ) ? ', ' : '';
			$return_data[CMD_DATA]['set'] = self::$set[$chan];
			unset( self::$set[$chan] );
		}
		// send back the target stuff..
		
		if ( isset( self::$already_set[$chan] ) )
		{
			$response .= services::parse( chanserv::$help->CS_FLAGS_ALREADY_SET, array( 'flag' => self::$already_set[$chan], 'chan' => $chan ) );
			$response .= ( isset( self::$not_set[$chan] ) || isset( $return_data['FALSE_RESPONSE'] ) ) ? ', ' : '';
			$return_data[CMD_DATA]['already_set'] = self::$already_set[$chan];
			unset( self::$already_set[$chan] );
		}
		// send back the target stuff..
		
		if ( isset( self::$not_set[$chan] ) )
		{
			$response .= services::parse( chanserv::$help->CS_FLAGS_NOT_SET, array( 'flag' => self::$not_set[$chan], 'chan' => $chan ) );
			$response .= ( isset( $return_data['FALSE_RESPONSE'] ) ) ? ', ' : '';
			$return_data[CMD_DATA]['not_set'] = self::$not_set[$chan];
			unset( self::$not_set[$chan] );
		}
		// send back the target stuff..
		
		if ( isset( $return_data['FALSE_RESPONSE'] ) )
		{
			$response .= $return_data['FALSE_RESPONSE'];
			unset( $return_data['FALSE_RESPONSE'] );
		}
		// do we have any additional responses?
		
		$return_data[CMD_RESPONSE][] = $response;
		$return_data[CMD_DATA]['chan'] = $chan;
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return data
	}
	
	/*
	* _set_flags
	* 
	* $nick, $chan, $mode, $params, &$return_data
	*/
	public function _set_flags( $nick, $chan, $flag, $mode, $params, &$return_data )
	{
		if ( isset( chanserv::$flags[$flag] ) )
		{
			$flag_data = chanserv::$flags[$flag];
			// get the flag data
			
			self::set_flag( $nick, $chan, $mode.$flag, $params[$flag], $return_data );
			// pass our data to set_flag
			
			if ( $mode == '+' && $flag_data[FLAG_SET_METHOD] != null )
				call_user_func_array( $flag_data[FLAG_SET_METHOD], array( $nick, $chan, $mode, $params, $return_data ) );
			if ( $mode == '-' && $flag_data[FLAG_UNSET_METHOD] != null )
				call_user_func_array( $flag_data[FLAG_UNSET_METHOD], array( $nick, $chan, $mode, $params, $return_data ) );
			// call any set/unset methods
		}
		// check if flag exists
	}

	/*
	* _set_flag_g
	* 
	* $nick, $chan, $mode, $params, &$return_data
	*/
	public function _set_flag_g( $nick, $chan, $mode, $params, &$return_data )
	{
		ircd::part_chan( core::$config->chanserv->nick, $chan );
		// leave the channel
	}
	
	/*
	* _unset_flag_g
	* 
	* $nick, $chan, $mode, $params, &$return_data
	*/
	public function _unset_flag_g( $nick, $chan, $mode, $params, &$return_data )
	{
		if ( count( core::$chans[$chan]['users'] ) > 0 )
		{
			ircd::join_chan( core::$config->chanserv->nick, $chan );
			// join the chan.
			
			if ( ircd::$protect )
				mode::set( core::$config->chanserv->nick, $chan, '+ao '.core::$config->chanserv->nick.' '.core::$config->chanserv->nick, true );
				// +ao its self.
			else
				mode::set( core::$config->chanserv->nick, $chan, '+o '.core::$config->chanserv->nick, true );
				// +o its self.
		}
		// only join if channel has above 0 users in it.
		// G the target in question
	}
	
	/*
	* _set_flag_l
	* 
	* $nick, $chan, $mode, $params, &$return_data
	*/
	public function _set_flag_l( $nick, $chan, $mode, $params, &$return_data )
	{
		mode::set( core::$config->chanserv->nick, $chan, '-l' );
	}
	
	/*
	* _unset_flag_l
	* 
	* $nick, $chan, $mode, $params, &$return_data
	*/
	public function _unset_flag_l( $nick, $chan, $mode, $params, &$return_data )
	{
		self::increase_limit( $chan );
	}
	
	/*
	* _set_flag_i
	* 
	* $nick, $chan, $mode, $params, &$return_data
	*/
	public function _set_flag_i( $nick, $chan, $mode, $params, &$return_data )
	{
		foreach ( core::$chans[$chan]['users'] as $unick => $mode )
		{
			if ( core::$nicks[$unick]['server'] == core::$config->server->name )
				continue;
		
			if ( chanserv::check_levels( $unick, $chan, array( 'k', 'S', 'F' ), true, false ) === false )
			{
				mode::set( core::$config->chanserv->nick, $chan, '+b *@'.core::$nicks[$unick]['host'] );
				ircd::kick( core::$config->chanserv->nick, $unick, $chan, '+k only channel' );
			}
			// they don't have +k, KICKEM
		}
		// loop everyone in this chan.
	}
	
	/*
	* on_quit (event hook)
	*/
	static public function on_quit( $nick, $startup = false )
	{
		while ( list( $chan, $data ) = each( core::$chans ) )
		{
			if ( chanserv::check_flags( $chan, array( 'L' ) ) )
				timer::add( array( 'cs_flags', 'increase_limit', array( $chan ) ), 5, 1 );
			// add a timer to update the limit, in 5 seconds
		}
		reset( core::$chans );
	}
	
	/*
	* on_mode (event hook)
	*/
	static public function on_mode( $nick, $chan, $mode_queue )
	{
		if ( strpos( $nick, '.' ) !== false )
			$server = $nick;
		elseif ( strlen( $nick ) == 3 )
			$server = core::$servers[$nick]['sid'];
		else
			$server = '';
		// we've found a.in nick, which means it's a server?
		
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
					mode::set( core::$config->chanserv->nick, $chan, $modelock );
				// reset the modes
			}
		}
		// modelock?
	}
	
	/*
	* on_chan_create (event hook)
	*/
	static public function on_chan_create( $chan )
	{
		$nusers = core::$chans[$chan]['users'];
		
		if ( chanserv::$chan_q[$chan] === false )
			return false;	
		// channel isnt registered
			
		if ( chanserv::check_flags( $chan, array( 'I' ) ) )
		{
			foreach ( $nusers as $nick => $mode )
			{
				if ( chanserv::check_levels( $nick, $chan, array( 'k', 'S', 'F' ), true, false ) === false )
				{
					if ( core::$nicks[$nick]['server'] == core::$config->server->name )
						continue;
				
					mode::set( core::$config->chanserv->nick, $chan, '+b *@'.core::$nicks[$nick]['host'] );
					ircd::kick( core::$config->chanserv->nick, $nick, $chan, '+k only channel' );
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
			cs_flags::increase_limit( $chan );
			// add a timer to update the limit, in 5 seconds
		}
		// is there auto-limit enabled?
	}
	
	/*
	* on_join (event hook)
	*/
	static public function on_join( $nick, $chan )
	{
		if ( chanserv::$chan_q[$chan] === false )
			return false;	
		// channel isnt registered
		
		if ( chanserv::check_flags( $chan, array( 'I' ) ) )
		{
			if ( chanserv::check_levels( $nick, $chan, array( 'k', 'S', 'F' ), true, false ) === false )
			{
				mode::set( core::$config->chanserv->nick, $chan, '+b *@'.core::$nicks[$nick]['host'] );
				ircd::kick( core::$config->chanserv->nick, $nick, $chan, '+k only channel' );
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
			timer::add( array( 'cs_flags', 'increase_limit', array( $chan ) ), 5, 1 );
			// add a timer to update the limit, in 5 seconds
		}
		// is there auto-limit enabled?
	}
	
	/*
	* on_part (event hook)
	*/
	static public function on_part( $nick, $chan )
	{
		if ( chanserv::check_flags( $chan, array( 'L' ) ) )
		{
			timer::add( array( 'cs_flags', 'increase_limit', array( $chan ) ), 5, 1 );
			// add a timer to update the limit, in 5 seconds
		}
		// is there auto-limit enabled?
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
		$new_limit = $current_users + 4 + $force;
		if ( $new_limit == core::$chans[$chan]['internal_limit'] )
			return false;
		// plus 3
		
		core::$chans[$chan]['internal_limit'] = $new_limit;
		mode::set( core::$config->chanserv->nick, $chan, '+l '.$new_limit );
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
	* &$return_data - a valid array from module::$return_data
	*/
	static public function set_flag( $nick, $chan, $flag, $param, &$return_data )
	{
		$mode = $flag[0];
		$r_flag = $flag[1];
		// get the real flag, eg. V, v and mode
		
		if ( chanserv::$flags[$r_flag]['has_param'] && $param == '' && $mode == '+' )
		{
			$return_data['FALSE_RESPONSE'] = services::parse( chanserv::$help->CS_FLAGS_NEEDS_PARAM, array( 'flag' => $flag ) );
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
		
		if ( chanserv::$flags[$r_flag]['has_param'] && $mode == '+' )
		{
			if ( $r_flag == 'e' && services::valid_email( $param ) === false )
			{
				$return_data['FALSE_RESPONSE'] = services::parse( chanserv::$help->CS_FLAGS_INVALID_E, array( 'flag' => $flag ) );
				return false;
			}
			// is the email invalid?
			
			if ( $r_flag == 't' && strpos( $param, '*' ) === false )
			{
				$return_data['FALSE_RESPONSE'] = services::parse( chanserv::$help->CS_FLAGS_INVALID_T, array( 'flag' => $flag ) );
				return false;
			}
			// is the topicmask invalid?
			
			if ( $r_flag == 'm' )
			{
				$mode_string = explode( ' ', $param );
				
				if ( strstr( $mode_string[0], 'r' ) || strstr( $mode_string[0], 'q' ) || strstr( $mode_string[0], 'a' ) || strstr( $mode_string[0], 'o' ) || strstr( $mode_string[0], 'h' ) || strstr( $mode_string[0], 'v' ) || strstr( $mode_string[0], 'b' ) )
				{
					$return_data['FALSE_RESPONSE'] = services::parse( chanserv::$help->CS_FLAGS_INVALID_M, array( 'flag' => $flag ) );
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
				
				if ( chanserv::$flags[$r_flag]['has_param'] )
					database::update( 'chans_flags', array( 'flags' => $new_chan_flags, $param_field => $param ), array( 'channel', '=', $chan ) );	
				// update the row with the new flags.
				else
					database::update( 'chans_flags', array( 'flags' => $new_chan_flags ), array( 'channel', '=', $chan ) );	
				// update the row with the new flags.
				
				self::$set[$chan] .= $r_flag;
				// some magic :O
				return true;
			}
			
			if ( $mode == '+' )
			{
				if ( !chanserv::$flags[$r_flag]['has_param'] )
				{
					self::$already_set[$chan] .= $r_flag;
					return false;
				}
				// if it doesn't need a param and is being +'d and its already set..
				
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
				
				if ( !chanserv::$flags[$r_flag]['has_param'] )
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
