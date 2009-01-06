<?php

/*
* Acora IRC Services
* core/drivers/mysql.php: MySQL driver class
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

class mysql implements driver
{

	const MOD_VERSION = '0.0.2';
	const MOD_AUTHOR = 'Acora';
	// module info.

	static public $link;
	// database link

	/*
	* __construct
	* 
	* @params
	* $class - ..
	*/
	public function __construct()
	{
		modules::init_module( 'mysql_driver', self::MOD_VERSION, self::MOD_AUTHOR, 'driver', 'static' );
		// these are standard in module constructors
		
		if ( !self::$link = @mysql_connect( core::$config->database->server, core::$config->database->user, core::$config->database->pass ) )
		{
			core::alog( 'database(): failed to connect to '.core::$config->database->server.' '.core::$config->database->user.':'.core::$config->database->pass, 'BASIC' );
			exit( 'cant connect to the mysql server, please make sure the config values "server", "user" and "pass" are defined in the database block.' );
		}
		// can we connect to sql?
		
		if ( !@mysql_select_db( core::$config->database->name, self::$link ) )
		{
			core::alog( 'database(): failed to select database '.core::$config->database->name, 'BASIC' );
			exit( 'cant select a database, please make sure the config value "name" is defined in the database block.' );
		}
		// can we select the database?
		
		core::alog( 'database(): connection to database sucessful', 'BASIC' );
		// log the sucessful connection
		
		if ( core::$config->database->optimize )
		{
			timer::add( array( 'database', 'optimize', array() ), 86399, 0 );
		}
		// add a timer to optimize the db every day.
	}
	
	/*
	* ping
	* 
	* @params
	* void
	*/
	static public function ping()
	{
		if ( !mysql_ping( self::$link ) )
		{
			mysql_close( self::$link );
			// close the connection
			
			self::$link = @mysql_connect( core::$config->database->server, core::$config->database->user, core::$config->database->pass );
			// can we connect to sql?
			
			@mysql_select_db( core::$config->database->name, self::$link );
			// can we select the database?
		}
	}

	
	/*
	* num_rows
	* 
	* @params
	* $resource - The result to fetch
	*/
	static public function num_rows( $resource )
	{
		return mysql_num_rows( $resource );
	}
	
	/*
	* row
	* 
	* @params
	* $resource - The row to fetch
	*/
	static public function row( $resource )
	{
		return mysql_fetch_row( $resource );
	}
	
	/*
	* fetch
	* 
	* @params
	* $resource - The result to fetch
	*/
	static public function fetch( $resource )
	{
		return mysql_fetch_object( $resource );
	}
	
	/*
	* quote
	* 
	* @params
	* $string - The string to clean
	*/
	static public function quote( $string )
	{
		$string = mysql_real_escape_string( $string );
		
		return $string;
	}
	
	/*
	* optimize
	* 
	* @params
	* void
	*/
	static public function optimize()
	{
		$tablesResult = mysql_query( "SHOW TABLES FROM ".core::$config->database->name );
		$tname = "Tables_in_".core::$config->database->name;
		
		while ( $row = database::fetch( $tablesResult ) )
		{
			mysql_query( "OPTIMIZE TABLE `".$row->$tname."`" );
		}
		// loop through our tables
		
		core::alog( core::$config->operserv->nick.': database optimization complete' );
		core::alog( 'optimize(): database optimization complete.', 'BASIC' );
	}
	
	/*
	* select
	* 
	* @params
	* $table - The table to delete from
	* $what - What to select, as an array
	* $where - The WHERE clause
	* $order - Order as an array
	* $limit - As an array
	*/
	static public function select( $table, $what, $where = '', $order = '', $limit = '' )
	{
		$query = "SELECT ";
		
		if ( $what == '*' )
		{
			$query .= '*';
		}
		elseif ( $what != '*' && is_array( $what ) )
		{
			$i = 0;
			foreach ( $what as $var )
			{
				$i++;
				
				if ( $i == count( $what ) ) $query .= "`".$var."`";
				else $query .= "`".$var."`, ";
			}
		}
		// construct the what part, `max_users` etc.
		
		$query .= " FROM `".core::$config->database->prefix.$table."`";
		// the table
		
		if ( $where != '' && !is_array( $where ) )
		{
			$query .= " WHERE ".$where;
		}
		// and the where part: `id` = '1'
		
		if ( $order != '' && is_array( $order ) )
		{
			$query .= " ORDER BY ";
			
			foreach ( $order as $index => $val )
			{
				$query .= "`".$index."` ".$val;
			}
		}
		// order by
		
		if ( $limit != '' && is_array( $limit ) )
		{
			$query .= " LIMIT ";
			
			foreach ( $limit as $index => $val )
			{
				$query .= $index.", ".$val;
			}
		}
		// order by
		
		return mysql_query( $query );
	}
	
	/*
	* update
	*
	* @params
	* $table - The table to update it
	* $what - What to update, as an array
	* $where - The WHERE clause
	*/
	static public function update( $table, $what, $where = '' )
	{
		$query = "UPDATE `".core::$config->database->prefix.$table."` SET";
		// the table
			
		$i = 0;
		foreach ( $what as $index => $val )
		{
			$i++;
			if ( $i == count( $what ) ) $query .= "`".$index."` = '".self::quote( $val )."'";
			else $query .= " `".$index."` = '".self::quote( $val )."', ";
		}
		// the what part, SET `field` = '1'
		
		if ( $where != '' && !is_array( $where ) )
		{
			$query .= " WHERE ".$where;
		}
		// and the where part: `id` = '1'
		
		return mysql_query( $query );
	}
	
	/*
	* insert
	* 
	* @params
	* $table - The table to insert to
	* $what - What to insert, as an array
	*/
	static public function insert( $table, $what )
	{
		$query = "INSERT INTO `".core::$config->database->prefix.$table."` ";
		// the table
		
		foreach ( $what as $index => $val )
		{
			$fieldarray[] .= $index;
			$valuearray[] .= $val;
		}
		// split into seperate arrays
		
		$i = 0;
		foreach ( $fieldarray as $value )
		{
			$i++;
			if ( $i == 1 ) $query .= "(`".$value."`, ";
			elseif ( $i == count( $fieldarray ) ) $query .= "`".$value."`)";
			else $query .= "`".$value."`, ";
		}
		
		$i = 0;
		foreach ( $valuearray as $value )
		{
			$i++;
			if ( $i == 1 ) $query .= " VALUES('".self::quote( $value )."', ";
			elseif ( $i == count( $fieldarray ) ) $query .= "'".self::quote( $value )."')";
			else $query .= "'".self::quote( $value )."', ";
		}
		// what are we inserting?
		
		return mysql_query( $query );
	}
	
	/*
	* delete
	* 
	* @params
	* $table - The table to delete from
	* $where - The WHERE clause
	*/
	static public function delete( $table, $where = '' )
	{
		$query = "DELETE FROM `".core::$config->database->prefix.$table."`";
		// the table
		
		if ( $where != '' && !is_array( $where ) )
		{
			$query .= " WHERE ".$where;
		}
		// and the where part: `id` = '1'
		
		return mysql_query( $query );
	}
}

// EOF;