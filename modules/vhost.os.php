<?php

/*
* Acora IRC Services
* modules/vhost.os.php: OperServ vhosts module
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

class os_vhost extends module
{
	
	const MOD_VERSION = '0.1.3';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'NICK_UNREGISTERED'	=> 2,
		'INVALID_HOSTNAME'	=> 3,
		'NO_VHOST'			=> 4,
		'NO_VHOST_REQUEST'	=> 5,
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
		modules::init_module( 'os_vhost', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		operserv::add_help( 'os_vhost', 'help', operserv::$help->OS_HELP_VHOST_1, true, 'local_op' );
		operserv::add_help( 'os_vhost', 'help vhost', operserv::$help->OS_HELP_VHOST_ALL, false, 'local_op' );
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
		$mode = strtolower( $ircdata[0] );
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		
		if ( $mode == 'set' )
		{
			if ( !services::oper_privs( $nick, 'local_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// access?
			
			$return_data = self::_add_vhost( $input, $nick, $ircdata[2], $ircdata[1] );
			// send to a subfunction
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		elseif ( $mode == 'del' )
		{
			if ( !services::oper_privs( $nick, 'local_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// access?
			
			$return_data = self::_del_vhost( $input, $nick, $ircdata[1] );
			// send to a subfunction
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		elseif ( $mode == 'list' )
		{
			$nmode = ( isset( $ircdata[2] ) ) ? strtolower( $ircdata[2] ) : '';
			$return_data = self::_list_vhost( $input, $nick, $ircdata[1], $nmode );
			// send to a subfunction
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		elseif ( strtolower( $mode ) == 'approve' )
		{
			if ( !services::oper_privs( $nick, 'local_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// access?
			
			$return_data = self::_approve_vhost( $input, $nick, $ircdata[1] );
			// send to a subfunction
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		elseif ( strtolower( $mode ) == 'reject' )
		{
			if ( !services::oper_privs( $nick, 'local_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// access?
			
			$return_data = self::_reject_vhost( $input, $nick, $ircdata[1] );
			// send to a subfunction
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'VHOST' ) );
			return false;
			// invalid syntax.
		}
	}
	
	/*
	* _add_vhost (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $host - The requested hostname
	* $unick - The nickname thats requesting the vhost
	*/
	static public function _add_vhost( $input, $nick, $host, $unick )
	{
		$return_data = module::$return_data;
		
		if ( trim( $unick ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'VHOST' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// are we missing nick? invalid syntax if so.
		
		if ( !$user = services::user_exists( $unick, false, array( 'display', 'id', 'vhost' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
			return $return_data;
		}
		// is the nick registered?
		
		if ( substr_count( $host, '@' ) == 1 )
		{
			$realhost = $host;
			$new_host = explode( '@', $host );
			$ident = $new_host[0];
			$host = $new_host[1];
		}
		elseif ( substr_count( $host, '@' ) > 1 || services::valid_host( $host ) === false )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_HOSTNAME );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_HOSTNAME;
			return $return_data;
		}
		else
			$realhost = $host;
		// check if there is a @
		
		database::update( 'users', array( 'vhost' => $realhost ), array( 'display', '=', $user->display ) );
		core::alog( core::$config->operserv->nick.': vHost for ('.$unick.') set to ('.$realhost.') by ('.$input['hostname'].') ('.$input['account'].')' );
		// update it and log it
		
		$lunick = strtolower( $unick );
		while ( list( $dnick, $data ) = each( core::$nicks ) )
		{
			if ( strtolower( $data['account'] ) != $lunick )
				continue;
			if ( !nickserv::check_flags( $data['account'], array( 'H' ) ) )
				break;
			// find the user, and check if they have +H	
			
			if ( substr_count( $realhost, '@' ) == 1 )
			{
				ircd::setident( core::$config->nickserv->nick, $dnick, $ident );
				ircd::sethost( core::$config->nickserv->nick, $dnick, $host );
			}
			else
				ircd::sethost( core::$config->nickserv->nick, $dnick, $host );
		}
		reset( core::$nicks );
		// we need to check if the user is online and identified?
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_SET, array( 'nick' => $unick, 'host' => $realhost ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// log this and return.
	}
	
	/*
	* _del_vhost (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $unick - The owner of the vhost
	*/
	static public function _del_vhost( $input, $nick, $unick )
	{
		$return_data = module::$return_data;
		
		if ( trim( $unick ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'VHOST' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// are we missing nick? invalid syntax if so.
		
		if ( !$user = services::user_exists( $unick, false, array( 'display', 'id', 'vhost' ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_ISNT_REGISTERED, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NICK_UNREGISTERED;
			return $return_data;
		}
		// is the nick registered?
		
		if ( $user->vhost == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_NO_VHOST, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NO_VHOST;
			return $return_data;
		}
		// is there a vhost?!
					
		database::update( 'users', array( 'vhost' => '' ), array( 'display', '=', $user->display ) );
		core::alog( core::$config->operserv->nick.': vHost for ('.$unick.') deleted by ('.$input['hostname'].') ('.$input['account'].')' );
		// update and logchan
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_DELETED, array( 'nick' => $unick ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// log this and return.
	}
	
	/*
	* _list_vhost (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $limit - vhost limit
	* $mode - mode, either like, approved or something
	*/
	static public function _list_vhost( $input, $nick, $limit, $nmode )
	{
		$return_data = module::$return_data;
		
		if ( trim( $limit ) == '' || !preg_match( '/([0-9]+)\-([0-9]+)/i', $limit ) || isset( $nmode ) && ( !in_array( $nmode, array( '', 'pending' ) ) ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'VHOST' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// invalid syntax
		
		if ( $nmode == '' )
			$total = database::select( 'users', array( 'id' ), array( 'vhost', '!=', '' ) );
		else
			$total = database::select( 'vhost_request', array( 'id' ) );
		$total = database::num_rows( $total );
		// get the total
		
		$limit = database::quote( $limit );
		$s_limit = explode( '-', $limit );
		$offset = $s_limit[0];
		$max = $s_limit[1];
		// split up the limit and stuff ^_^
		
		if ( $nmode == '' )
			$users_q = database::select( 'users', array( 'display', 'vhost' ), array( 'vhost', '!=', '' ), '', array( $offset => $max ) );
		else
			$users_q = database::select( 'vhost_request', array( 'nickname', 'vhost' ), '', '', array( $offset => $max ) );
		// get the vhosts
		
		if ( database::num_rows( $users_q ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_LIST_B, array( 'num' => database::num_rows( $users_q ), 'total' => $total ) );
			$return_data[CMD_SUCCESS] = true;
			return $return_data;
		}
		// no vhosts
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_LIST_T );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_LIST_D );
		// list top.
		
		$x = 0;
		while ( $users = database::fetch( $users_q ) )
		{
			$x++;
			$false_nick = ( $nmode == '' ) ? $users->display : $users->nickname;
			$num = explode( '-', $limit );
			$num = $num[0] + $x;
			
			$y_i = strlen( $num );
				for ( $i_i = $y_i; $i_i <= 5; $i_i++ )
					$num .= ' ';
			
			if ( !isset( $false_nick[18] ) )
			{
				$y = strlen( $false_nick );
				for ( $i = $y; $i <= 17; $i++ )
					$false_nick .= ' ';
			}
			// this is just a bit of fancy fancy, so everything displays neat
			
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_LIST_R, array( 'num' => $num, 'nick' => $false_nick, 'info' => $users->vhost ) );
			$return_data[CMD_DATA][] = array( 'nick' => ( $nmode == '' ) ? $users->display : $users->nickname, 'vhost' => $users->vhost );
		}
		// loop through em, show the vhosts
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_LIST_D );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_LIST_B, array( 'num' => ( database::num_rows( $users_q ) == 0 ) ? 0 : database::num_rows( $users_q ), 'total' => $total ) );
		// end of list.
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// log this and return.
	}
	
	/*
	* _approve_vhost (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $unick - The owner of the vhost to approve
	*/
	static public function _approve_vhost( $input, $nick, $unick )
	{
		$return_data = module::$return_data;
		
		if ( trim( $unick ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'VHOST' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// are we missing nick? invalid syntax if so.
		
		$users_q = database::select( 'vhost_request', array( 'nickname', 'vhost' ), array( 'nickname', '=', $unick ) );
		if ( database::num_rows( $users_q ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_NO_REQ, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NO_VHOST_REQUEST;
			return $return_data;
		}
		// no request?
		
		$user = database::fetch( $users_q );
		database::delete( 'vhost_request', array( 'nickname', '=', $user->nickname ) );
		database::update( 'users', array( 'vhost' => $user->vhost ), array( 'display', '=', $user->nickname ) );
		core::alog( core::$config->operserv->nick.': vHost for ('.$unick.') approved ('.$user->vhost.') by ('.$input['hostname'].') ('.$input['account'].')' );
		// update it and log it
		
		$lunick = strtolower( $unick );
		while ( list( $dnick, $data ) = each( core::$nicks ) )
		{
			if ( strtolower( $data['account'] ) != $lunick )
				continue;
			if ( !nickserv::check_flags( $user->nickname, array( 'H' ) ) )
				break;
			// find the user, and check if they have +H
			
			if ( substr_count( $user->vhost, '@' ) == 1 )
			{
				$split = explode( '@', $user->vhost );
				ircd::setident( core::$config->nickserv->nick, $dnick, $split[0] );
				ircd::sethost( core::$config->nickserv->nick, $dnick, $split[1] );
			}
			else
				ircd::sethost( core::$config->nickserv->nick, $dnick, $user->vhost );
		}
		reset( core::$nicks );
		// we need to check if the user is online and identified?
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_APPROVE, array( 'nick' => $unick ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// log this and return.
	}
	
	/*
	* _reject_vhost (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $unick - The owner of the vhost to reject
	*/
	static public function _reject_vhost( $input, $nick, $unick )
	{
		$return_data = module::$return_data;
		
		if ( trim( $unick ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'VHOST' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// are we missing nick? invalid syntax if so.
		
		$users_q = database::select( 'vhost_request', array( 'nickname', 'vhost' ), array( 'nickname', '=', $unick ) );
		if ( database::num_rows( $users_q ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_NO_REQ, array( 'nick' => $unick ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NO_VHOST_REQUEST;
			return $return_data;
		}
		// no request?
		
		$user = database::fetch( $users_q );
		database::delete( 'vhost_request', array( 'nickname', '=', $user->nickname ) );
		core::alog( core::$config->operserv->nick.': vHost for ('.$unick.') rejected ('.$user->vhost.') by ('.$input['hostname'].') ('.$input['account'].')' );
		// update it and log it
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_VHOST_REJECTED, array( 'nick' => $unick ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// log this and return.
	}
}

// EOF;