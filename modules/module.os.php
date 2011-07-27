<?php

/*
* Acora IRC Services
* modules/module.os.php: OperServ modules module
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

class os_module extends module
{
	
	const MOD_VERSION = '0.1.3';
	const MOD_AUTHOR = 'Acora';
	// module info
	
	static public $return_codes = array(
		'INVALID_SYNTAX'	=> 1,
		'ALREADY_LOADED'	=> 2,
		'FILE_NO_EXIST'		=> 3,
		'BOOT_ERROR'		=> 4,
		'NOT_LOADED'		=> 5,
		'STATIC_MODULE'		=> 6,
	);
	// return codes
	
	/*
	* modload (private)
	* 
	* @params
	* void
	*/
	static public function modload()
	{
		modules::init_module( 'os_module', self::MOD_VERSION, self::MOD_AUTHOR, 'operserv', 'static' );
		self::$return_codes = (object) self::$return_codes;
		// these are standard in module constructors
		
		operserv::add_help( 'os_module', 'help', operserv::$help->OS_HELP_MODULES_1, true );
		operserv::add_help( 'os_module', 'help', operserv::$help->OS_HELP_MODLOAD_1, true, 'root' );
		operserv::add_help( 'os_module', 'help', operserv::$help->OS_HELP_MODUNLOAD_1, true, 'root' );
		operserv::add_help( 'os_module', 'help modlist', operserv::$help->OS_HELP_MODLIST_ALL );
		operserv::add_help( 'os_module', 'help modload', operserv::$help->OS_HELP_MODLOAD_ALL, false, 'root' );
		operserv::add_help( 'os_module', 'help modunload', operserv::$help->OS_HELP_MODUNLOAD_ALL, false, 'root' );
		// add the help
		
		operserv::add_command( 'modlist', 'os_module', 'modlist_command' );
		operserv::add_command( 'modload', 'os_module', 'modload_command' );
		operserv::add_command( 'modunload', 'os_module', 'modunload_command' );
		// add the commands
	}
	
	/*
	* modlist_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	static public function modlist_command( $nick, $ircdata = array() )
	{
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_list_modules( $input );
		// list the news
		
		services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* modload_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	public function modload_command( $nick, $ircdata = array() )
	{
		if ( !services::oper_privs( $nick, 'root' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// don't have privs
		
		$file = explode( '_', $ircdata[0] );
		$file = $file[1].'.'.$file[0].'.php';
		// get the module thats been requested.
		
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_load_module( $input, $nick, $file, $ircdata[0] );
		// list the news
		
		services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* modunload_command (command)
	* 
	* @params
	* $nick - The nick of the person issuing the command
	* $ircdata - Any parameters.
	*/
	public function modunload_command( $nick, $ircdata = array() )
	{
		if ( !services::oper_privs( $nick, 'root' ) )
		{
			services::communicate( core::$config->operserv->nick, $nick, operserv::$help->OS_ACCESS_DENIED );
			return false;
		}
		// don't have privs
	
		$input = array( 'internal' => true, 'hostname' => core::get_full_hostname( $nick ), 'account' => core::$nicks[$nick]['account'] );
		$return_data = self::_unload_module( $input, $nick, $ircdata[0] );
		// list the news
		
		services::respond( core::$config->operserv->nick, $nick, $return_data[CMD_RESPONSE] );
		return $return_data[CMD_SUCCESS];
		// respond and return
	}
	
	/*
	* _load_module (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $file - The module filename
	* $module - The module to load
	*/
	static public function _load_module( $input, $nick, $file, $module )
	{
		$return_data = module::$return_data;
		if ( trim( $module ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'MODLOAD' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
	
		if ( isset( modules::$list[$module] ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_MODLOAD_3, array( 'name' => $module ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->ALREADY_LOADED;
			return $return_data;
		}
		// module exists
	
		modules::load_module( $module, $file, true );
		// load the module 
		
		modules::$list[$module]['class']->modload();
		// onload handler.
		
		core::alog( core::$config->operserv->nick.': ('.$input['hostname'].') ('.$input['account'].') loaded module ('.$module.') ('.modules::$list[$module]['version'].') ('.modules::$list[$module]['author'].'/'.modules::$list[$module]['type'].'/'.modules::$list[$module]['extra'].')' );
		// let everyone know
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_MODLOAD_2, array( 'name' => $module, 'version' => modules::$list[$module]['version'], 'extra' => modules::$list[$module]['author'].'/'.modules::$list[$module]['type'].'/'.modules::$list[$module]['extra'] ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return shiz
	}
	
	/*
	* _unload_module (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	* $nick - The nick of the person issuing the command
	* $module - The module to load
	*/
	static public function _unload_module( $input, $nick, $module )
	{
		$return_data = module::$return_data;
		if ( trim( $module ) == '' )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_INVALID_SYNTAX_RE, array( 'help' => 'MODUNLOAD' ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->INVALID_SYNTAX;
			return $return_data;
		}
		// wrong syntax
	
		if ( !isset( modules::$list[$module] ) )
		{
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_MODUNLOAD_1, array( 'name' => $module ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->NOT_LOADED;
			return $return_data;
		}
		// module isnt loaded
		
		if ( modules::$list[$module]['extra'] == 'static' )
		{
			core::alog( 'modunload_command(): unable to unload static module '.$module.' (cannot be unloaded)', 'BASIC' );
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_MODUNLOAD_2, array( 'name' => $module ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->STATIC_MODULE;
			return $return_data;
		}
		// module is static
		
		if ( !class_exists( $module ) )
		{
			core::alog( 'modunload_command(): unable to unload module '.$module.' (not booted)', 'BASIC' );
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_MODUNLOAD_2, array( 'name' => $module ) );
			$return_data[CMD_FAILCODE] = self::$return_codes->BOOT_ERROR;
			return $return_data;
		}
		// class doesnt exist, maybe it's loaded but not booted, ie something went wrong upon boot in modules::_construct()
		
		if ( is_callable( array( $module, 'modunload' ), true ) && method_exists( $module, 'modunload' ) )
			modules::$list[$module]['class']->modunload();
		// if the module has an unload method, call it now before we destroy the class.
		
		$data = modules::$list[$module];
		unset( modules::$list[$module] );
		// unset the module
		
		modules::_unset_docs( $module );
		// unset the modules help docs etc.
		
		core::alog( core::$config->operserv->nick.': ('.$input['hostname'].') ('.$input['account'].') unloaded module ('.$module.') ('.$data['version'].') ('.$data['author'].'/'.$data['type'].'/'.$data['extra'].')' );
		// let everyone know :D	
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_MODUNLOAD_3, array( 'name' => $module, 'version' => $data['version'], 'extra' => $data['author'].'/'.$data['type'].'/'.$data['extra'] ) );
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return shiz
	}
	
	/*
	* _list_modules (private)
	* 
	* @params
	* $input - Should be internal => true, hostname => *!*@*, account => accountName
	*/
	static public function _list_modules( $input )
	{
		$return_data = module::$return_data;
		// we don't even need to listen for any
		// parameters, because its just a straight command
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_MODLIST_TOP );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_MODLIST_DLM );
		
		$x = 0;
		foreach ( modules::$list as $module => $data )
		{
			$x++;
			$false_name = $data['name'];
				
			if ( !isset( $data['name'][17] ) )
			{
				$y = strlen( $data['name'] );
				for ( $i = $y; $i <= 16; $i++ )
					$false_name .= ' ';
			}
			// this is just a bit of fancy fancy, so everything displays neat
			
			$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_MODLIST_3, array( 'name' => $false_name, 'version' => $data['version'], 'author' => $data['author'], 'type' => $data['type'], 'extra' => $data['extra'] ) );
			$return_data[CMD_DATA][] = array( 'name' => $data['name'], 'version' => $data['version'], 'author' => $data['author'], 'type' => $data['type'], 'extra' => $data['extra'] );
		}
		// loop through the currently loaded modules.
		
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_MODLIST_DLM );
		$return_data[CMD_RESPONSE][] = services::parse( operserv::$help->OS_MODLIST_BTM, array( 'num' => count( modules::$list ) ) );
		// compile a list, charveour
		
		$return_data[CMD_SUCCESS] = true;
		return $return_data;
		// return shiz
	}
}

// EOF;