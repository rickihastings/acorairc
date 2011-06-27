<?php

/*
* Acora IRC Services
* modules/request.ns.php: NickServ vhost request module
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

class ns_request implements module
{
	
	const MOD_VERSION = '0.0.1';
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
		modules::init_module( 'ns_request', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_request', 'help', nickserv::$help->NS_HELP_REQUEST_1 );
		nickserv::add_help( 'ns_request', 'help request', nickserv::$help->NS_HELP_REQUEST_ALL );
		// add the help
		
		nickserv::add_command( 'request', 'ns_request', 'request_command' );
		// add the drop command
	}
	
	/*
	* request_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function request_command( $nick, $ircdata = array() )
	{
		$host = $ircdata[0];
		
		if ( !core::$nicks[$nick]['identified'] )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NOT_IDENTIFIED );
			return false;
		}
		// are they identified?
		
		if ( trim( $host ) == '' )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'REQUEST' ) );
			return false;
		}
		// invalid syntax
		
		if ( substr_count( $host, '@' ) == 1 )
		{
			$realhost = $host;
			$new_host = explode( '@', $host );
			$ident = $new_host[0];
			$host = $new_host[1];
		}
		elseif ( substr_count( $host, '@' ) > 1 )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_HOSTNAME );
			return false;
		}
		else
		{
			$realhost = $host;
		}
		// check if there is a @
		
		if ( services::valid_host( $host ) === false )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_HOSTNAME );
			return false;
		}
		// is the hostname valid?
		
		$query = database::select( 'vhost_request', array( 'id', 'nickname' ), array( 'nickname', '=', core::$nicks[$nick]['account'] ) );
		if ( database::num_rows( $query ) > 0 )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_PENDING_REQUEST );
			return false;
		}
		// check if there is already a pending request
		
		database::insert( 'vhost_request', array( 'vhost' => $host, 'nickname' => core::$nicks[$nick]['account'], 'hostname' => core::get_full_hostname( $nick ), 'timestamp' => core::$network_time ) );
		core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') has requested a vhost ('.$host.')' );
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_REQUESTED_HOST );
		// update it and log it
	}
}

//EOF;