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
		
		operserv::add_help( 'os_ignore', 'help', operserv::$help->OS_HELP_IGNORE_1, 'global_op' );
		operserv::add_help( 'os_ignore', 'help ignore', operserv::$help->OS_HELP_IGNORE_ALL, 'global_op' );
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
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'IGNORE' ) );
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
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'IGNORE' ) );
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
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'IGNORE' ) );
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
	public function main( $ircdata, $startup = false )
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
		
		if ( services::has_privs( $who ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// cant ignore someone with privs, regardless of what privs you have.
			
		if ( database::num_rows( $check_nick_q ) == 0 )
		{
			if ( strpos( $who, '@' ) !== false && strpos( $who, '!' ) === false )
				$who = '*!'.$who;
			// we need to check if it's a hostmask thats been written properly.
			
			database::insert( 'ignored_users', array( 'who' => $who, 'time' => core::$network_time, 'temp' => '0' ) );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_IGNORE_ADD, array( 'nick' => $who ) );
			core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') added ('.$who.') to services ignore list' );
			// as simple, as.
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_IGNORE_EXISTS, array( 'nick' => $who ) );
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
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_IGNORE_DEL, array( 'nick' => $who ) );
			core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') deleted ('.$who.') from the services ignore list' );
			// as simple, as.
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_IGNORE_NONE );
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
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_IGNORE_LIST_T );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_IGNORE_LIST_D );
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
				
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_IGNORE_LIST, array( 'num' => $num, 'nick' => $false_nick, 'time' => date( "F j, Y, g:i a", $ignored->time ) ) );
			}
			// loop through the records
			
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_IGNORE_LIST_D );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_IGNORE_LIST_B, array( 'num' => $x ) );
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_IGNORE_EMPTY );
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
		services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_IGNORE_CLEARED, array( 'users' => database::num_rows( $nicks_q ) ) );
		// list cleared.
	}
}
// EOF;