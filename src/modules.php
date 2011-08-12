<?php

/*
* Acora IRC Services
* src/modules.php: Class for module handlers
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

class modules
{
	
	static public $list	= array();
	static public $event_methods = array();
	static public $event_handlers = array(
		'on_burst_connect',
		'on_connect',
		'on_nick_change',
		'on_quit',
		'on_mode',
		'on_topic',
		'on_chan_create',
		'on_join',
		'on_part',
		'on_kick',
		'on_oper_up',
		'on_msg',
		'on_rehash'
	);
	// setup the core module list and event handlers
	
	/*
	* init_module
	*
	* @params
	* $name - Name of the module
	* $version - Module version
	* $author - Module author
	* $type - chanserv/nickserv etc.
	* $extra - static/default/third-party
	*/
	static public function init_module( $name, $version, $author, $type, $extra )
	{
		self::$list[$name]['name'] = $name;
		self::$list[$name]['version'] = $version;
		self::$list[$name]['author'] = $author;
		self::$list[$name]['type'] = $type;
		self::$list[$name]['extra'] = $extra;
		
		core::alog( 'init_module(): loaded module '.$name.' ('.$version.'/'.$type.'/'.$extra.')', 'BASIC' );
		// log it
		
		foreach ( self::$event_handlers as $l => $event )
			if ( method_exists( $name, $event ) ) self::$event_methods[$event][] = $name;
		// let's find out what we've got in terms of events..
	}
	
	/*
	* init_service
	*
	* @params
	* $name - Name of the service
	* $version - service version
	* $author - service author
	*/
	static public function init_service( $name, $version, $author )
	{
		core::alog( 'init_service(): loaded service '.$name.' ('.$version.')', 'BASIC' );
		// log it
		
		foreach ( self::$event_handlers as $l => $event )
			if ( method_exists( $name, $event ) ) self::$event_methods[$event][] = $name;
		// let's find out what we've got in terms of events..
	}
	
	/*
	* load_module
	*
	* @params
	* $module_name - the name of the module
	* $module_file - the filename of the module
	* $reload - false or true
	*/
	static public function load_module( $module_name, $module_file, $reload = false )
	{
		if ( !file_exists( BASEPATH.'/modules/'.$module_file ) )
		{
			core::alog( 'load_module(): unable to load: '.$module_name.' (not found)', 'BASIC' );
			return false;
		}
		// check if the module actually exists
		
		if ( $reload && ( extension_loaded( 'runkit' ) && !runkit_import( BASEPATH.'/modules/'.$module_file, RUNKIT_IMPORT_OVERRIDE | RUNKIT_IMPORT_CLASSES ) ) )
		{
			core::alog( 'load_module(): unable to load: '.$module_name.' (error loading)', 'BASIC' );
			return false;
		}
		// class is already loaded, reload it
	
		if ( !$reload && ( !include( BASEPATH.'/modules/'.$module_file ) ) )
		{
			core::alog( 'load_module(): unable to load: '.$module_name.' (error loading)', 'BASIC' );
			return false;
		}
		// module (exists?) but can't be loaded
		
		if ( !class_exists( $module_name ) )
		{
			core::alog( 'load_module(): unable to load: '.$module_name.' (boot error)', 'BASIC' );
			return false;
		}
		// class doesn't exist
		
		self::$list[$module_name]['class'] = new $module_name();
		call_user_func( array( $module_name, 'modload' ) );
		// onload handler.
	}
	
	/*
	* on_rehash (public)
	* 
	* @params
	* (void)
	*/
	public function on_rehash()
	{
		foreach ( self::$event_methods['on_rehash'] as $l => $class )
			call_user_func( array( $class, 'on_rehash' ) );
		// call the event method
	}
		
	/*
	* _unset_docs (private)
	* 
	* @params
	* $module - The module to unset crap for
	*/
	static public function _unset_docs( $module )
	{
		foreach ( commands::$helpv as $hook => $data )
		{
			foreach ( $data as $command => $mdata )
			{
				foreach ( $mdata as $index => $meta_data )
				{
					$meta = unserialize( $meta_data );
				
					if ( $meta['module'] == $module )
						unset( commands::$helpv[$hook][$command][$index] );
					// if it has meta data matching we just unset it.
				}
			}
		}
		// this is nasty as fuck, 3 embedded loops, idk how to deal with this tbh :S
		// i really can find no way what so ever to avoid this, so i guess this is
		// how it happens until i find a better way -.-
		
		foreach ( commands::$commands as $hook => $data )
		{
			foreach ( $data as $index => $mdata )
			{
				if ( $mdata['class'] == $module )
					unset( commands::$commands[$hook][$index] );
			}
		}
		// we do the same with commands linked to the module, to avoid crashes
		
		foreach ( commands::$prefix as $hook => $data )
		{
			foreach ( $data as $index => $mdata )
			{
				foreach ( $mdata as $help => $meta_data )
				{
					$meta = unserialize( $meta_data );
					
					if ( $meta['module'] == $module )
						unset( commands::$prefix[$hook][$index][$help] );
					// epic unsets.
				}
			}
		}
		// prefix
		
		foreach ( commands::$suffix as $hook => $data )
		{
			foreach ( $data as $index => $mdata )
			{
				foreach ( $mdata as $help => $meta_data )
				{
					$meta = unserialize( $meta_data );
					
					if ( $meta['module'] == $module )
						unset( commands::$suffix[$hook][$index][$help] );
					// epic unsets.
				}
			}
		}
		// and the same with prefixes and suffixes :@
		// this is so fucking messy, i do apologize
	}
}

// EOF;
