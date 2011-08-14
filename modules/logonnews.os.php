<?php

/*
* Acora IRC Services
* modules/logonnews.os.php: OperServ logonnews module
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

class os_logonnews extends module
{
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'NEWS_EXISTS'		=> 2,
		'NEWS_NO_EXIST'		=> 3,
		'NEWS_LIST_EMPTY'	=> 4,
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
		modules::init_module( __CLASS__, self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		commands::add_help( 'operserv', 'os_logonnews', 'help', operserv::$help->OS_HELP_LOGONNEWS_1, true, 'global_op' );
		commands::add_help( 'operserv', 'os_logonnews', 'help logonnews', operserv::$help->OS_HELP_LOGONNEWS_ALL, false, 'global_op' );
		// add the help
		
		commands::add_command( 'operserv', 'logonnews', 'os_logonnews', 'logonnews_command' );
		// add the command
	}
	
	/*
	* logonnews_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function logonnews_command( $nick, $ircdata = array() )
	{
		$mode = strtolower( $ircdata[0] );
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		
		if ( $mode == 'add' )
		{
			if ( !services::oper_privs( $nick, 'global_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// access?
			
			$return_data = self::_add_news( $input, $nick, $ircdata[1], core::get_data_after( $ircdata, 2 ) );
			// add a news article
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		elseif ( $mode == 'del' )
		{
			if ( !services::oper_privs( $nick, 'global_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// access?
			
			$return_data = self::_del_news( $input, $nick, $ircdata[1] );
			// delete a news article, FROM the title.
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		elseif ( $mode == 'list' )
		{
			$return_data = self::_list_news( $input );
			// list the news
			
			services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
			return $return_data[CMD_SUCCESS];
			// respond and return
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'LOGONEWS' ) );
			// wrong syntax
			return false;
		}
	}
	
	/*
	* on_connect (event hook)
	*/
	static public function on_connect( $connect_data )
	{
		$get_news = database::select( 'logon_news', array( 'nick', 'title', 'message', 'time' ), '', array( 'time' => 'DESC' ), array( 0 => 3 ) );
		// get our news
		
		if ( database::num_rows( $get_news ) > 0 )
		{
			while ( $news = database::fetch( $get_news ) )
			{
				$response[] = services::parse( operserv::$help->OS_LOGON_NEWS_1, array( 'title' => $news->title, 'user' => $news->nick, 'date' => date( "F j, Y, g:i a", $news->time ) ) );
				$response[] = services::parse( operserv::$help->OS_LOGON_NEWS_2, array( 'message' => $news->message ) );
			}
			// loop through the news
			
			services::respond( core::$config->global->nick, $connect_data['nick'], $response );
		}
		// there is news! epic
	}
	
	/*
	* _add_news (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick commandeeer
	* $title - Article title
	* $text - Actual message
	*/
	static public function _add_news( $input, $nick, $title, $text )
	{
		$return_data = module::$return_data;
		if ( trim( $title ) == '' || trim( $text ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'LOGONNEWS' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
	
		$check = database::select( 'logon_news', array( 'title' ), array( 'title', '=', $title ) );
		if ( database::num_rows( $check ) != 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_LOGONNEWS_EXISTS );
			$return_data[CMD_FAILCODE] = self::$return_codes->NEWS_EXISTS;
			return $return_data;
		}
		// One already exists
		
		database::insert( 'logon_news', array( 'nick' => $nick, 'time' => core::$network_time, 'title' => $title, 'message' => $text ) );
		core::alog( core::$config->operserv->nick.': ('.$input['hostname'].') ('.$input['account'].') added a logon news message entitled ('.$title.')' );
		// as simple, as.
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_LOGONNEWS_ADD );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* _del_news (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick commandeeer
	* $title - Article title
	*/
	static public function _del_news( $input, $nick, $title )
	{
		$return_data = module::$return_data;
		if ( trim( $title ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'LOGONNEWS' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
		
		$check = database::select( 'logon_news', array( 'title' ), array( 'title', '=', $title ) );
		if ( database::num_rows( $check ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_LOGONNEWS_NONE );
			$return_data[CMD_FAILCODE] = self::$return_codes->NEWS_NO_EXIST;
			return $return_data;
		}
		// let's check if we can find what they are lookin phowar
		
		database::delete( 'logon_news', array( 'title', '=', $title ) );
		core::alog( core::$config->operserv->nick.': ('.$input['hostname'].') ('.$input['account'].') deleted ('.$title.') from logonnews' );
		// as simple, as.
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_LOGONNEWS_DEL, array( 'title' => $title ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}
	
	/*
	* _list_news (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	*/
	static public function _list_news( $input )
	{
		$return_data = module::$return_data;
		$get_news = database::select( 'logon_news', array( 'nick', 'title', 'message', 'time' ), '', array( 'time' => 'DESC' ) );
		// get our news
			
		if ( database::num_rows( $get_news ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_LOGONNEWS_EMPTY );
			$return_data[CMD_FAILCODE] = self::$return_codes->NEWS_EMPTY;
			return $return_data;
		}
		// no news pill
		
		while ( $news = database::fetch( $get_news ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_LOGON_NEWS_1, array( 'title' => $news->title, 'user' => $news->nick, 'date' => date( "F j, Y, g:i a", $news->time ) ) );
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_LOGON_NEWS_2, array( 'message' => $news->message ) );
			$return_data[CMD_DATA][] = array( 'title' => $news->title, 'user' => $news->nick, 'timestamp' => $news->time, 'message' => $news->message );
		}
		// loop through the news
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return shiz
	}
}

// EOF;
