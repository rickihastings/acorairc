<?php

/*
* Acora IRC Services
* modules/help.cs.php: ChanServ help module
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* This project is licensed under the GNU Public License
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class cs_help implements module
{
	
	const MOD_VERSION = '0.0.2';
	const MOD_AUTHOR = 'Acora';
	
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
		modules::init_module( 'cs_help', self::MOD_VERSION, self::MOD_AUTHOR, 'chanserv', 'static' );
		// these are standard in module constructors
		
		chanserv::add_help_fix( 'cs_help', 'prefix', 'help', &chanserv::$help->CS_HELP_PREFIX );
		chanserv::add_help_fix( 'cs_help', 'suffix', 'help', &chanserv::$help->CS_HELP_SUFFIX );
		// add teh help docs
	}
	
	/*
	* main (event hook)
	* 
	* @params
	* $ircdata - ''
	*/
    public function main( &$ircdata, $startup = false )
	{
		if ( ircd::on_msg( &$ircdata, core::$config->chanserv->nick ) )
		{
			$nick = core::get_nick( &$ircdata, 0 );
			$query = substr( core::get_data_after( &$ircdata, 3 ), 1 );
			// convert to lower case because all the tingy wags are in lowercase
			$query = strtolower( $query );
			
			chanserv::get_help( $nick, $query );
		}
		// only hook to the privmsg towards ChanServ, not channel messages
		// although chanserv shouldn't even be in any channels :P
	}
    
}

// EOF;