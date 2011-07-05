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
		modules::init_module( 'os_logonnews', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'default' );
		// these are standard in module constructors
		
		operserv::add_help( 'os_logonnews', 'help', operserv::$help->OS_HELP_LOGONNEWS_1, true, 'global_op' );
		operserv::add_help( 'os_logonnews', 'help logonnews', operserv::$help->OS_HELP_LOGONNEWS_ALL, false, 'global_op' );
		// add the help
		
		operserv::add_command( 'logonnews', 'os_logonnews', 'logonnews_command' );
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
		if ( strtolower( $ircdata[0] ) == 'add' )
		{
			$title = $ircdata[1];
			$text = core::get_data_after( $ircdata, 2 );
			
			if ( trim( $title ) == '' || trim( $text ) == '' )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'LOGONEWS' ) );
				// wrong syntax
				return false;
			}
			
			if ( !services::oper_privs( $nick, 'global_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// access?
			
			self::_add_news( $nick, $title, $text );
			// add a news article
		}
		elseif ( strtolower( $ircdata[0] ) == 'del' )
		{
			$title = $ircdata[1];
			
			if ( trim( $title ) == '' )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'LOGONEWS' ) );
				// wrong syntax
				return false;
			}
			
			if ( !services::oper_privs( $nick, 'global_op' ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
				return false;
			}
			// access?
			
			self::_del_news( $nick, $title );
			// delete a news article, FROM the title.
		}
		elseif ( strtolower( $ircdata[0] ) == 'list' )
		{
			self::_list_news( $nick );
			// list the news
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
	static public function on_connect( $connect_data, $startup )
	{
		if ( $startup )
			return false;
		
		$nick = $connect_data['nick'];
		
		$get_news = database::select( 'logon_news', array( 'nick', 'title', 'message', 'time' ), '', array( 'time' => 'DESC' ), array( 0 => 3 ) );
		// get our news
		
		if ( database::num_rows( $get_news ) > 0 )
		{
			while ( $news = database::fetch( $get_news ) )
			{
				services::communicate( core::$config->global->nick, $nick, operserv::$help->OS_LOGON_NEWS_1, array( 'title' => $news->title, 'user' => $news->nick, 'date' => date( "F j, Y, g:i a", $news->time ) ) );
				services::communicate( core::$config->global->nick, $nick, operserv::$help->OS_LOGON_NEWS_2, array( 'message' => $news->message ) );
			}
			// loop through the news
		}
		// there is news! epic
	}
	
	/*
	* _add_news (private)
	* 
	* @params
	* $nick - The nick commandeeer
	* $title - Article title
	* $text - Actual message
	*/
	static public function _add_news( $nick, $title, $text )
	{
		$check = database::select( 'logon_news', array( 'title' ), array( 'title', '=', $title ) );
		
		if ( database::num_rows( $check ) == 0 )
		{
			database::insert( 'logon_news', array( 'nick' => $nick, 'time' => core::$network_time, 'title' => $title, 'message' => $text ) );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_LOGONNEWS_ADD );
			core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') added a logon news message entitled ('.$title.')' );
			// as simple, as.
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_LOGONNEWS_EXISTS );
		}
		// let's check if an article with a similar title exists.
	}
	
	/*
	* _del_news (private)
	* 
	* @params
	* $nick - The nick commandeeer
	* $title - Article title
	*/
	static public function _del_news( $nick, $title )
	{
		$check = database::select( 'logon_news', array( 'title' ), array( 'title', '=', $title ) );
		
		if ( database::num_rows( $check ) > 0 )
		{
			database::delete( 'logon_news', array( 'title', '=', $title ) );
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_LOGONNEWS_DEL, array( 'title' => $title ) );
			core::alog( core::$config->operserv->nick.': ('.core::get_full_hostname( $nick ).') ('.core::$nicks[$nick]['account'].') deleted ('.$title.') from logonnews' );
			// as simple, as.
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_LOGONNEWS_NONE );
		}
		// let's check if we can find what they are lookin phowar
	}
	
	/*
	* _list_news (private)
	* 
	* @params
	* $nick - The nick commandeeer
	*/
	static public function _list_news( $nick )
	{
		$get_news = database::select( 'logon_news', array( 'nick', 'title', 'message', 'time' ), '', array( 'time' => 'DESC' ) );
		// get our news
			
		if ( database::num_rows( $get_news ) > 0 )
		{
			while ( $news = database::fetch( $get_news ) )
			{
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_LOGON_NEWS_1, array( 'title' => $news->title, 'user' => $news->nick, 'date' => date( "F j, Y, g:i a", $news->time ) ) );
				services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_LOGON_NEWS_2, array( 'message' => $news->message ) );
			}
			// loop through the news
		}
		else
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_LOGONNEWS_EMPTY );
		}
		// there is news! epic
	}
}

// EOF;