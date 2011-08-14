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
	
	const MOD_VERSION = '0.1.4';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'LIST_EMPTY'		=> 2,
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
		
		commands::add_help( 'nickserv', 'ns_list', 'help', nickserv::$help->NS_HELP_LIST_1, true, 'nickserv_op' );
		commands::add_help( 'nickserv', 'ns_list', 'help list', nickserv::$help->NS_HELP_LIST_ALL, false, 'nickserv_op' );
		// add the help
		
		commands::add_command( 'nickserv', 'list', 'ns_list', 'list_command' );
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
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_list_nicks( $input, $nick, $ircdata[0], $ircdata[1], $mode );
		// call _list_nicks
		
		services::respond( core::$config->nickserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _list_nicks (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $term - The term to search by
	* $limit - The limit, ie 0-10
	* $mode - extra modes, ie SUSPENDED
	*/
	static public function _list_nicks( $input, $nick, $term, $limit, $mode )
	{
		$return_data = module::$return_data;
		if ( ( trim( $term ) == '' || trim( $limit ) == '' ) || ( isset( $mode ) && ( !in_array( $mode, array( '', 'suspended' ) ) ) ) || !preg_match( '/([0-9]+)\-([0-9]+)/i', $limit ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_INVALID_SYNTAX_RE, array( 'help' => 'LIST' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// invalid syntax
		
		$total = database::select( 'users', array( 'id' ) );
		$total = database::num_rows( $total );
		$nicks = self::_find_match( $term, $mode, $limit );
		// try and find a match
		
		if ( database::num_rows( $nicks ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_LIST_BOTTOM, array( 'num' => 0, 'total' => $total ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->LIST_EMPTY;
			return $return_data;
		}
		// no nicks?
		
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_LIST_TOP );
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_LIST_DLM );
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
			
			$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_LIST_ROW, array( 'num' => $x_s, 'nick' => $false_nick, 'info' => $info ) );
			$return_data[CMD_DATA][] = array( 'nick' => $user->display, 'hostmask' => $user->last_hostmask, 'suspended' => $user->suspended, 'suspend_reason' => $user->suspend_reason );
		}
		// loop through the nicks
		
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_LIST_DLM );
		$return_data[CMD_RESPONSE][] = services::parse( nickserv::$help->NS_LIST_BOTTOM, array( 'num' => ( database::num_rows( $nicks ) == 0 ) ? 0 : database::num_rows( $nicks ), 'total' => $total ) );
		// show list
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
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
