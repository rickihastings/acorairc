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

class ns_request extends module
{
	
	const MOD_VERSION = '0.1.2';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'INVALID_HOSTNAME'	=> 2,
		'PENDING_REQUEST'	=> 3,
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
		modules::init_module( __CLASS__, self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		commands::add_help( 'nickserv', 'ns_request', 'help', nickserv::$help->NS_HELP_REQUEST_1, true );
		commands::add_help( 'nickserv', 'ns_request', 'help request', nickserv::$help->NS_HELP_REQUEST_ALL );
		// add the help
		
		commands::add_command( 'nickserv', 'request', 'ns_request', 'request_command' );
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
		if ( !core::$nicks[$nick]['identified'] )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_NOT_IDENTIFIED );
			return false;
		}
		// are they identified?
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_request_vhost( $input, $nick, $ircdata[0] );
		// call _request_vhost
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _request_vhost (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $host - The vhost to request
	*/
	public function _request_vhost( $input, $nick, $host )
	{
		if ( trim( $host ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'REQUEST' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// invalid syntax
		
		if ( substr_count( $host, '@' ) == 1 )
		{
			$realhost = $host;
			$new_host = explode( '@', $host );
			$ident = $new_host[0];
			$host = $new_host[1];
		}
		elseif ( substr_count( $host, '@' ) > 1 || services::valid_host( $host ) === false )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_HOSTNAME );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_HOSTNAME;
			return $return_data;
		}
		else
			$realhost = $host;
		// check if there is a @
		
		$query = database::select( 'vhost_request', array( 'id', 'nickname' ), array( 'nickname', '=', $input['account'] ) );
		if ( database::num_rows( $query ) > 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_PENDING_REQUEST );
			$return_data[CMD_FAILCODE] = self::$return_codes->PENDING_REQUEST;
			return $return_data;
		}
		// check if there is already a pending request
		
		database::insert( 'vhost_request', array( 'vhost' => $host, 'nickname' => core::$nicks[$nick]['account'], 'hostname' => $input['hostname'], 'timestamp' => core::$network_time ) );
		core::alog( core::$config->nickserv->nick.': ('.$input['hostname'].') ('.$input['account'].') has requested a vhost ('.$host.')' );
		// update it and log it
		
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_REQUESTED_HOST );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return data back
	}
}

// EOF;
