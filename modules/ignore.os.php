<?php

/*
* Acora IRC Services
* modules/ignore.os.php: OperServ ignore module
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

class os_ignore implements module
{
	
	const MOD_VERSION = '0.0.3';
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
		modules::init_module( 'os_ignore', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_ignore', 'help', &operserv::$help->OS_HELP_IGNORE_1 );
		operserv::add_help( 'os_ignore', 'help ignore', &operserv::$help->OS_HELP_IGNORE_ALL );
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
		if ( strtolower( $ircdata[0] ) == 'add' )
		{
			$who = $ircdata[1];
		
			if ( trim( $who ) == '' )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX );
				return false;
			}
			// wrong syntax
			
			self::_add_user( $nick, $who );
			// $who is the user we're adding REMEMBER!
		}
		elseif ( strtolower( $ircdata[0] ) == 'del' )
		{
			$who = $ircdata[1];
		
			if ( trim( $who ) == '' )
			{
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX );
				return false;
			}
			// wrong syntax
			
			self::_del_user( $nick, $who );
			// again $who is the user we're deleting.
		}
		elseif ( strtolower( $ircdata[0] ) == 'list' )
		{
			self::_list_users( $nick );
			// basic shiz, no checking, no parameter command
		}
		elseif ( strtolower( $ircdata[0] ) == 'clear' )
		{
			self::_clear_users( $nick );
			// again no params, straight cmd
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_INVALID_SYNTAX );
			// wrong syntax
			return false;
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
	
	/*
	* _add_user (private)
	* 
	* @params
	* $nick - The nick commandeeer
	* $who - The nick to add
	*/
	static public function _add_user( $nick, $who )
	{
		$check_nick_q = database::select( 'ignored_users', array( 'who' ), array( 'who', '=', $who ) );
		
		if ( services::is_root( $who ) && !services::is_root( $nick ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, &operserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// is a non-root trying to drop a root?
			
		if ( database::num_rows( $check_nick_q ) == 0 )
		{
			if ( strpos( $who, '@' ) !== false && strpos( $who, '!' ) === false )
				$who = '*!'.$who;
			// we need to check if it's a hostmask thats been written properly.
			
			database::insert( 'ignored_users', array( 'who' => $who, 'time' => core::$network_time ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_IGNORE_ADD, array( 'nick' => $who ) );
			core::alog( core::$config->operserv->nick.': '.$nick.' added '.$who.' to services ignore list' );
			// as simple, as.
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_IGNORE_EXISTS, array( 'nick' => $who ) );
			// already being ignored? :O NEVER!
		}
	}
	
	/*
	* _del_user (private)
	* 
	* @params
	* $nick - The nick commandeeer
	* $who - The nick to del
	*/
	static public function _del_user( $nick, $who )
	{
		if ( strpos( $who, '@' ) !== false && strpos( $who, '!' ) === false )
			$who = '*!'.$who;
		// we need to check if it's a hostmask thats been written properly.
		
		$check_nick_q = database::select( 'ignored_users', array( 'who' ), array( 'who', '=', $who ) );
			
		if ( database::num_rows( $check_nick_q ) > 0 )
		{
			database::delete( 'ignored_users', array( 'who', '=', $who ) );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_IGNORE_DEL, array( 'nick' => $who ) );
			core::alog( core::$config->operserv->nick.': '.$nick.' deleted '.$who.' from the services ignore list' );
			// as simple, as.
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_IGNORE_NONE );
			// empty list.
		}
	}
	
	/*
	* _list_users (private)
	* 
	* @params
	* $nick - The nick commandeeer
	*/
	static public function _list_users( $nick )
	{
		$check_nick_q = database::select( 'ignored_users', array( 'who', 'time' ) );
		
		if ( database::num_rows( $check_nick_q ) > 0 )
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_IGNORE_LIST_T );
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_IGNORE_LIST_T2 );
			// t-o-l
			
			while ( $ignored = database::fetch( $check_nick_q ) )
			{
				$false_nick = $ignored->who;
				
				if ( !isset( $ignored->who[18] ) )
				{
					$y = strlen( $ignored->who );
					for ( $i = $y; $i <= 17; $i++ )
						$false_nick .= ' ';
				}
				// this is just a bit of fancy fancy, so everything displays neat
				
				services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_IGNORE_LIST, array( 'nick' => $false_nick, 'time' => date( "F j, Y, g:i a", $ignored->time ) ) );
			}
			// loop through the records
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_IGNORE_EMPTY );
			// empty list.
		}
	}
	
	/*
	* _clear_users (private)
	* 
	* @params
	* $nick - The nick commandeeer
	*/
	static public function _clear_users( $nick )
	{
		database::delete( 'ignored_users' );
		services::communicate( core::$config->operserv->nick, $nick, &operserv::$help->OS_IGNORE_CLEARED, array( 'users' => database::num_rows( $nicks_q ) ) );
		// list cleared.
	}
}
// EOF;