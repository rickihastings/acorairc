<?php

/*
* Acora IRC Services
* core/protocol/inspircd11.php: Provides support for InspIRCd 1.2
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

class ircd implements protocol
{

	const MOD_VERSION = '0.0.6s';
	const MOD_AUTHOR = 'Acora';
	// module info.

	static public $ircd = 'InspIRCd 1.2';
	static public $globops = false;
	static public $chghost = false;
	static public $chgident = false;
	static public $sid;
	static public $uid_count = 'AAAAAA';

	static public $restrict_modes;
	static public $status_modes = array();
	static public $owner = false;
	static public $protect = false;
	static public $halfop = false;
	
	static public $modes_params;
	static public $modes_p_unrequired;
	static public $modes;
	static public $max_params = 6;
	
	static public $jupes = array();
	static public $motd_start = '- {server} message of the day';
	static public $motd_end = 'End of message of the day.';
	static public $default_c_modes = 'nt';
	
	static public $prefix_modes = array();
	
	static public $service_modes = array(
		'enforcer' 	=> '+Ii',
		'service'	=> '+Iio',
	);
	// we have a bunch of variables here
	// most of these are defined when we boot up
	// but alot of them will still need changed if your coding
	// a protocol module, even $modes_params; bear that in mind.
	
	/*
	* __construct
	*
	* @params
	* void
	*/
	public function __construct()
	{
		modules::init_module( 'inspircd12', self::MOD_VERSION, self::MOD_AUTHOR, 'protocol', 'static' );
		self::$sid = core::$config->server->numeric;
		// these are standard in module constructors
	}
	
	/*
	* handle_on_server
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_on_server( $ircdata )
	{
		ircd_handle::handle_on_server( $ircdata[1], $ircdata[4], self::$sid );
	}
	
	/*
	* handle_on_squit
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_on_squit( $ircdata )
	{
		$server = str_replace( ':', '', $server );
		
		ircd_handle::handle_on_squit( $server );
	}
	
	/*
	* handle_on_connect
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_on_connect( $ircdata, $startup = false )
	{
		$nick = $ircdata[4];
		$server = ircd_handle::get_server( $ircdata, 0 );
		$gecos = core::get_data_after( $ircdata, 11 );
		$gecos = explode( ':', $gecos );
		$gecos = $gecos[1];
		// get nick, server, gecos
		
		if ( $nick[0] == ':' ) $nick = substr( $nick, 1 );
		if ( $gecos[0] == ':' ) $gecos = substr( $gecos, 1 );
		if ( $server[0] == ':' ) $server = substr( $server, 1 );
		// strip :
		
		ircd_handle::handle_on_connect( $nick, $ircdata[2], $ircdata[7], $ircdata[6], $ircdata[5], $gecos, $server, $ircdata[3], $startup );
	}
	
	/*
	* handle_nick_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_nick_change( $ircdata, $startup = false )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		$new_nick = $ircdata[2];
		
		if ( $nick[0] == ':' ) $nick = substr( $nick, 1 );
		if ( $new_nick[0] == ':' ) $new_nick = substr( $new_nick, 1 );
		// strip :
	
		ircd_handle::handle_nick_change( $nick, $new_nick, $startup );
	}
	
		
	/*
	* handle_quit
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_quit( $ircdata, $startup = false )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		if ( $nick[0] == ':' ) $nick = substr( $nick, 1 );
		// strip :
		
		ircd_handle::handle_quit( $nick, $startup );
	}
	
	/*
	* handle_host_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_host_change( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		ircd_handle::handle_host_change( $nick, $ircdata[2] );
	}
	
	/*
	* handle_ident_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_ident_change( $ircdata )
	{
		if ( $ircdata[1] == 'CHGIDENT' )
		{
			$nick = ircd_handle::get_nick( $ircdata, 2 );
			$ident = substr( $ircdata[3], 1 );
		}
		elseif ( $ircdata[1] == 'SETIDENT' )
		{
			$nick = ircd_handle::get_nick( $ircdata, 0 );
			$ident = substr( $ircdata[2], 1 );
		}
		
		ircd_handle::handle_ident_change( $nick, $ident );
	}
	
	/*
	* handle_gecos_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_gecos_change( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		$gecos = substr( core::get_data_after( $ircdata, 2 ), 1 );
	
		ircd_handle::handle_gecos_change( $nick, $gecos );
	}
	
	/*
	* handle_mode
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_mode( $ircdata )
	{
		$chan = core::get_chan( $ircdata, 2 );
		$mode_queue = core::get_data_after( $ircdata, 4 );
	
		ircd_handle::handle_mode( $chan, $mode_queue );
	}
	
	/*
	* handle_ftopic
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_ftopic( $ircdata )
	{
		$nick = explode( '!', $ircdata[4] );
		$nick = $nick[0];
		// get the nick
		$chan = core::get_chan( $ircdata, 2 );
		$topic = trim( substr( core::get_data_after( $ircdata, 5 ), 1 ) );
		// grab the topic
	
		ircd_handle::handle_ftopic( $chan, $topic, $nick );
	}
	
	/*
	* handle_topic
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_topic( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		$chan = core::get_chan( $ircdata, 2 );
		$topic = trim( substr( core::get_data_after( $ircdata, 3 ), 1 ) );
		// grab the topic
	
		ircd_handle::handle_topic( $chan, $topic, $nick );
	}
	
	/*
	* handle_channel_create
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_channel_create( $ircdata )
	{
		$chans = explode( ',', $ircdata[2] );
		$chan = $chans[0];
		// parse the chans sending an array, although we shouldn't actually get these in an FJOIN, do it to be safe.
		
		$nusers_str = implode( ' ', $ircdata );
		$nusers_str = explode( ':', $nusers_str );
		// right here we need to find out where the thing is, because
		// of the way 1.2 handles FJOINs
		$nusers = ircd_handle::parse_users( $chan, $nusers_str, 1 );
		
		$mode_queue = core::get_data_after( $ircdata, 4 );
		$mode_queue = explode( ':', $mode_queue );
		$mode_queue = trim( $mode_queue[0] );
		// get the mode queue from ircdata and explode via :, which is n_users stuff, which we don't want!
	
		ircd_handle::handle_channel_create( $chans, $nusers, $ircdata[3], $mode_queue );
	}
	
	/*
	* handle_join
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_join( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		$chans = explode( ',', $ircdata[2] );
	
		ircd_handle::handle_join( $chans, $nick );
	}

	/*
	* handle_part
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_part( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		$chan = core::get_chan( $ircdata, 2 );
	
		ircd_handle::handle_part( $chan, $nick );
	}
	
	/*
	* handle_kick
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_kick( $ircdata )
	{
		$chan = core::get_chan( $ircdata, 2 );
		$who = ircd_handle::get_nick( $ircdata, 3 );
	
		ircd_handle::handle_kick( $chan, $who );
	}
	
	/*
	* handle_oper_up
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_oper_up( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		
		ircd_handle::handle_oper_up( $nick );
	}

	/*
	* ircd functions
	*
	* our functions like core::kick, grabbed from the ircd protocol class.
	*/
	
	/*
	* send_burst
	*
	* @params
	* $server
	*/
	static public function send_burst( $server )
	{
		self::send( ':'.$server.' BURST '.core::$network_time );
	}
	
	/*
	* send_squit
	*
	* @params
	* $server
	*/
	static public function send_squit( $server )
	{
		self::send( ':'.core::$config->server->name.' SQUIT '.$server.' :SQUIT' );
	}

	/*
	* ping
	*
	* @params
	* $ircdata - ..
	*/
	static public function ping( $ircdata )
	{
		if ( self::on_ping( $ircdata ) )
		{
			ircd_handle::ping( $ircdata[1] );
			
			self::send( 'PONG '. $ircdata[1], core::$socket );
			return true;
        }
		// ping pong.
		
		return false;
	}
	
	/*
	* get_information
	*
	* @params
	* $ircdata - ..
	*/
	static public function get_information( $ircdata )
	{
		if ( isset( $ircdata[0] ) && $ircdata[0] == 'CAPAB' && $ircdata[1] == 'MODULES' )
		{
			$ircd_modules = explode( ',', $ircdata[2] );
			$services_account = false;
			$hidechans = false;
			$globops = false;
			$chghost = false;
			$chgident = false;
			// setup some vars. (this part is probably the trickest part of coding protocol modules
			// because unreal etc don't use insp style modules see irc.ircnode.org #acora for assistance.
			
			if ( in_array( 'm_services_account.so', $ircd_modules ) )
				$services_account = true;
			// we have services_account
			
			if ( in_array( 'm_hidechans.so', $ircd_modules ) )
				$hidechans = true;
			// we have hidechans
			
			if ( in_array( 'm_globops.so', $ircd_modules ) )
				$globops = true;
			// we have globops!
			
			if ( in_array( 'm_chghost.so', $ircd_modules ) )
				$chghost = true;
			// we have chghost
			
			if ( in_array( 'm_chgident.so' ) !== false )
				$chgident = true;
			// and chgident
			
			ircd_handle::parse_ircd_modules( $services_account, $hidechans, $globops, $chghost, $chgident );
		}
		// only trigger when our modules info is coming through
		
		if ( isset( $ircdata[0] ) && $ircdata[0] == 'CAPAB' && $ircdata[1] == 'CAPABILITIES' )
		{
			$max_modes = explode( '=', $ircdata[5] );
			$max_modes = $max_modes[1];
			$pdata = explode( '=', $ircdata[15] );
			$pdata = $pdata[1];
			$data = explode( '=', $ircdata[16] );
			$data = $data[1];
		
			ircd_handle::parse_ircd_modes( $max_modes, $pdata, $data );
			// parse some data out of CAPABILITIES and send it into parse_ircd_modes
		}
		// only trigger when the capab capabilities is coming through
		
		return true;
	}
	
	/*
	* init_server
	*
	* @params
	* $name - name of server
	* $pass - link pass
	* $desc - server gecos
	* $numeric - server numeric
	*/
	static public function init_server( $name, $pass, $desc, $numeric )
	{
		self::send( 'SERVER '.$name.' '.$pass.' 0 '.self::$sid.' :'.$desc );
		
		ircd_handle::init_server( $name, $pass, $desc, $numeric );
		// call the handler
	}
		
	/*
	* send_version
	*
	* @params
	* $version - version
	* $name - server name
	* $ircd - ircd
	*/
	static public function send_version( $version, $name, $ircd )
	{
		self::send( ':'.self::$sid.' VERSION :acora-'.core::$version.' '.core::$config->server_name.' '.core::$config->ircd.' booted: '.date( 'F j, Y, g:i a', core::$network_time ).'' );
		// ooh, version?
	}
	
	/*
	* introduce_client
	*
	* @params
	* $nick - nick
	* $ident - ident
	* $hostname - hostname
	* $gecos - user's gecos (realname)
	* $enforcer - set this to true to deny the user power etc.
	*/
	static public function introduce_client( $nick, $ident, $hostname, $gecos, $enforcer = false )
	{
		++self::$uid_count;
		$uid = self::$sid . self::$uid_count;
		// produce our random UUID (internal and TS6 specific).
		
		if ( $enforcer )
			$service_mode = self::$service_modes['enforcer'];
		else
			$service_mode = self::$service_modes['service'];
		// what do we use?
			
		self::send( ':'.self::$sid.' UID '.$uid.' '.core::$network_time.' '.$nick.' '.$hostname.' '.$hostname.' '.$ident.' '.core::$config->conn->vhost.' '.core::$network_time.' '.$service_mode.' :'.$gecos );
		
		if ( !$enforcer )
			self::send( ':'.$uid.' OPERTYPE Service' );
		// send the opertype
		
		ircd_handle::introduce_client( $nick, $uid, $ident, $hostname, $gecos, $enforcer );
		// handle it
	}
	
	/*
	* remove_client
	* 
	* @params
	* $nick - nick
	* $message - quit message
	*/
	static public function remove_client( $nick, $message )
	{
		$uid = ircd_handle::get_uid( $nick );
		// get the uid.
		
		self::send( ':'.$uid.' QUIT '.$message );
		// as simple as.
		
		ircd_handle::remove_client( $nick, $uid, $message );
		// handle it
	}
	
	/*
	* globops
	*
	* @params
	* $nick - who to send it from
	* $message - message to send
	*/
	static public function globops( $nick, $message )
	{
		if ( self::$globops && core::$config->settings->silent )
		{
			$unick = ircd_handle::get_uid( $nick );
			self::send( ':'.$unick.' GLOBOPS :'.$message );
			// get the uid and send it.
			
			ircd_handle::globops( $nick, $message );
			// handle it
		}
	}
	
	/*
	* global_notice
	*
	* @params
	* $nick - who to send it from
	* $mask - mask to use
	* $message - message to send
	*/
	static public function global_notice( $nick, $mask, $message )
	{
		ircd_handle::global_notice( $nick, $mask, $message );
	}
	
	/*
	* notice
	*
	* @params
	* $nick - who to send it from
	* $what - what to send it to
	* $message - message to send
	*/
	static public function notice( $nick, $what, $message )
	{
		$nick = ircd_handle::get_uid( $nick );
		if ( $what[0] != '#' ) $what = ircd_handle::get_uid( $what );
		// get the uid.
		
		self::send( ':'.$nick.' NOTICE '.$what.' :'.$message );
	}
	
	/*
	* msg
	*
	* @params
	* $nick - who to send it from
	* $what - what to send it to
	* $message - message to send
	*/
	static public function msg( $nick, $what, $message )
	{
		$nick = ircd_handle::get_uid( $nick );
		if ( $what[0] != '#' ) $what = ircd_handle::get_uid( $what );
		// get the uid.
		
		self::send( ':'.$nick.' PRIVMSG '.$what.' :'.$message );
	}
	
	/*
	* mode
	*
	* @params
	* $nick - who to send it from
	* $chan - the channel to use
	* $mode - mode to set
	*/
	static public function mode( $nick, $chan, $mode )
	{
		$unick = ircd_handle::get_uid( $nick );
		// get the uid.
		
		if ( $mode[0] != '-' && $mode[0] != '+' ) $mode = '+'.$mode;
		
		$old_mode = $mode;
		$mode = mode::check_modes( $mode );
		// we don't want nobody messing about
		
		if ( trim( $mode ) == '' )
			return false;
		
		self::send( ':'.$unick.' FMODE '.$chan.' '.core::$chans[$chan]['timestamp'].' '.$mode );
		ircd_handle::mode( $nick, $chan, $mode );
		// send the mode then handle it internally
	}
	
	/*
	* umode
	*
	* @params
	* $nick - who to send it from
	* $user - user to use
	* $mode - mode to set
	*/
	static public function umode( $nick, $user, $mode )
	{
		$unick = ircd_handle::get_uid( $nick );
		$uuser = ircd_handle::get_uid( $user );
		// get the uid.
		
		self::send( ':'.$unick.' SVSMODE '.$uuser.' '.$mode );
		ircd_handle::umode( $nick, $user, $mode );
		// send the mode then handle it internally
	}
	
	/*
	* join_chan
	*
	* @params
	* $nick - who to send it from
	* $chan - chan to join
	*/
	static public function join_chan( $nick, $chan )
	{
		$unick = ircd_handle::get_uid( $nick );
		// get the uid.
		
		self::send( ':'.$unick.' JOIN '.$chan.' '.core::$network_time );
		ircd_handle::join_chan( $nick, $chan );
		// send the join then handle it internally
	}
	
	/*
	* part_chan
	*
	* @params
	* $nick - who to send it from
	* $chan - chan to part
	*/
	static public function part_chan( $nick, $chan )
	{
		$unick = ircd_handle::get_uid( $nick );
		// get the uid.
		
		self::send( ':'.$unick.' PART '.$chan );
		ircd_handle::part_chan( $nick, $chan );
		// send the part then handle it internally
	}
	
	/*
	* topic
	*
	* @params
	* $nick - who to send it from
	* $chan - chan to use
	* $topic - topic to set
	*/
	static public function topic( $nick, $chan, $topic )
	{
		$unick = ircd_handle::get_uid( $nick );
		// get the uid.
		
		self::send( ':'.$unick.' TOPIC '.$chan.' :'.$topic );
		ircd_handle::topic( $nick, $chan, $topic );
		// send the cmd then handle it internally
	}
	
	/*
	* kick
	*
	* @params
	* $nick - who to send it from
	* $user - who to kick
	* $chan - chan to use
	* $reason - optional reason
	*/
	static public function kick( $nick, $user, $chan, $reason = '' )
	{
		$urow = core::search_nick( $user );
		
		if ( $urow['server'] != core::$config->server->name )
		{
			$unick = ircd_handle::get_uid( $nick );
			$uuser = ircd_handle::get_uid( $user );
			// get the uid.
			
			self::send( ':'.$unick.' KICK '.$chan.' '.$uuser.' :'.$reason );
			ircd_handle::kick( $nick, $user, $chan, $reason );
			// send the cmd then handle it internally
		}
	}
		
	/*
	* sethost
	*
	* @params
	* $from - from
	* $nick - to nick
	* $host - new host
	*/
	static public function sethost( $from, $nick, $host )
	{
		if ( self::$chghost )
		{
			$ufrom = ircd_handle::get_uid( $from );
			$unick = ircd_handle::get_uid( $nick );
			// get the uid.
			
			self::send( ':'.$ufrom.' CHGHOST '.$unick.' '.$host );
			ircd_handle::sethost( $from, $nick, $host );
			// send the cmd then handle it internally
		}
	}
	
	/*
	* setident
	*
	* @params
	* $from - from
	* $nick - to nick
	* $ident - new ident
	*/
	static public function setident( $from, $nick, $ident )
	{
		if ( self::$chgident )
		{
			$ufrom = ircd_handle::get_uid( $from );
			$unick = ircd_handle::get_uid( $nick );
			// get the uid.
		
			self::send( ':'.$ufrom.' CHGIDENT '.$unick.' '.$ident );
			ircd_handle::setident( $from, $nick, $host );
			// send the cmd then handle it internally
		}
	}
	
	/*
	* svsnick
	*
	* @params
	* $old_nick - old nick
	* $new_nick - new nick
	* $timestamp - timestamp
	*/
	static public function svsnick( $old_nick, $new_nick, $timestamp )
	{
		$uold_nick = ircd_handle::get_uid( $old_nick );
		$unew_nick = ircd_handle::get_uid( $new_nick );
		// get the uid.
		
		self::send( ':'.self::$sid.' SVSNICK '.$uold_nick.' '.$unew_nick.' '.$timestamp );
		ircd_handle::svsnick( $old_nick, $new_nick, $timestamp );
		// send the cmd then handle it internally
	}
	
	/*
	* kill
	*
	* @params
	* $nick - who to send it from
	* $user - who to kill
	* $message - message to use
	*/
	static public function kill( $nick, $user, $message )
	{
		$unick = ircd_handle::get_uid( $nick );
		$uuser = ircd_handle::get_uid( $user );
		// get the uid.
		
		self::send( ':'.$unick.' KILL '.$uuser.' :Killed ('.$nick.' ('.$message.')))' );
		ircd_handle::svsnick( $nick, $user, $timestamp );
		// send the cmd then handle it internally
	}
	
	/*
	* gline
	*
	* @params
	* $nick - who to send it from
	* $mask - the mask of the gline
	* $duration - the duration
	* $message - message to use
	*/
	static public function gline( $nick, $mask, $duration, $message )
	{
		self::send( ':'.core::$config->server->name.' ADDLINE G '.$mask.' '.$nick.' '.core::$network_time.' '.$duration.' :'.$message );
		ircd_handle::gline( $nick, $mask, $duration, $message );
		// send the cmd then handle it internally
	}
	
	/*
	* shutdown
	*
	* @params
	* $message - shutdown message
	* $terminate - true/false whether to terminate the service.
	*/
	static public function shutdown( $message, $terminate = false )
	{
		self::send( ':'.core::$config->server->name.' SQUIT '.core::$config->server->name.' :'.$message );
		ircd_handle::shutdown( $message, $terminate );
		// send the cmd then handle it internally
	}
	
	/*
	* push
	*
	* @params
	* $from - from
	* $numeric - numeric
	* $nick - to nick
	* $message - array of things to send.
	*/
	static public function push( $from, $numeric, $nick, $message )
	{
		$unick = ircd_handle::get_uid( $nick );
		$ufrom = ircd_handle::get_uid( $from );
		// get the uid.
		
		$message = implode( ' ', $message );
		// implode the message
		
		self::send( ':'.$ufrom.' PUSH '.$unick.' ::'.$ufrom.' '.$numeric.' '.$unick.' '.$message );
		ircd_handle::shutdown( $from, $numeric, $nick, $message );
		// send the cmd then handle it internally
	}
	
	/*
	* end_burst
	*
	* @params
	* void
	*/
	static public function end_burst()
	{
		self::send( ':'.self::$sid.' ENDBURST' );
	}
	
	/*
	* send
	*
	* @params
	* $command - command to send
	*/
	static public function send( $command )
	{
		ircd_handle::send( $command );
	}
	
    /*
	* core events
	*
	* These are all the core event functions, core::on_start etc.
	*/
	
	/*
	* on_user_login
	*
	* @params
	* $nick - nick
	*/
	static public function on_user_login( $nick )
	{
		$uid = ircd_handle::get_uid( $nick );
		self::send( ':'.self::$sid.' METADATA '.$uid.' accountname :'.$nick );
	}
	
	/*
	* on_user_logout
	*
	* @params
	* $nick - nick
	*/
	static public function on_user_logout( $nick )
	{
		$uid = ircd_handle::get_uid( $nick );
		self::send( ':'.self::$sid.' METADATA '.$uid.' accountname :' );	
	}

	/*
	* on_capab_start
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_capab_start( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[0] == 'CAPAB' && $ircdata[1] == 'START' )
			return true;
		
		return false;
	}

	/*
	* on_capab_end
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_capab_end( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[0] == 'CAPAB' && $ircdata[1] == 'END' )
			return true;
		
		return false;
	}

	/*
	* on_timeset
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_timeset( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'TIMESET' )
		{
			ircd_handle::on_timeset( $ircdata[2] );
			
			return true;
		}
		
		return false;
	}
	
	/*
	* on_start_burst
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_start_burst( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'BURST' )
			return true;
		
		return false;
	}
	
	/*
	* on_end_burst
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_end_burst( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'ENDBURST' )
			return true;
		
		return false;
	}
	
	/*
	* on_server
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_server( $ircdata )
	{
		if ( isset( $ircdata[0] ) && $ircdata[0] == 'SERVER' )
			return true;
		
		return false;
	}
	
	/*
	* on_squit
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_squit( $ircdata )
	{
		if ( isset( $ircdata[1] ) && ( $ircdata[1] == 'RSQUIT' || $ircdata[1] == 'SQUIT' ) )
			return true;
		
		return false;
	}
	
	/*
	* on_ping
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_ping( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'PING' )
			return true;
		
		return false;
	}
	
	/*
	* on_connect
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_connect( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'UID' )
		{
			ircd_handle::on_connect( $ircdata[4], ircd_handle::get_server( $ircdata, 0 ) );
			return core::$nicks[$ircdata[4]];
		}
		// return true when the $ircdata finds a (remote)connect.
		
		return false;
	}
	
	/*
	* on_quit
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_quit( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'QUIT' )
		{
			ircd_handle::on_connect( ircd_handle::get_nick( $ircdata, 0 ) );
			return true;
		}
		// return true when the $ircdata finds a quit.
		
		return false;
	}
	
	/*
	* on_fhost
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_fhost( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'FHOST' )
		{
			ircd_handle::on_fhost( ircd_handle::get_nick( $ircdata, 0 ), $ircdata[2] );
			return true;
		}
		// return true when the $ircdata finds a host change
		
		return false;
	}
	
	/*
	* on_chan_create
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_chan_create( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'FJOIN' )
			return $ircdata[2];
		// return true when any channel is created, because $chan isnt set.
		
		return false;
	}
	
	/*
	* on_join
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_join( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'JOIN' )
		{
			ircd_handle::on_join( ircd_handle::get_nick( $ircdata, 0 ), $ircdata[2] );
			return true;
		}
		// return true when any channel is joined, because $chan isnt set.
		
		return false;
	}
	
	/*
	* on_part
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_part( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'PART' )
		{
			ircd_handle::on_part( ircd_handle::get_nick( $ircdata, 0 ), $ircdata[2] );
			return true;
		}
		// return true when any channel is parted, because $chan isnt set.
		
		return false;
	}
	
	/*
	* on_mode
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_mode( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'FMODE' )
		{
			ircd_handle::on_mode( ircd_handle::get_nick( $ircdata, 0 ), core::get_data_after( $ircdata, 4 ), $ircdata[2] );
			return true;
		}
		// return true when any channel has a mode change, because $chan isnt set.
		
		return false;
	}
	
	/*
	* on_kick
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_kick( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'KICK' )
		{
			ircd_handle::on_kick( ircd_handle::get_nick( $ircdata, 0 ), core::get_data_after( $ircdata, 3 ), $ircdata[2] );
			return true;
		}
		// return true when anyone is kicked from any channel, because $chan isnt set.
		
		return false;
	}
	
	/*
	* on_topic
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_topic( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'TOPIC' )
		{
			ircd_handle::on_topic( $ircdata[2] );
			return true;
		}
		// return true when any channel's topic is changed, because $chan isnt set.
		
		return false;
	}
	
	/*
	* on_ftopic
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_ftopic( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'FTOPIC' )
		{
			ircd_handle::on_ftopic( $ircdata[2] );
			return true;
		}
		// return true when any channel's topic is changed, because $chan isnt set.
		
		return false;
	}
	
	/*
	* on_oper_up
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_oper_up( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'OPERTYPE' )
		{
			ircd_handle::on_oper_up( ircd_handle::get_nick( $ircdata, 0 ), str_replace( '_', ' ', $ircdata[2] ) );
			return true;
		}
		// return true when a oper up is matched, and not an oper warning x]
		
		return false;
	}
	
	/*
	* on_msg
	*
	* @params
	* $ircdata - ..
	* $where - optional
	*/
	static public function on_msg( $ircdata, $where = '' )
	{
		if ( $where != '' )
		{
			if ( $where[0] != '#' ) $where = ircd_handle::get_uid( $where );
			
			if ( isset( $ircdata[1] ) && ( $ircdata[1] == 'PRIVMSG' && $ircdata[2] == $where ) )
				return true;
			// return true providing $where matches where it was sent, crafty.
			// clearly doesn't make much sence imo. lol
		}
		else
		{
			if ( isset( $ircdata[1] ) && $ircdata[1] == 'PRIVMSG' )
				return true;
			// return true on any privmsg, because $where aint set.
		}
		
		return false;
	}
	
	/*
	* on_notice
	*
	* @params
	* $ircdata - ..
	* $where - optional
	*/
	static public function on_notice( $ircdata, $where = '' )
	{
		if ( $where != '' )
		{
			if ( $where[0] != '#' ) $where = ircd_handle::get_uid( $where );
			
			if ( isset( $ircdata[1] ) && $ircdata[1] == 'NOTICE' && $ircdata[2] == $where )
				return true;
			// return true providing $where matches where it was sent, crafty.
			// clearly doesn't make much sence imo. lol
		}
		else
		{
			if ( isset( $ircdata[1] ) && $ircdata[1] == 'NOTICE' )
				return true;
			// return true on any notice, because $where aint set.
		}
		
		return false;
	}
	
	/*
	* on_nick_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_nick_change( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'NICK' && count( $ircdata ) == 4 )
		{
			ircd_handle::on_nick_change( ircd_handle::get_nick( $ircdata, 0 ), $ircdata[2] );
			return true;
		}
		// return true on any nick change.
		
		return false;
	}
	
	/*
	* on_ident_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_ident_change( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'CHGIDENT' )
		{
			ircd_handle::on_ident_change( ircd_handle::get_nick( $ircdata, 0 ), substr( $ircdata[3], 1 ) );
			return true;
		}
		// return true on chgident.
		
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'SETIDENT' )
		{
			ircd_handle::on_ident_change( ircd_handle::get_nick( $ircdata, 0 ), substr( $ircdata[2], 1 ) );
			return true;
		}
		// return true on setident.
		
		return false;
	}
	
	/*
	* on_gecos_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_gecos_change( $ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'FNAME' )
		{
			ircd_handle::on_gecos_change( ircd_handle::get_nick( $ircdata, 0 ), substr( $ircdata[2], 1 ) );
			return true;
		}
		// return true on fname.
		
		return false;
	}
	
	/*
	* get_server
	*
	* @params
	* $ircdata - ..
	* $number - ..
	*/
	static public function get_server( $ircdata, $number )
	{
		return ircd_handle::get_server( $ircdata, $number );
	}
	
	/*
	* get_nick
	*
	* @params
	* $ircdata - ..
	* $number - ..
	*/
	static public function get_nick( $ircdata, $number )
	{
		return ircd_handle::get_nick( $ircdata, $number );	
	}
	
	/*
	* get_uid
	*
	* @params
	* $nick - should be a valid nickname
	*/
	static public function get_uid( $nick )
	{
		return ircd_handle::get_uid( $nick );
	}
	
	/*
	* parse_users
	*
	* @params
	* $ircdata - ..
	*/
	static public function parse_users( $chan, $ircdata, $number )
	{
		return ircd_handle::parse_users( $chan, $ircdata, $number );
	}
}
// EOF;