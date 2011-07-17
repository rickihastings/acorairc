<?php

/*
* Acora IRC Services
* core/sockets.php: Socket engine
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

class socket_engine
{
	
	static public $sockets = array();
	
	/*
	* __construct
	*
	* @params
	* void
	*/
	public function __construct() { }
	
	/*
	* __destruct
	*
	* @params
	* void
	*/
	public function __destruct()
	{
		foreach ( self::$sockets as $identifier => $socket )
			self::close( $identifier );
		// close our sockets
	}
	
	/*
	* create
	*
	* @params
	* $identifier - a name to refer to the socket to
	* $host - the host to connect to
	* $port - the port to connect to
	* $block - whether to set to blocking or nonblocking, again not advised to set to false for
	*          sockets created by modules.
	*/
	static public function create( $identifier, $host, $port, $block = false )
	{
		if ( !self::$sockets[$identifier] = socket_create( AF_INET, SOCK_STREAM, SOL_TCP ) )
		{
			core::alog( 'create(): failed: reason: ' . socket_strerror( socket_last_error() ), 'BASIC' );
			
			self::close( $identifier );
			return false;
			// alog and attempt to reconnect
		}
		
		core::alog( 'create(): attempting to connect to '.$host.':'.$port, 'BASIC' );
		$result = socket_connect( self::$sockets[$identifier], $host, $port );
		
		if ( !$result )
		{
			core::alog( 'create(): failed to connect to '.$host.':'.$port.' reason: '.socket_strerror( socket_last_error( self::$sockets[$identifier] ) ), 'BASIC' );
		
			self::close( $identifier );
		}
		// socket can't be made, let's gtfo.
		
		if ( !$block )
			socket_set_nonblock( self::$sockets[$identifier] );
		// socket needs to be set to nonblocking, they're blocked by default.
		
		return true;
		// if we've got this far all is good.
	}
	
	/*
	* close
	*
	* @params
	* $identifier - the socket to close
	*/
	static public function close( $identifier )
	{
		if ( !isset( self::$sockets[$identifier] ) )
		{
			core::alog( 'close(): '.$identifier.' doesn\'t seem to exist' );
			return false;
		}
		// $identifier doesn't even exist..
	
		if ( is_resource( self::$sockets[$identifier] ) )
			socket_close( self::$sockets[$identifier] );
		// close the socket if it's a resource	
		
		unset( self::$sockets[$identifier] );
		
		core::alog( 'close(): sucessfully closed socket \''.$identifier.'\'' );
	}
}

// EOF;