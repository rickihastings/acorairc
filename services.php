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
define( 'BASEPATH', dirname( __FILE__ ) );
define( 'CONFPATH', BASEPATH.'/conf/' );

// Check to see if the version of PHP meets the minimum requirement
if ( version_compare( '5.1.0', PHP_VERSION, '>' ) )
    exit( 'Fatal Error: PHP 5.1.0+ is required, current version: '.PHP_VERSION );

// make sure we have access to PHP CLI
if ( ( substr( php_sapi_name(), 0, 3 ) != 'cli' ) )
    exit( 'Fatal Error: PHP CLI is required to run.' );

// set time limit to 0
// also set ignore user abort
ini_set( 'max_execution_time', '0' );
ini_set( 'max_input_time', '0' );
set_time_limit( 0 );
ignore_user_abort( true );

// set the default time and date
date_default_timezone_set( 'GMT' );

// memory limit.
ini_set( 'memory_limit', '64M' );

// set error reporting to all
//ini_set( 'display_errors', 'off' );
error_reporting( E_ALL ^ E_NOTICE );

// include all the core system file
require( BASEPATH.'/src/core.php' );

// set the error handler
set_error_handler( array( 'core', 'core_error' ) );

// include all the other required classes
require( BASEPATH.'/src/interfaces.php' );
require( BASEPATH.'/src/timer.php' );
require( BASEPATH.'/src/mode.php' );
require( BASEPATH.'/src/services.php' );
require( BASEPATH.'/src/commands.php' );
require( BASEPATH.'/src/modules.php' );
require( BASEPATH.'/src/parser.php' );
require( BASEPATH.'/src/database.php' );
require( BASEPATH.'/src/sockets.php' );
require( BASEPATH.'/src/ircd.php' );

// boot the services.
new core( $argv );

// EOF;
