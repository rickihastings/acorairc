<?php

/*
* Acora IRC Services
* modules/identify.ns.php: NickServ identify module
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

class ns_identify extends module
{
	
	const MOD_VERSION = '0.1.8';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'NICK_UNREGISTERED'	=> 2,
		'NOT_VALIDATED'		=> 3,
		'NO_MULTIPLE_SESS' 	=> 4,
		'REACHED_LIMIT'		=> 5,
		'INVALID_PASSWORD'	=> 6,
		'NOT_IDENTIFIED'	=> 7,
		'ALREADY_IDENTIFIED'=> 8,
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
		modules::init_module( 'ns_identify', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_identify', 'help', nickserv::$help->NS_HELP_IDENTIFY_1, true );
		nickserv::add_help( 'ns_identify', 'help identify', nickserv::$help->NS_HELP_IDENTIFY_ALL );
		nickserv::add_help( 'ns_identify', 'help', nickserv::$help->NS_HELP_LOGOUT_1, true );
		nickserv::add_help( 'ns_identify', 'help logout', nickserv::$help->NS_HELP_LOGOUT_ALL );
		// add the help
		
		nickserv::add_command( 'identify', 'ns_identify', 'identify_command' );
		nickserv::add_command( 'logout', 'ns_identify', 'logout_command' );
		// add the command
		
		nickserv::add_help( 'ns_identify', 'help id', nickserv::$help->NS_HELP_IDENTIFY_ALL );
		nickserv::add_command( 'id', 'ns_identify', 'identify_command' );
		// "id" alias, help and command
	}
	
	/*
	* identify_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function identify_command( $nick, $ircdata = array() )
	{
		if ( count( $ircdata ) == 1 )
		{
			$account = $nick;
			$password = $ircdata[0];
		}
		else
		{
			$account = $ircdata[0];
			$password = $ircdata[1];
		}
		// determine how many params we have
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );		
		$return_data = self::_identify_user( $input, $nick, $account, $password );
		// call _logout_user
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* logout_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function logout_command( $nick, $ircdata = array() )
	{	
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );		
		$return_data = self::_logout_user( $input, $nick );
		// call _logout_user
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _identify_user (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $unick - The account to identify to
	* $password - The password for that account
	*/
	static public function _identify_user( $input, $nick, $account, $password )
	{
		$return_data = module::$return_data;
		$allow_multiple_sessions = ( isset( core::$config->nickserv->allow_multiple_sessions ) ) ? core::$config->nickserv->allow_multiple_sessions : true;
		$session_limit = ( isset( core::$config->nickserv->session_limit ) ) ? core::$config->nickserv->session_limit : 2;
		
		if ( core::$nicks[$nick]['identified'] )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_ALREADY_IDENTIFIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ALREADY_IDENTIFIED;
			return $return_data;
		}
		// not even identified
		
		if ( trim( $account ) == '' || trim( $password ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'IDENTIFY' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax damit!
		
		if ( !$user = services::user_exists( $account, false, array( 'display', 'pass', 'validated', 'salt', 'vhost' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_ISNT_REGISTERED, array( 'nick' => $account ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
			return $return_data;
		}
		// doesn't even exist..
		
		if ( $user->validated == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_AWAITING_VALIDATION );
			$return_data[CMD_FAILCODE] = self::$return_codes->NOT_VALIDATED;
			return $return_data;
		}
		// user hasen't validated
		
		if ( $user->pass == sha1( $password.$user->salt ) )
		{
			$sessions = 0;
			while ( list( $n, $d ) = each( core::$nicks ) )
			{
				if ( $d['account'] == $account )
					$sessions++;
				if ( $allow_multiple_sessions && $sessions == $session_limit )
					break;
			}
			reset( core::$nicks );
			// check how many sessions we're in, if any
			
			if ( $allow_multiple_sessions && $sessions == $session_limit )
			{
				$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_REACHED_LIMIT, array( 'limit' => $session_limit ) );
				$return_data[CMD_FAILCODE] = self::$return_codes->REACHED_LIMIT;
				return $return_data;
			}
			if ( $sessions >= 1 && !$allow_multiple_sessions )
			{
				$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_NO_MULTIPLE_SESS );
				$return_data[CMD_FAILCODE] = self::$return_codes->NO_MULTIPLE_SESS;
				return $return_data;
			}
			// if we're in more than the limit specified in the config, bail!
		
			timer::remove( array( 'ns_identify', 'secured_callback', array( $nick ) ) );
			// remove the secured timer. if there is one
			
			ircd::on_user_login( $nick, $account );
			core::$nicks[$nick]['account'] = $account;
			core::$nicks[$nick]['identified'] = true;
			core::$nicks[$nick]['failed_attempts'] = 0;
			// registered mode
			
			database::update( 'users', array( 'last_hostmask' => $input['hostname'], 'last_timestamp' => core::$network_time, 'identified' => 1 ), array( 'display', '=', $account ) );
			// right, standard identify crap
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_IDENTIFIED );
			core::alog( core::$config->nickserv->nick.': ('.$input['hostname'].') identified for '.$account );
			// logchan
			
			if ( $user->vhost != '' && isset( modules::$list['os_vhost'] ) && nickserv::check_flags( $nick, array( 'H' ) ) )
			{
				if ( substr_count( $user->vhost, '@' ) == 1 )
				{
					$new_host = explode( '@', $user->vhost );
					$ident = $new_host[0];
					$host = $new_host[1];
					
					ircd::setident( core::$config->nickserv->nick, $nick, $ident );
					ircd::sethost( core::$config->nickserv->nick, $nick, $host );
				}
				else
					ircd::sethost( core::$config->nickserv->nick, $nick, $user->vhost );
			}
			// first thing we do, check if they have a vhost, if they do, apply it.
			
			$failed_attempts = database::select( 'failed_attempts', array( 'nick', 'mask', 'time' ), array( 'nick', '=', $account ) );
			if ( database::num_rows( $failed_attempts ) > 0 )
			{
				$return_data[CMD_RESPONSE][] = services::parse( ''.database::num_rows( $failed_attempts ).' failed login(s) since last login.' );
			
				while ( $row = database::fetch( $failed_attempts ) )
					$return_data[CMD_RESPONSE][] = services::parse( 'Failed login from: '.$row->mask.' on '.date( "F j, Y, g:i a", $row->time ).'' );
				// loop through the failed attempts messaging them to the user
				database::delete( 'failed_attempts', array( 'nick', '=', $account ) );
				// clear them now that they've been seen
			}
			// we got any failed attempts? HUMM
			
			if ( core::$config->settings->mode_on_id && isset( modules::$list['cs_levels'] ) )
			{
				while ( list( $chan, $cdata ) = each( core::$chans ) )
				{
					if ( !isset( core::$chans[$chan]['users'][$nick] ) )
						continue;
				
					if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
						return false;
					// if the channel doesn't exist we return false, to save us the hassle of wasting
					// resources on this stuff below.
				
					if ( $nick == core::$config->chanserv->nick )
						continue;
					// skip us :D
					
					cs_levels::on_create( array( $nick => core::$chans[$chan]['users'][$nick] ), $channel, true );
					// on_create event
				}
				reset( core::$chans );
				// loop through channels, check if they are in any
			}
			// check if mode_on_id is set, also cs_access is enabled, and lets do a remote access gain :D
			
			$return_data[CMD_SUCCESS] = true;
			return $return_data;
			// return the data back
		}
		else
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_PASSWORD );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_PASSWORD;
			
			core::alog( core::$config->nickserv->nick.': Invalid password from ('.$input['hostname'].') for '.$account );
			// some logging stuff
			
			database::insert( 'failed_attempts', array( 'nick' => $account, 'mask' => $input['hostname'], 'time' => core::$network_time ) );
			core::$nicks[$nick]['failed_attempts']++;
			// ooh, we have something to log :)
			
			if ( core::$nicks[$nick]['failed_attempts'] == 5 )
				ircd::kill( core::$config->nickserv->nick, $nick, 'Maxmium FAILED login attempts reached.' );
			// have they reached the failed attempts limit? we gonna fucking KILL mwhaha
			
			return $return_data;
			// return the data back
		}
		// invalid password? HAX!!
	}
	
	/*
	* _logout_user (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	*/
	static public function _logout_user( $input, $nick )
	{
		$return_data = module::$return_data;
		if ( !core::$nicks[$nick]['identified'] )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_NOT_IDENTIFIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->NOT_IDENTIFIED;
			return $return_data;
		}
		// not even identified
		
		database::update( 'users', array( 'last_timestamp' => core::$network_time, 'identified' => 0 ), array( 'display', '=', $input['account'] ) );
		// unidentify them
		core::alog( core::$config->nickserv->nick.': '.$nick.' logged out of ('.$input['hostname'].') ('.$input['account'].')' );
		// and log it.
		
		ircd::on_user_logout( $nick );
		core::$nicks[$nick]['account'] = '';
		core::$nicks[$nick]['identified'] = false;
		
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_LOGGED_OUT );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* on_burst_connect (event hook)
	*/
	static public function on_burst_connect( $connect_data )
	{
		self::on_connect( $connect_data );
	}
	
	/*
	* on_connect (event hook)
	*/
	static public function on_connect( $connect_data )
	{
		$user = nickserv::$nick_q[$connect_data['nick']];
		// get nick
		
		if ( !isset( $user ) || $user === false )
			return false;
			
		$nick = $connect_data['nick'];
		// re-allocate it after we know we actually need to use $nick, will shave milliseconds off huge bursts
		// not amazing but better than nothing.
			
		if ( $user->suspended == 1 )
		{
			return false;
		}
		elseif ( $user->validated == 0 && $user->suspended == 0 )
		{
			ircd::on_user_logout( $nick );
			core::$nicks[$nick]['account'] = '';
			core::$nicks[$nick]['identified'] = false;
			// they shouldn't really have registered mode
			database::update( 'users', array( 'last_timestamp' => core::$network_time, 'identified' => 0 ), array( 'display', '=', $nick ) );
			// store the internal identified state in a database.
			
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_AWAITING_VALIDATION );
			return false;
		}
		elseif ( ( !core::$nicks[$nick]['identified'] && $user->identified == 1 ) && core::get_full_hostname( $nick ) == $user->last_hostmask )
		{
			ircd::on_user_login( $nick, $user->display );
			core::$nicks[$nick]['account'] = $user->display;
			core::$nicks[$nick]['identified'] = true;
			
			return false;
		}
		else
		{
			self::_registered_nick( $nick, $user );
		}
	}
	
	/*
	* on_nick_change (event hook)
	*/
	static public function on_nick_change( $old_nick, $nick )
	{
		timer::remove( array( 'ns_identify', 'secured_callback', array( $old_nick ) ) );
		// remove the secured timer. if there is one
		
		if ( $user = services::user_exists( $nick, false, array( 'display', 'identified', 'validated', 'last_hostmask', 'suspended' ) ) )
		{
			if ( $user->suspended == 1 )
			{
				ircd::on_user_logout( $nick );
				core::$nicks[$nick]['account'] = '';
				core::$nicks[$nick]['identified'] = false;
				// they shouldn't really have registered mode
				database::update( 'users', array( 'last_timestamp' => core::$network_time, 'identified' => 0 ), array( 'display', '=', $nick ) );
				// store the internal identified state in a database.
				
				return false;
			}
			elseif ( $user->validated == 0 && $user->suspended == 0 )
			{
				ircd::on_user_logout( $nick );
				core::$nicks[$nick]['account'] = '';
				core::$nicks[$nick]['identified'] = false;
				// they shouldn't really have registered mode
				database::update( 'users', array( 'last_timestamp' => core::$network_time, 'identified' => 0 ), array( 'display', '=', $nick ) );
				// store the internal identified state in a database.
				
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_AWAITING_VALIDATION );
				return false;
			}
			elseif ( $nick != core::$nicks[$nick]['account'] )
			{
				self::_registered_nick( $nick, $user );
			}
		}
		// is the new nick registered? let them know
	}
	
	/*
	* on_quit (event hook)
	*/
	static public function on_quit( $nick, $startup = false )
	{
		timer::remove( array( 'ns_identify', 'secured_callback', array( $nick ) ) );
		// remove the secured timer. if there is one
		
		database::update( 'users', array( 'last_timestamp' => core::$network_time, 'identified' => 0 ), array( 'display', '=', $nick ) );
		// update timestamp
	}
	
	/*
	* secured_callback (timer)
	* 
	* @params
	* $nick - nick to change.
	*/
	public function secured_callback( $nick )
	{
		$random_nick = 'Unknown'.rand( 10000, 99999 );
		ircd::svsnick( $nick, $random_nick, core::$nicks[$nick]['timestamp'] );
		// ready to change a secured nick >:D
	}	
	
	/*
	* _registered_nick (private)
	* 
	* @params
	* $nick - the nick to use
	* $user - a valid user record from the database.
	*/
	static public function _registered_nick( $nick, $user )
	{
		ircd::on_user_logout( $nick );
		core::$nicks[$nick]['account'] = '';
		core::$nicks[$nick]['identified'] = false;
		// they shouldn't really have registered mode
		
		database::update( 'users', array( 'last_timestamp' => core::$network_time, 'identified' => 0 ), array( 'display', '=', $nick ) );
		// store the internal identified state in a database.
		
		if ( is_array( nickserv::$help->NS_REGISTERED_NICK ) )
		{
			foreach ( nickserv::$help->NS_REGISTERED_NICK as $line )
				$response[] = services::parse( $line );
			services::parse( core::$config->nickserv->nick, $nick, $response );
		}
		else
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_REGISTERED_NICK );
		}
		// this is just a crappy function, basically just parses the NS_REGISTERED thing
		// we check for arrays and single lines, even though the default is array
		// someone might have changed it.
		
		if ( nickserv::check_flags( $nick, array( 'S' ) ) && isset( modules::$list['ns_flags'] ) )
		{
			$limit = nickserv::get_flags( $nick, 's' );
			$limit = ( $limit == 0 ) ? core::$config->nickserv->secure_time : $limit;
		
			timer::add( array( 'ns_identify', 'secured_callback', array( $nick ) ), $limit, 1 );
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_SECURED_NICK, array( 'seconds' => $limit ) );
		}
		// if the nickname has secure enabled, we let them know that we're watching them :o
	}
}

// EOF;