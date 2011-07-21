<?php

/*
* Acora IRC Services
* modules/list.cs.php: ChanServ list module
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

class cs_list extends module
{
	
	const MOD_VERSION = '0.1.5';
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
	public function modload()
	{
		modules::init_module( 'cs_list', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_list', 'help', chanserv::$help->CS_HELP_LIST_1, true, 'chanserv_op' );
		chanserv::add_help( 'cs_list', 'help list', chanserv::$help->CS_HELP_LIST_ALL, false, 'chanserv_op' );
		// add the help
		
		chanserv::add_command( 'list', 'cs_list', 'list_command' );
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
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// they've gotta be identified and opered..
		
		$mode = ( isset( $ircdata[2] ) ) ? strtolower( $ircdata[2] ) : '';
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_list_chans( $input, $nick, $ircdata[0], $ircdata[1], $mode );
		// call the list chans function :3
		
		services::respond( core::$config->chanserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _list_chans (command)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $term - The search term
	* $limit - Valid limit, ie 0-10
	* $mode - SUSPENDED etc
	*/
	static public function _list_chans( $input, $nick, $term, $limit, $mode )
	{
		$return_data = module::$return_data;
	
		if ( ( trim( $term ) == '' || trim( $limit ) == '' ) || isset( $mode ) && ( !in_array( $mode, array( '', 'suspended' ) ) ) || !preg_match( '/([0-9]+)\-([0-9]+)/i', $limit ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'LIST' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// invalid syntax
		
		$total = database::select( 'chans', array( 'id' ) );
		$total = database::num_rows( $total );
		$chans = self::_find_match( $term, $mode, $limit );
		// try and find a match
		
		if ( database::num_rows( $chans ) == 0 )
		{
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LIST_BOTTOM, array( 'num' => 0, 'total' => $total ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->LIST_EMPTY;
			return $return_data;
		}
		// no channels?
		
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LIST_TOP );
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LIST_DLM );
		// top of the list
		
		$x = 0;
		while ( $channel = database::fetch( $chans ) )
		{
			$x++;
			$false_chan = $channel->channel;
			$x_s = explode( '-', $limit );
			$x_s = $x_s[0] + $x;
			
			$y_s = strlen( $x_s );
			for ( $i_s = $y_s; $i_s < 5; $i_s++ )
				$x_s .= ' ';
			
			if ( !isset( $channel->channel[18] ) )
			{
				$y = strlen( $channel->channel );
				for ( $i = $y; $i <= 17; $i++ )
					$false_chan .= ' ';
			}
			// this is just a bit of fancy fancy, so everything displays neat
			
			$desc = chanserv::get_flags( $channel->channel, 'd' );
			if ( $channel->suspended == 0 ) $info = $desc;
			else $info = $channel->suspend_reason;
			// suspend reason, or description?
			
			$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LIST_ROW, array( 'num' => $x_s, 'chan' => $false_chan, 'info' => '['.$info.']' ) );
			$return_data[CMD_DATA][] = array( 'chan' => $channel->channel, 'desc' => $desc, 'suspended' => $channel->suspended, 'suspend_reason' => $channel->suspend_reason );
		}
		// loop through the channels
		
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LIST_DLM );
		$return_data[CMD_RESPONSE][] = services::parse( chanserv::$help->CS_LIST_BOTTOM, array( 'num' => ( database::num_rows( $chans ) == 0 ) ? 0 : database::num_rows( $chans ), 'total' => $total ) );
		// setup the responses
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return the data back
	}

	/*
	* _find_match (private)
	*
	* @params
	* $term - should be.. lobby* etc. *
	* $mode - should be SUSPENDED, or blank
	* $limit - should be 0-10, 30-10 etc, (offset-max)
	*/
	static public function _find_match( $term, $mode, $limit )
	{
		$new_term = str_replace( '*', '%', $term );
		// search for a channel name
		// allow the ability to search with "*"'s
		
		$limit = database::quote( $limit );
		$s_limit = explode( '-', $limit );
		$offset = $s_limit[0];
		$max = $s_limit[1];
		// split up the limit and stuff ^_^
			
		if ( $mode == 'suspended' )
			$results = database::select( 'chans', array( 'id', 'channel', 'suspended', 'suspend_reason' ), array( 'suspended', '=', '1', 'AND', 'channel', 'LIKE', $new_term ), '', array( $offset => $max ) );
		else
			$results = database::select( 'chans', array( 'id', 'channel', 'suspended', 'suspend_reason' ), array( 'suspended', '=', '0', 'AND', 'channel', 'LIKE', $new_term ), '', array( $offset => $max ) );	
			
		return $results;	
	}
}

// EOF;