<?php

/*
* Acora IRC Services
* modules/list.ns.php: NickServ list module
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

class ns_list extends module
{
	
	const MOD_VERSION = '0.0.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	/*
	* modload (private)
	* 
	* @params
	* void
	*/
	public function modload()
	{
		modules::init_module( 'ns_list', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'default' );
		// these are standard in module constructors
		
		nickserv::add_help( 'ns_list', 'help', nickserv::$help->NS_HELP_LIST_1, true, 'nickserv_op' );
		nickserv::add_help( 'ns_list', 'help list', nickserv::$help->NS_HELP_LIST_ALL, false, 'nickserv_op' );
		// add the help
		
		nickserv::add_command( 'list', 'ns_list', 'list_command' );
		// add the list command
	}
	
	/*
	* list_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function list_command( $nick, $ircdata = array() )
	{
		if ( !core::$nicks[$nick]['ircop'] || !core::$nicks[$nick]['identified'] )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_ACCESS_DENIED );
			return false;
		}
		// they've gotta be identified and opered..
		
		$mode = ( isset( $ircdata[2] ) ) ? strtolower( $ircdata[2] ) : '';
		self::_list_nicks( $nick, $ircdata[0], $ircdata[1], $mode );
		// call _list_nicks
	}
	
	/*
	* _list_nicks (private)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $term - The term to search by
	* $limit - The limit, ie 0-10
	* $mode - extra modes, ie SUSPENDED
	*/
	static public function _list_nicks( $nick, $term, $limit, $mode )
	{
		if ( ( trim( $term ) == '' || trim( $limit ) == '' ) || ( isset( $mode ) && ( !in_array( $mode, array( '', 'suspended' ) ) ) ) || !preg_match( '/([0-9]+)\-([0-9]+)/i', $limit ) )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'LIST' ) );
			return false;
		}
		// invalid syntax
		
		$total = database::select( 'users', array( 'id' ) );
		$total = database::num_rows( $total );
		$nicks = self::_find_match( $term, $mode, $limit );
		// try and find a match
		
		if ( database::num_rows( $nicks ) == 0 )
		{
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_LIST_BOTTOM, array( 'num' => 0, 'total' => $total ) );
			return false;
		}
		// no nicks?
		
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_LIST_TOP );
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_LIST_DLM );
		// top of list.
		
		$x = 0;
		while ( $user = database::fetch( $nicks ) )
		{
			$x++;
			$false_nick = $user->display;
			$x_s = explode( '-', $limit );
			$x_s = $x_s[0] + $x;
			
			$y_s = strlen( $x_s );
			for ( $i_s = $y_s; $i_s < 5; $i_s++ )
				$x_s .= ' ';
				
			if ( !isset( $user->display[18] ) )
			{
				$y = strlen( $user->display );
				for ( $i = $y; $i <= 17; $i++ )
					$false_nick .= ' ';
			}
			// this is just a bit of fancy fancy, so everything displays neat
			
			if ( $user->suspended == 0 )
			{
				$hostmask = explode( '!', $user->last_hostmask );
				$info = '['.$hostmask[1].']';
			}
			else
				$info = '[*@*] ['.$user->suspend_reason.']';
			
			services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_LIST_ROW, array( 'num' => $x_s, 'nick' => $false_nick, 'info' => $info ) );
		}
		// loop through the nicks
		
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_LIST_DLM );
		services::communicate( core::$config->nickserv->nick, $nick, nickserv::$help->NS_LIST_BOTTOM, array( 'num' => ( database::num_rows( $nicks ) == 0 ) ? 0 : database::num_rows( $nicks ), 'total' => $total ) );
	}
	
	/*
	* _find_match (private)
	*
	* @params
	* $term - should be.. N0va* etc. *
	* $mode - should be SUSPENDED, or blank
	* $limit - should be 0-10, 30-10 etc, (offset-max)
	*/
	static public function _find_match( $term, $mode, $limit )
	{
		$new_term = str_replace( '*', '%', $term );
		// search for a nickname
		// allow the ability to search with "*"'s
		
		$limit = database::quote( $limit );
		$s_limit = explode( '-', $limit );
		$offset = $s_limit[0];
		$max = $s_limit[1];
		// split up the limit and stuff ^_^
			
		if ( $mode == 'suspended' )
			$results = database::select( 'users', array( 'id', 'display', 'last_hostmask', 'suspended', 'suspend_reason' ), array( 'suspended', '=', '1', 'AND', 'display', 'LIKE', $new_term ), '', array( $offset => $max ) );
		else
			$results = database::select( 'users', array( 'id', 'display', 'last_hostmask', 'suspended', 'suspend_reason' ), array( 'suspended', '=', '0', 'AND', 'display', 'LIKE', $new_term ), '', array( $offset => $max ) );	
			
		return $results;
	}
}

// EOF;