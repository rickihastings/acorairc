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

class ns_identify implements module
{
	
	const MOD_VERSION = '0.0.5';
	const MOD_AUTHOR = 'Acora';
	// module info
	
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
		modules::init_module( 'ns_identify', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_identify', 'help', nickserv::$help->NS_HELP_IDENTIFY_1 );
		nickserv::add_help( 'ns_identify', 'help identify', nickserv::$help->NS_HELP_IDENTIFY_ALL );
		nickserv::add_help( 'ns_identify', 'help', nickserv::$help->NS_HELP_LOGOUT_1 );
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
		$password = $ircdata[0];
		
		if ( trim( $password ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'IDENTIFY' ) );
			return false;
		}
		// wrong syntax damit!
		
		if ( $user = services::user_exists( $nick, false, array( 'display', 'pass', 'validated', 'salt', 'vhost' ) ) )
		{
			if ( $user->validated == 0 )
			{
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_AWAITING_VALIDATION );
				return false;
			}
			elseif ( core::$nicks[$nick]['identified'] )
			{
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ALREADY_IDENTIFIED );
				return false;
			}
			else
			{
				if ( $user->pass == sha1( $password.$user->salt ) )
				{
					timer::remove( array( 'ns_identify', 'secured_callback', array( $nick ) ) );
					// remove the secured timer. if there is one
					
					ircd::on_user_login( $nick );
					core::$nicks[$nick]['identified'] = true;
					// registered mode
					
					database::update( 'users', array( 'last_hostmask' => core::get_full_hostname( $nick ), 'last_timestamp' => 0 ), array( 'display', '=', $nick ) );
					services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_IDENTIFIED );
					// right, standard identify crap
					core::alog( core::$config->nickserv->nick.': '.core::get_full_hostname( $nick ).' identified for nick '.core::$nicks[$nick]['nick'] );
					// logchan
					
					if ( $user->vhost != '' && isset( modules::$list['os_vhost'] ) )
					{
						if ( substr_count( $user->vhost, '@' ) == 1 )
						{
							$new_host = explode( '@', $user->vhost );
							$ident = $new_host[0];
							$host = $new_host[1];
							
							ircd::setident( core::$config->operserv->nick, $user->display, $ident );
							ircd::sethost( core::$config->operserv->nick, $user->display, $host );
						}
						else
						{
							ircd::sethost( core::$config->operserv->nick, $user->display, $user->vhost );
						}
					}
					// first thing we do, check if they have a vhost, if they do, apply it.
					
					$failed_attempts = database::select( 'failed_attempts', array( 'nick', 'mask', 'time' ), array( 'nick', '=', $nick ) );
					
					if ( database::num_rows( $failed_attempts ) > 0 )
					{
						services::communicate( core::$config->nickserv->nick, $nick, ''.database::num_rows( $failed_attempts ).' failed login(s) since last login.' );
					
						while ( $row = database::fetch( $failed_attempts ) )
						{
							services::communicate( core::$config->nickserv->nick, $nick, 'Failed login from: '.$row->mask.' on '.date( "F j, Y, g:i a", $row->time ).'' );
						}
						// loop through the failed attempts messaging them to the user
						database::delete( 'failed_attempts', array( 'nick', '=', $nick ) );
						// clear them now that they've been seen
					}
					// we got any failed attempts? HUMM
					
					$hostname = core::get_full_hostname( $nick );
					// generate a hostname.
					
					if ( core::$config->settings->mode_on_id == 'yes' && isset( modules::$list['cs_levels'] ) )
					{
						foreach ( core::$chans as $chan => $cdata )
						{
							if ( !$channel = services::chan_exists( $chan, array( 'channel' ) ) )
								return false;
							// if the channel doesn't exist we return false, to save us the hassle of wasting
							// resources on this stuff below.
						
							if ( $nick == core::$config->chanserv->nick )
								continue;
							// skip us :D
							
							$hostname = core::get_full_hostname( $nick );
							// get the hostname ready.
							
							cs_levels::on_create( array( $nick => core::$chans[$chan]['users'][$nick] ), $channel, true );
							// on_create event
						}
						// loop through channels, check if they are in any
					}
					// check if mode_on_id is set, also cs_access is enabled, and lets do a remote access gain :D
				}
				else
				{
					services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_PASSWORD );
					core::alog( core::$config->nickserv->nick.': Invalid password from '.core::get_full_hostname( $nick ) );
					// some logging stuff
					
					database::insert( 'failed_attempts', array( 'nick' => $nick, 'mask' => core::get_full_hostname( $nick ), 'time' => core::$network_time ) );
					core::$nicks[$nick]['failed_attempts']++;
					// ooh, we have something to log :)
					
					if ( core::$nicks[$nick]['failed_attempts'] == 5 )
						ircd::kill( core::$config->nickserv->nick, $nick, 'Maxmium FAILED login attempts reached.' );
					// have they reached the failed attempts limit? we gonna fucking KILL mwhaha
				}
				// invalid password? HAX!!
			}
			// are they already identifed?
		}
		else
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_UNREGISTERED );
			return false;
			// doesn't even exist..
		}
		// right now we need to check if the user exists, and password matches
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
		// no parameter commands ftw.
		
		if ( $user = services::user_exists( $nick, false, array( 'display', 'id', 'vhost' ) ) )
		{
			if ( $user->identified == 1 )
			{
				ircd::on_user_logout( $nick );
				core::$nicks[$nick]['identified'] = false;
					
				// here we set unregistered mode
				database::update( 'users', array( 'last_timestamp' => core::$network_time ), array( 'display', '=', $nick ) );
				// unidentify them
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_LOGGED_OUT );
				// let them know
				core::alog( core::$config->nickserv->nick.': '.core::get_full_hostname( $nick ).' logged out of '.core::$nicks[$nick]['nick'] );
				// and log it.
			}
			else
			{
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NOT_IDENTIFIED );
				// not even identified
			}
		}
		else
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_UNREGISTERED );
			// unregistered nick name
		}
	}
	
	/*
	* main (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	public function main( $ircdata, $startup = false )
	{
		$connect_data = ircd::on_connect( $ircdata );
		if ( $connect_data !== false )
		{
			$nick = $connect_data['nick'];
			$user = nickserv::$nick_q[strtolower( $nick )];
			// get nick
			
			if ( !isset( $user ) || $user === false )
				return false;
				
			if ( $user->suspended == 1 )
			{
				return false;
			}
			elseif ( $user->validated == 0 && $user->suspended == 0 )
			{
				ircd::on_user_logout( $nick );
				core::$nicks[$nick]['identified'] = false;
				// they shouldn't really have registered mode
				
				services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_AWAITING_VALIDATION );
			}
			elseif ( !core::$nicks[$nick]['identified'] && $user->suspended == 0 )
			{
				self::_registered_nick( $nick, $user );
			}
			elseif ( core::$nicks[$nick]['identified'] && $user->last_hostmask == core::get_full_hostname( $nick ) )
			{
				ircd::on_user_login( $nick );
				core::$nicks[$nick]['identified'] = true;
				
				if ( !$startup )
					core::alog( core::$config->nickserv->nick.': '.$connect_data['ident'].'@'.$connect_data['host'].' automatically identified for '.$nick );
			}
			else
			{
				self::_registered_nick( $nick, $user );
			}
		}
		// on connect let them know that they're using
		// an identified nickname
		
		$return = ircd::on_nick_change( $ircdata );
		if ( $return !== false )
		{
			$nick = $return['new_nick'];
			$old_nick = $return['nick'];
			// get the nicknames
			
			timer::remove( array( 'ns_identify', 'secured_callback', array( $old_nick ) ) );
			// remove the secured timer. if there is one
			ircd::on_user_logout( $nick );
			// we remove the registered mode
			
			if ( $user = services::user_exists( $nick, false, array( 'display', 'validated', 'last_hostmask', 'suspended' ) ) )
			{
				if ( $user->suspended == 1 )
				{
					return false;
				}
				elseif ( $user->validated == 0 && $user->suspended == 0 )
				{
					ircd::on_user_logout( $nick );
					core::$nicks[$nick]['identified'] = false;
					// they shouldn't really have registered mode
					
					services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_AWAITING_VALIDATION );
				}
				elseif ( !core::$nicks[$nick]['identified'] && $user->suspended == 0 )
				{
					self::_registered_nick( $nick, $user );
				}
				elseif ( core::$nicks[$nick]['identified'] && $user->last_hostmask == core::get_full_hostname( $nick ) )
				{
					ircd::on_user_login( $nick );
					core::$nicks[$nick]['identified'] = true;
					
					core::alog( core::$config->nickserv->nick.': '.core::$nicks[$nick]['ident'].'@'.core::$nicks[$nick]['host'].' automatically identified for '.$nick );
				}
				else
				{
					self::_registered_nick( $nick, $user );
				}
			}
			// is the new nick registered? let them know
		}
		
		$quit_nick = ircd::on_quit( $ircdata );
		if ( $quit_nick !== false )
		{
			timer::remove( array( 'ns_identify', 'secured_callback', array( $nick ) ) );
			// remove the secured timer. if there is one
			
			database::update( 'users', array( 'last_timestamp' => core::$network_time ), array( 'display', '=', $nick ) );
			// change nick to unidentified imo
		}
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
	* $nick - the nick to usee
	* $user - a valid user record from the database.
	*/
	static public function _registered_nick( $nick, $user )
	{
		ircd::on_user_logout( $nick );
		core::$nicks[$nick]['identified'] = false;
		// they shouldn't really have registered mode
		
		if ( is_array( nickserv::$help->NS_REGISTERED_NICK ) )
		{
			foreach ( nickserv::$help->NS_REGISTERED_NICK as $line )
				services::communicate( core::$config->nickserv->nick, $nick, $line );
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
			timer::add( array( 'ns_identify', 'secured_callback', array( $nick ) ), core::$config->nickserv->secure_time, 1 );
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_SECURED_NICK, array( 'seconds' => core::$config->nickserv->secure_time ) );
		}
		// if the nickname has secure enabled, we let them know that we're watching them :o
	}
}

// EOF;