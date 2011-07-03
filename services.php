#!/usr/bin/php -q
<?php

/*
* Acora IRC Services
* services.php: Boots up the services package.
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

// define the basepath
define( 'BASEPATH', dirname(__FILE__) );
define( 'CONFPATH', BASEPATH.'/conf/' );

// Check to see if the version of PHP meets the minimum requirement
if ( version_compare( '5.0.0', PHP_VERSION, '>' ) )
    exit( 'Fatal Error: PHP 5.0.0+ is required, current version: '.PHP_VERSION );

// make sure we have access to PHP CLI
if ( ( substr( php_sapi_name(), 0, 3 ) != 'cli' ) )
    exit( 'Fatal Error: PHP CLI is required to run.' );

// set time limit to 0
// also set ignore user abort
ini_set( 'max_execution_time', '0' );
ini_set( 'max_input_time', '0' );
set_time_limit( 0 );
ignore_user_abort( true );

// memory limit.
ini_set( 'memory_limit', '32M' );

// set error reporting to all
error_reporting( E_ALL ^ ( E_NOTICE | E_WARNING ) );

// include all the core system file
require( BASEPATH.'/core/core.php' );

// set the error handler
set_error_handler( array( 'core', 'core_error' ) );

// include all the other required classes
require( BASEPATH.'/core/interfaces.php' );
require( BASEPATH.'/core/timer.php' );
require( BASEPATH.'/core/mode.php' );
require( BASEPATH.'/core/services.php' );
require( BASEPATH.'/core/commands.php' );
require( BASEPATH.'/core/modules.php' );
require( BASEPATH.'/core/parser.php' );
require( BASEPATH.'/core/database.php' );
require( BASEPATH.'/core/ircd.php' );

// boot the services.
new core( $argv );

// EOF;
