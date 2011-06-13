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

class cs_list implements module
{
	
	const MOD_VERSION = '0.0.4';
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
		modules::init_module( 'cs_list', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'default' );
		// these are standard in module constructors
		
		chanserv::add_help( 'cs_list', 'help', chanserv::$help->CS_HELP_LIST_1, true );
		chanserv::add_help( 'cs_list', 'help list', chanserv::$help->CS_HELP_LIST_ALL, true );
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
		$term = $ircdata[0];
		$limit = $ircdata[1];
		$mode = ( isset( $ircdata[2] ) ) ? strtolower( $ircdata[2] ) : '';
		
		if ( !core::$nicks[$nick]['ircop'] || !core::$nicks[$nick]['identified'] )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_ACCESS_DENIED );
			return false;
		}
		// they've gotta be identified and opered..
		
		if ( ( trim( $term ) == '' || trim( $limit ) == '' ) || isset( $mode ) && ( !in_array( $mode, array( '', 'suspended' ) ) ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'LIST' ) );
			return false;
		}
		// invalid syntax
		
		if ( !preg_match( '/([0-9]+)\-([0-9]+)/i', $limit ) )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_INVALID_SYNTAX_RE, array( 'help' => 'LIST' ) );
			return false;
		}
		// invalid syntax
		
		$total = database::select( 'chans', array( 'id' ) );
		$total = database::num_rows( $total );
		$chans = self::_find_match( $term, $mode, $limit );
		// try and find a match
		
		if ( database::num_rows( $chans ) == 0 )
		{
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LIST_BOTTOM, array( 'num' => 0, 'total' => $total ) );
			return false;
		}
		// no channels?
		
		services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LIST_TOP );
		services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LIST_DLM );
		// top of the list
		
		$x = 0;
		while ( $channel = database::fetch( $chans ) )
		{
			$x++;
			$false_chan = $channel->channel;
			$x_s = $x;
			
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
			
			if ( $channel->suspended == 0 )
				$info = '['.chanserv::get_flags( $channel->channel, 'd' ).']';
			else
				$info = '['.$channel->suspend_reason.']';
			// suspend reason, or description?
			
			services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LIST_ROW, array( 'num' => $x_s, 'chan' => $false_chan, 'info' => $info ) );
		}
		// loop through the channels
		
		services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LIST_DLM );
		services::communicate( core::$config->chanserv->nick, $nick, chanserv::$help->CS_LIST_BOTTOM, array( 'num' => ( database::num_rows( $chans ) == 0 ) ? 0 : database::num_rows( $chans ), 'total' => $total ) );
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