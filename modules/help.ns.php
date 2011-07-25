<?php

/*
* Acora IRC Services
* modules/help.ns.php: NickServ help module
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

class ns_help extends module
{
	
	const MOD_VERSION = '0.0.1';
	const MOD_AUTHOR = 'Acora';
	
	/*
	* modload (private)
	* 
	* @params
	* void
	*/
	static public function modload()
	{
		modules::init_module( 'ns_help', self::MOD_VERSION, self::MOD_AUTHOR, 'nickserv', 'static' );
		// these are standard in module constructors
		
		nickserv::add_help_fix( 'ns_help', 'prefix', 'help', nickserv::$help->NS_HELP_PREFIX );
		nickserv::add_help_fix( 'ns_help', 'suffix', 'help', nickserv::$help->NS_HELP_SUFFIX );
		// add teh help docs
	}
	
	/*
	* on_msg (event hook)
	*/
	static public function on_msg( $nick, $target, $msg )
	{
		if ( $target != core::$config->nickserv->nick )
			return false;
			
		$query = substr( $msg, 1 );
		// convert to lower case because all the tingy wags are in lowercase
		$query = strtolower( $query );
		
		nickserv::get_help( $nick, $query );
	}
}

// EOF;