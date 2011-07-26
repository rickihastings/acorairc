<?php

/*
* Acora IRC Services
* modules/ignore.os.php: OperServ ignore module
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

class os_ignore extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'ACCESS_DENIED'		=> 2,
		'IGNORE_EXISTS'		=> 3,
		'IGNORE_NONE'		=> 4,
		'IGNORE_LIST_EMPTY' => 5,
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
		modules::init_module( 'os_ignore', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		operserv::add_help( 'os_ignore', 'help', operserv::$help->OS_HELP_IGNORE_1, true );
		operserv::add_help( 'os_ignore', 'help ignore', operserv::$help->OS_HELP_IGNORE_ALL, false );
		// add the help
		
		operserv::add_command( 'ignore', 'os_ignore', 'ignore_command' );
		// add the ignore command
	}
	
	/*
	* ignore_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function ignore_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
	
		if ( strtolower( $ircdata[0] ) == 'add' )
		{
			if ( !services::oper_privs( $nick, 'global_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// you have to be root to add/del an akill
		
			$return_data = self::_add_user( $input, $nick, $ircdata[1] );
			// $who is the user we're adding REMEMBER!
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		elseif ( strtolower( $ircdata[0] ) == 'del' )
		{
			if ( !services::oper_privs( $nick, 'global_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// you have to be root to add/del an akill
		
			$return_data = self::_del_user( $input, $nick, $ircdata[1] );
			// again $who is the user we're deleting.
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		elseif ( strtolower( $ircdata[0] ) == 'list' )
		{
			$return_data = self::_list_users( $input );
			// basic shiz, no checking, no parameter command
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		elseif ( strtolower( $ircdata[0] ) == 'clear' )
		{
			$return_data = self::_clear_users( $input );
			// again no params, straight cmd
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'IGNORE' ) );
			// wrong syntax
			return false;
		}
	}
	
	/*
	* _add_user (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick commandeeer
	* $who - The nick to add
	*/
	static public function _add_user( $input, $nick, $who )
	{
		$return_data = module::$return_data;
	
		if ( trim( $who ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'IGNORE' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax

		if ( services::has_privs( $who ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_ACCESS_DENIED );
			$return_data[CMD_FAILCODE] = self::$return_codes->ACCESS_DENIED;
			return $return_data;
		}
		// cant ignore someone with privs, regardless of what privs you have.
			
		$check_nick_q = database::select( 'ignored_users', array( 'who' ), array( 'who', '=', $who ) );
		if ( database::num_rows( $check_nick_q ) != 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_IGNORE_EXISTS, array( 'nick' => $who ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->IGNORE_EXISTS;
			return $return_data;
		}
		
		if ( strpos( $who, '@' ) !== false && strpos( $who, '!' ) === false )
			$who = '*!'.$who;
		// we need to check if it's a hostmask thats been written properly.
		
		database::insert( 'ignored_users', array( 'who' => $who, 'time' => core::$network_time, 'temp' => '0' ) );
		core::alog( core::$config->operserv->nick.': ('.$input['hostname'].') ('.$input['account'].') added ('.$who.') to services ignore list' );
		// as simple, as.
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_IGNORE_ADD, array( 'nick' => $who ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* _del_user (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick commandeeer
	* $who - The nick to del
	*/
	static public function _del_user( $input, $nick, $who )
	{
		$return_data = module::$return_data;
	
		if ( trim( $who ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'IGNORE' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
			
		if ( strpos( $who, '@' ) !== false && strpos( $who, '!' ) === false )
			$who = '*!'.$who;
		// we need to check if it's a hostmask thats been written properly.
		
		$check_nick_q = database::select( 'ignored_users', array( 'who' ), array( 'who', '=', $who ) );
		if ( database::num_rows( $check_nick_q ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_IGNORE_NONE );
			$return_data[CMD_FAILCODE] = self::$return_codes->IGNORE_NONE;
			return $return_data;
		}
		// doesn't exist
		
		database::delete( 'ignored_users', array( 'who', '=', $who ) );
		core::alog( core::$config->operserv->nick.': ('.$input['hostname'].') ('.$input['account'].') deleted ('.$who.') from the services ignore list' );
		// as simple, as.
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_IGNORE_DEL, array( 'nick' => $who ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* _list_users (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	*/
	static public function _list_users( $input )
	{
		$return_data = module::$return_data;
		$check_nick_q = database::select( 'ignored_users', array( 'who', 'time' ) );
		
		if ( database::num_rows( $check_nick_q ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_IGNORE_LIST_B, array( 'num' => 0 ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->IGNORE_LIST_EMPTY;
			return $return_data;
		}
		// empty list.
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_IGNORE_LIST_T );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_IGNORE_LIST_D );
		// t-o-l
		
		$x = 0;
		while ( $ignored = database::fetch( $check_nick_q ) )
		{
			$x++;
			$false_nick = $ignored->who;
			
			$num = $x;
			$y_i = strlen( $num );
				for ( $i_i = $y_i; $i_i <= 5; $i_i++ )
					$num .= ' ';
			
			if ( !isset( $ignored->who[50] ) )
			{
				$y = strlen( $ignored->who );
				for ( $i = $y; $i <= 49; $i++ )
					$false_nick .= ' ';
			}
			// this is just a bit of fancy fancy, so everything displays neat
			
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_IGNORE_LIST, array( 'num' => $num, 'nick' => $false_nick, 'time' => date( "F j, Y, g:i a", $ignored->time ) ) );
			$return_data[CMD_DATA][] = array( 'nick' => $ignored->who, 'time' => $ignored->time );
		}
		// loop through the records
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_IGNORE_LIST_D );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_IGNORE_LIST_B, array( 'num' => $x ) );
		// display list
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* _clear_users (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	*/
	static public function _clear_users( $input )
	{
		$nicks_q = database::select( 'ignored_users', array( 'who', 'time' ) );
		database::delete( 'ignored_users' );
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_IGNORE_CLEARED, array( 'users' => database::num_rows( $nicks_q ) ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
}
// EOF;