<?php

/*
* Acora IRC Services
* modules/vhost.os.php: OperServ vhosts module
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class os_vhost implements module
{
	
	const MOD_VERSION = '0.0.2';
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
		modules::init_module( 'os_vhost', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_vhost', 'help', &operserv::$help->OS_HELP_VHOST_1 );
		operserv::add_help( 'os_vhost', 'help vhost', &operserv::$help->OS_HELP_VHOST_ALL );
		// add the help
		
		operserv::add_command( 'vhost', 'os_vhost', 'vhost_command' );
		// add the vhost command
	}


	/*
	* vhost_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function vhost_command( $nick, $ircdata = array() )
	{
		$mode = $ircdata[0];
		
		if ( strtolower( $mode ) == 'set' )
		{
			$host = $ircdata[2];
			$unick = $ircdata[1];
			// some variables.
			
			if ( trim( $unick ) == '' )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'VHOST' ) );
				return false;
			}
			// are we missing nick? invalid syntax if so.
			
			if ( !$user = services::user_exists( $unick, false, array( 'display', 'id', 'identified', 'vhost' ) ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_ISNT_REGISTERED, array( 'nick' => $unick ) );
				return false;
			}
			// is the nick registered?
			
			if ( substr_count( $host, '@' ) == 1 )
			{
				$realhost = $host;
				$new_host = explode( '@', $host );
				$ident = $new_host[0];
				$host = $new_host[1];
			}
			elseif ( substr_count( $host, '@' ) > 1 )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_HOSTNAME );
				return false;
			}
			else
			{
				$realhost = $host;
			}
			// check if there is a @
			
			if ( services::valid_host( $host ) === false )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_HOSTNAME );
				return false;
			}
			// is the hostname valid?
			
			database::update( 'users', array( 'vhost' => $realhost ), "`display` = '".$user->display."'" );
			core::alog( core::$config->operserv->nick.': vHost for '.$unick.' set to '.$realhost.' by '.$nick );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_VHOST_SET, array( 'nick' => $unick, 'host' => $realhost ) );
			// update it and log it
			
			if ( isset( core::$nicks[$unick] ) && $user->identified == 1 )
			{
				if ( substr_count( $realhost, '@' ) == 1 )
				{
					ircd::setident( core::$config->operserv->nick, $unick, $ident );
					ircd::sethost( core::$config->operserv->nick, $unick, $host );
				}
				else
				{
					ircd::sethost( core::$config->operserv->nick, $unick, $host );
				}
			}
			// we need to check if the user is online and identified?
		}
		elseif ( strtolower( $mode ) == 'del' )
		{
			$unick = $ircdata[1];
			// some variables.
			
			if ( trim( $unick ) == '' )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'VHOST' ) );
				return false;
			}
			// are we missing nick? invalid syntax if so.
			
			if ( !$user = services::user_exists( $unick, false, array( 'display', 'id', 'identified', 'vhost' ) ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_ISNT_REGISTERED, array( 'nick' => $unick ) );
				return false;
			}
			// is the nick registered?
			
			if ( $user->vhost == '' )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_NO_VHOST, array( 'nick' => $unick ) );
				return false;
			}
			// is there a vhost?!
						
			database::update( 'users', array( 'vhost' => '' ), "`display` = '".$user->display."'" );
			core::alog( core::$config->operserv->nick.': vHost for '.$unick.' deleted by '.$nick );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_VHOST_DELETED, array( 'nick' => $unick ) );
			// update and logchan
		}
		elseif ( strtolower( $mode ) == 'list' )
		{
			$limit = $ircdata[1];
			// get limit.
			
			if ( trim( $limit ) == ''  )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'VHOST' ) );
				return false;
			}
			// invalid syntax
			
			if ( substr_count( $limit, '-' ) > 1 )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'VHOST' ) );
				return false;
			}
			// invalid syntax
			
			$total = database::select( 'users', array( 'id' ), "`vhost` != ''" );
			$total = database::num_rows( $total );
			// get the total
			
			$limit = database::quote( $limit );
			$s_limit = explode( '-', $limit );
			$offset = $s_limit[0];
			$max = $s_limit[1];
			// split up the limit and stuff ^_^
			
			$users_q = database::select( 'users', array( 'display', 'vhost' ), "`vhost` != ''", '', array( $offset => $max ) );
			// get the vhosts
			
			if ( database::num_rows( $users_q ) == 0 )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_VHOST_LIST_B, array( 'num' => database::num_rows( $users_q ), 'total' => $total ) );
				return false;
			}
			// no vhosts
			
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_VHOST_LIST_T );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_VHOST_LIST_T2 );
			// list top.
			
			$x = 0;
			while ( $users = database::fetch( $users_q ) )
			{
				$x++;
				$false_nick = $users->display;
				
				if ( !isset( $users->display[18] ) )
				{
					$y = strlen( $users->display );
					for ( $i = $y; $i <= 17; $i++ )
						$false_nick .= ' ';
				}
				// this is just a bit of fancy fancy, so everything displays neat
				
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_VHOST_LIST_R, array( 'num' => $x, 'nick' => $false_nick, 'info' => $users->vhost ) );
			}
			// loop through em, show the vhosts
			
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_VHOST_LIST_B, array( 'num' => ( database::num_rows( $users_q ) == 0 ) ? 0 : database::num_rows( $users_q ), 'total' => $total ) );
			// end of list.
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'VHOST' ) );
			return false;
			// invalid syntax.
		}
	}
	
	/*
	* main (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
	public function main( &$ircdata, $startup = false )
	{
		return true;
		// we don't need to listen for anything in this module
		// so we just return true immediatly.
	}
}

// EOF;