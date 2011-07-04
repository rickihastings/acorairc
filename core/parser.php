<?php

/*
* Acora IRC Services
* core/parser.php: Configuration parser
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

class parser
{
	
	static public $filename;
	static public $config = array();
	static public $comment_opened = false;
	static public $sub_open = false;
	static public $opened = false;
	static public $opened_dir;
	static public $opened_sub;
	// some vars.
	
	static public $required = array( 
		'server', 'uplink', 'ulined_servers', 'settings', 'database', 'root'
	);
	// required
	
	static public $multiple = array(
		'opers'
	);
	// multiple allowed
	
	/*
	* __construct
	* 
	* @params
	* void
	*/
	public function __construct( $filename = '', $check = true )
	{
		if ( $filename != '' )
		{
			self::$filename = $filename;
			
			self::parse();
			// init the parser
			
			if ( $check )
			{
				self::check();
				// check values.
			}
			
			self::$config = (array) self::array_to_object( self::$config );
			core::$config = (object) array_merge( (array) core::$config, self::$config );
			// assign it to the core.
			self::$config = array();
			// unset self::$config, to avoid object <-> array errors
			// if the parser is used again.
		}
	}
	
	/*
	* parse
	* 
	* @params
	* void
	*/
	static public function parse()
	{
        if ( !file_exists( self::$filename ) && $check )
		{
			core::alog( 'ERROR: '.self::$filename.' cannot be found.', 'BASIC' );
			core::save_logs();
			// force a log save
		}
		// check if it exists
		
		$lines = file( self::$filename );
		// open the file
		
		foreach ( $lines as $num => $line )
		{
			$line = trim( $line );
			if ( substr( $line, -1 ) == ';' ) $line = substr( $line, 0, -1 );
			$line = preg_replace( '/\s+/', ' ', $line ); 
			// clear the crap out of it.
			
			if ( self::check_comment( $line ) == 'open' )
				self::$comment_opened = true;
			if ( self::check_comment( $line ) == 'close' )
				self::$comment_opened = false;
			if ( self::check_comment( $line ) == 'comment' )
				continue;
			
			if ( $line == '{' || self::$comment_opened )
				continue;
			// skip it if it's just {
			// or, a multiline comment is open
				
			if ( $line == '}' )
			{
				self::$sub_open = false;
				self::$opened = false;
				self::$opened_dir = '';
				self::$opened_sub = '';
				
				continue;
			}
			// stop parsing the data for that dir
			// once we've hit };
			
			if ( trim( $lines[$num + 1] ) == '{' || substr( $line, -1 ) == '{' )
			{
				$rvar = explode( ' ', $line );
				$var = trim( $rvar[0] );
				// grab the variable, eg. uplink, server. etc
				
				preg_match( "/\"(.*)\"/", $line, $matches );
				$sub_var = ( isset( $matches[1] ) ) ? $matches[1] : '';
				// format our data
				
				if ( in_array( $var, self::$multiple ) )
				{
					if ( count( $matches ) == 2 )
						self::$config[$var][$sub_var][$var] = trim( $matches[1] );
					elseif ( $line != '{' && $line != '};' )
						self::$config[$var][$sub_var] = array();
					// does this directive allow multiple entries?
					
					self::$sub_open = true;
					self::$opened_sub = $sub_var;
				}
				else
				{
					if ( count( $matches ) == 2 )
						self::$config[$var][$var] = trim( $matches[1] );
					elseif ( $line != '{' && $line != '};' )
						self::$config[$var] = array();
					// if it doesn't allow multiple entries
				}
				
				self::$opened = true;
				self::$opened_dir = $var;
				// so, what we do here, we check if we have any "quoted info"
				// for instance uplink "server.name" {
				// if we do, we grab server.name.
				
				continue;
			}
			// so, we're dealing with a 
			// "something {" scenario.
			
			if ( self::$opened && $line != '{' && $line != '}' )
			{
				$rvar = explode( ' ', $line );
				$var = trim( $rvar[0] );
				unset( $rvar[0] );
				$value = ( isset( $rvar[1] ) ) ? implode( ' ', $rvar ) : '';
				// here we create our var and value variables, fixing a bug
				// with spaces not working in values earlier :@
				
				if ( substr( $var, -2 ) == '[]' )
				{
					$var = substr( $var, 0, -2 );
					// strip the []
					
					preg_match( "/\"(.*)\"/", $value, $matches );
					
					if ( count( $matches ) == 2 )
					{
						if ( self::$sub_open )
							self::$config[self::$opened_dir][self::$opened_sub][$var][] = trim( $matches[1] );
						else
							self::$config[self::$opened_dir][$var][] = trim( $matches[1] );
					}
						
					continue;
				}
				// are we dealing with an array, inside of {}
				// we only use the [] formats inside of {}
				// outside it, it's pointless as we can just use {}
				
				if ( $value == '' )
				{
					preg_match( "/\"(.*)\"/", $var, $matches );
					
					if ( count( $matches ) == 2 )
					{
						if ( self::$sub_open )
							self::$config[self::$opened_dir][self::$opened_sub][] = trim( $matches[1] );
						else
							self::$config[self::$opened_dir][] = trim( $matches[1] );
					}
				}
				else
				{
					preg_match( "/\"(.*)\"/", $value, $matches );
					
					if ( count( $matches ) == 2 )
					{
						if ( self::$sub_open )
							self::$config[self::$opened_dir][self::$opened_sub][$var] = trim( $matches[1] );
						else
							self::$config[self::$opened_dir][$var] = trim( $matches[1] );
					}
				}
				// is it a standalone value? eg "test"; or a defined value
				// eg variable "test"; ?
			
				continue;
			}
			// parse the data inside of { };
		}
		// loooop
	}
	
	/*
	* check
	* 
	* @params
	* void
	*/
	static public function check()
	{
		foreach ( self::$required as $var => $value )
		{
			if ( !isset( self::$config[$value] ) )
			{
				core::alog( 'ERROR: '.$value.' is REQUIRED, startup halted', 'BASIC' );
				core::save_logs();
				// force a log save.
			}
		}
		// check for required vars
		
		if ( is_array( self::$config['chanserv_exception_modules'] ) && !in_array( 'cs_fantasy', self::$config['chanserv_exception_modules'] ) )
		{
			if ( !isset( self::$config['fantasy_prefix'] ) )
				self::$config['fantasy_prefix'] = '!';
		}
		// check for undefined vars.
		
		foreach ( self::$config as $var => $values )
		{
			if ( $values == 'yes' || $values == 'true' || $values == '1' )
				self::$config[$values] = true;
			
			if ( $values == 'no' || $values == 'false' || $values == '0' )
				self::$config[$values] = false;
		
			foreach ( $values as $name => $value )
			{
				if ( $value == 'yes' || $value == 'true' || $value == '1' )
					self::$config[$var][$name] = true;
				
				if ( $value == 'no' || $value == 'false' || $value == '0' )
					self::$config[$var][$name] = false;
			}
		}
		// convert 'yes', 'true', '1' and their opposites to booleans
	}
	
	/*
	* check_comments (private)
	* 
	* @params
	* $string = The string to check
	*/
	static public function check_comment( $string )
	{
		if ( ( isset( $string[0] ) && $string[0] == '/' ) && ( isset( $string[1] ) && $string[1] == '/' ) )
			return 'comment';
		// we've found a "// comment"
		
		if ( ( isset( $string[0] ) && $string[0] == '#' ) )
			return 'comment';
		// we've found a "# comment"
		
		if ( ( isset( $string[0] ) && $string[0] == '/' ) && ( isset( $string[1] ) && $string[1] == '*' ) )
			return 'open';
		// we've found a "/* comment"
		
		if ( ( isset( $string[0] ) && $string[0] == '*' ) && ( isset( $string[1] ) && $string[1] == '/' ) )
			return 'close';
		// we've found a "*/ comment"
		
		return false;
	}
	
	/*
	* array_to_object (private)
	* 
	* @params
	* $array - converts the array (recursively) into an object
	*/
	function array_to_object( $array )
	{
		foreach ( $array as $key => $value )
			if ( is_array( $value ) ) $array[$key] = self::array_to_object( $value );
		
		return (object) $array;
	}
}

// EOF;