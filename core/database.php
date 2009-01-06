<?php

/*
* Acora IRC Services
* core/database.php: Database driver initiation class
* 
* Copyright (c) 2009 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* This project is licensed under the GNU Public License
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class database implements driver
{
	
	static public $driver;
	// driver
	
	/*
	* __construct (private)
	* 
	* @params
	* void
	*/
	private function __construct() { }
	
	/*
	* factory
	* 
	* @params
	* $driver - mysql
	*/
	static public function factory( $driver )
	{
		if ( require( BASEPATH.'/core/drivers/'.$driver.'.php' ) )
		{
			core::alog( 'factory(): using '.$driver.' database driver', 'BASIC' );
            self::$driver = new $driver;
            // store the instance here
        }
		else
		{
			core::alog( 'factory(): failed to open '.$driver.' database driver', 'BASIC' );
            exit( 'cant initiate the specified database driver ('.$driver.')' );
        }
        // see if we can require the file
        // if so initiate the class
        // if not, bail.
	}
	
	/*
	* ping
	*/
	static public function ping()
	{
		return self::$driver->ping();
	}
	
	/*
	* num_rows
	*/
	static public function num_rows( $resource )
	{
		return self::$driver->num_rows( $resource );
	}
	
	/*
	* row
	*/
	static public function row( $resource )
	{
		return self::$driver->row( $resource );
	}
	
	/*
	* fetch
	*/
	static public function fetch( $resource )
	{
		return self::$driver->fetch( $resource );
	}
	
	/*
	* quote
	*/
	static public function quote( $string )
	{
		return self::$driver->quote( $string );
	}
	
	/*
	* optimize
	*/
	static public function optimize()
	{
		return self::$driver->optimize();
	}
	
	/*
	* select
	*/
	static public function select( $table, $what, $where = '', $order = '', $limit = '' )
	{
		return self::$driver->select( $table, $what, $where, $order, $limit );
	}
	
	/*
	* update
	*/
	static public function update( $table, $what, $where = '' )
	{
		return self::$driver->update( $table, $what, $where );
	}
	
	/*
	* insert
	*/
	static public function insert( $table, $what )
	{
		return self::$driver->insert( $table, $what );
	}
	
	/*
	* delete
	*/
	static public function delete( $table, $where = '' )
	{
		return self::$driver->delete( $table, $where );
	}
}

// EOF;