<?php

/*
* Acora IRC Services
* modules/help.os.php: OperServ help module
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

class os_help extends module
{
	
	const MOD_VERSION = '0.0.2';
	const MOD_AUTHOR = 'Acora';
	
	/*
	* modload (private)
	* 
	* @params
	* void
	*/
	static public function modload()
	{
		modules::init_module( __CLASS__, self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		// these are standard in module constructors
		
		commands::add_help_fix( 'operserv', 'os_help', 'prefix', 'help', operserv::$help->OS_HELP_PREFIX );
		commands::add_help_fix( 'operserv', 'os_help', 'suffix', 'help', operserv::$help->OS_HELP_SUFFIX );
		// add teh help docs
	}
	
	/*
	* on_msg (event hook)
	*/
	static public function on_msg( $nick, $target, $msg )
	{
		if ( $target != core::$config->operserv->nick )
			return false;
			
		$query = substr( $msg, 1 );
		// convert to lower case because all the tingy wags are in lowercase
		$query = strtolower( $query );
		
		if ( core::$nicks[$nick]['ircop'] && core::$nicks[$nick]['identified'] )
			commands::get_help( 'operserv', $nick, $query );
	}
}

// EOF;
