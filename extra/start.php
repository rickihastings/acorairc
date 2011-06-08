#!/usr/bin/php -q
<?php

/*
* Acora IRC Services
* extra/start.php: Unix boot up file.
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

define( 'BASEPATH', dirname(__FILE__) );

$file 	= BASEPATH.'/../services.php';
// edit this if you've renamed services.php
// although really it shouldn't be renamed because
// that might stop remote control from working

if ( !file_exists( $file ) )
{
	exit( 'cannot find '.$file );
}
// if we can't find the file, exit

exec( 'php -q '.$file.' > /dev/null &' );
// boot up.

// EOF;