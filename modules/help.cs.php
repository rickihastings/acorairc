<?php

/*
* Acora IRC Services
* modules/help.cs.php: ChanServ help module
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

class cs_help extends module
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
		
		chanserv::add_help_fix( 'cs_help', 'prefix', 'help', chanserv::$help->CS_HELP_PREFIX );
		chanserv::add_help_fix( 'cs_help', 'suffix', 'help', chanserv::$help->CS_HELP_SUFFIX );
		// add teh help docs
	}
	
	/*
	* on_msg (event hook)
	*/
	static public function on_msg( $nick, $target, $msg )
	{
		if ( $target != core::$config->chanserv->nick )
			return false;
			
		$query = substr( $msg, 1 );
		// convert to lower case because all the tingy wags are in lowercase
		$query = strtolower( $query );
		
		chanserv::get_help( $nick, $query );
	}
}

// EOF;