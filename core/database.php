<?php

/*
* Acora IRC Services
* core/database.php: Database driver initiation class
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

class database
{
	
	static public $driver;
	// driver
	
	/*
	* factory
	* 
	* @params
	* $driver - mysql
	*/
	static public function factory( $drivers )
	{
		if ( require( BASEPATH.'/core/drivers/'.$drivers.'.php' ) )
		{
			core::alog( 'factory(): using '.$drivers.' database driver', 'BASIC' );
            self::$driver = new $drivers;
            // store the instance here
        }
		else
		{
			core::alog( 'factory(): failed to open '.$drivers.' database driver', 'BASIC' );
            
            core::save_logs();
            // force a log save
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
	static public function num_rows( &$resource )
	{
		if ( is_resource( $resource ) || is_array( $resource ) )
			return self::$driver->num_rows( $resource );
		else
			return 0;
	}
	
	/*
	* row
	*/
	static public function row( &$resource )
	{
		if ( is_resource( $resource ) || is_array( $resource ) )
			return self::$driver->row( $resource );
		else
			return false;
	}
	
	/*
	* fetch
	*/
	static public function fetch( &$resource )
	{
		if ( is_resource( $resource ) || is_array( $resource ) )
			return self::$driver->fetch( $resource );
		else
			return false;
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