<?php

/*
* Acora IRC Services
* core/protocol/charybdis33.php: Provides support for Charybdis 3.3
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

	const MOD_VERSION = '0.1.3';
	const MOD_AUTHOR = 'Acora';
	// module info.

	static public $ircd = 'Charybdis 3.3';
	static public $chghost = false;
	static public $chgident = false;
	static public $sid;
	static public $uid_count = 'AAAAAA';
	
	static private $trick_capab_start = 'Looking up your hostname...';
	static private $trick_capab_end = 'Found your hostname';
	static private $last_sid;
	static private $prefix_data = '(ov)@+';
	static private $mode_data = 'eIb,k,flj,CFPcgimnpstz';

	static public $restrict_modes;
	static public $status_modes = array();
	static public $owner = false;
	static public $protect = false;
	static public $halfop = false;
	
	static public $modes_params;
	static public $modes_p_unrequired;
	static public $modes;
	static public $max_params = 4;
	
	static public $jupes = array();
	static public $motd_start = '- {server} message of the day';
	static public $motd_end = 'End of message of the day.';
	static public $default_c_modes = 'nt';
	
	static public $prefix_modes = array();
	
	static public $service_modes = array(
		'enforcer' 	=> '+i',
		'service'	=> '+Sio',
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
		modules::init_module( 'charybdis33', self::MOD_VERSION, self::MOD_AUTHOR, 'protocol', 'static' );
		self::$sid = core::$config->server->numeric;
		// these are standard in module constructors
		
		ircd_handle::add_command( 'NOTICE', 'on_notice' );
		ircd_handle::add_command( 'CAPAB', 'on_capab' );
		ircd_handle::add_command( 'SVINFO', 'on_start_burst' );
		ircd_handle::add_command( 'PING', 'on_ping' );
		ircd_handle::add_command( 'VERSION', 'send_version' );
		ircd_handle::add_command( 'PASS', 'on_server' );
		ircd_handle::add_command( 'SERVER', 'on_server' );
		ircd_handle::add_command( 'SID', 'on_server' );
		ircd_handle::add_command( 'SQUIT', 'on_squit' );
		ircd_handle::add_command( 'EUID', 'on_connect' );
		ircd_handle::add_command( 'QUIT', 'on_quit' );
		ircd_handle::add_command( 'CHGHOST', 'on_fhost' );
		ircd_handle::add_command( 'SJOIN', 'on_chan_create' );
		ircd_handle::add_command( 'JOIN', 'on_join' );
		ircd_handle::add_command( 'PART', 'on_part' );
		ircd_handle::add_command( 'BMASK', 'on_mode' );
		ircd_handle::add_command( 'TMODE', 'on_mode' );
		ircd_handle::add_command( 'KICK', 'on_kick' );
		ircd_handle::add_command( 'TB', 'on_topic' );
		ircd_handle::add_command( 'TOPIC', 'on_topic' );
		ircd_handle::add_command( 'MODE', 'on_umode' );
		ircd_handle::add_command( 'PRIVMSG', 'on_msg' );
		ircd_handle::add_command( 'NICK', 'on_nick_change' );
		ircd_handle::add_command( 'SIGNON', 'on_gecos_change' );
		ircd_handle::add_command( 'ERROR', 'on_error' );
		// add all our commands :3 new fancy command handler, saves code and helps me
		// keep these modules clean-er
	}
	
	/*
	* send_burst
	*
	* @params
	* $server
	*/
	static public function send_burst( $server )
	{
		ircd_handle::send( ':'.self::$sid.' SVINFO 6 6 0 '.core::$network_time );
	}
	
	/*
	* send_squit
	*
	* @params
	* $server
	*/
	static public function send_squit( $server )
	{
		ircd_handle::send( ':'.$server.' SQUIT :SQUIT' );
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
		ircd_handle::send( 'PASS '.$pass.' TS 6 '.self::$sid );
		ircd_handle::send( 'CAPAB :QS EX CHW IE KLN KNOCK TB UNKLN ENCAP SERVICES RSFNC SAVE EUID EOPMOD BAN' );
		ircd_handle::send( ':'.self::$sid.' SERVER '.$name.' 0 :'.$desc );
		// handle server, bit of trickery for ON CAPAB START etc does on here ;)
		
		ircd_handle::init_server( $name, $pass, $desc, $numeric );
		// call the handler
	}
		
	/*
	* send_version
	*
	* @params
	* void
	*/
	static public function send_version( $ircdata )
	{
		$nick = str_replace( ':', '', $ircdata[0] );
	
		ircd_handle::send( ':'.self::$sid.' 351 '.$nick.' :acora-'.base64_decode( core::$version ).' '.core::$config->server->name.' '.core::$config->server->ircd.' booted: '.date( 'F j, Y, g:i a', core::$network_time ).'' );
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
		$uid = self::$sid.self::$uid_count;
		// produce our random UUID (internal and TS6 specific).
		
		if ( $enforcer )
			$service_mode = self::$service_modes['enforcer'];
		else
			$service_mode = self::$service_modes['service'];
		// what do we use?
		
		ircd_handle::send( ':'.self::$sid.' EUID '.$nick.' 1 '.core::$network_time.' '.$service_mode.' '.$ident.' '.$hostname.' '.core::$config->conn->vhost.' '.$uid.' * * :'.$gecos );		
		
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
		
		ircd_handle::send( ':'.$uid.' QUIT :'.$message );
		// as simple as.
		
		ircd_handle::remove_client( $nick, $uid, $message );
		// handle it
	}
	
	/*
	* wallops
	*
	* @params
	* $nick - who to send it from
	* $message - message to send
	*/
	static public function wallops( $nick, $message )
	{
		if ( core::$config->settings->silent )
		{
			$unick = ircd_handle::get_uid( $nick );
			ircd_handle::send( ':'.$unick.' WALLOPS :'.$message );
			// get the uid and send it.
			
			ircd_handle::wallops( $nick, $message );
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
		
		ircd_handle::send( ':'.$nick.' NOTICE '.$what.' :'.$message );
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
		
		ircd_handle::send( ':'.$nick.' PRIVMSG '.$what.' :'.$message );
	}
	
	/*
	* mode
	*
	* @params
	* $nick - who to send it from
	* $chan - the channel to use
	* $mode - mode to set
	* $boolean - if set to true sid will be sent instead of nick
	*/
	static public function mode( $nick, $chan, $mode, $boolean = false )
	{
		$unick = ircd_handle::get_uid( $nick );
		// get the uid.
		
		$from = ( $boolean ) ? self::$sid : $unick;
		// check what we send
		
		ircd_handle::send( ':'.$from.' TMODE '.core::$chans[$chan]['timestamp'].' '.$chan.' '.$mode );
		ircd_handle::mode( $nick, $chan, $mode );
		// send the mode
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
		
		ircd_handle::send( ':'.$unick.' MODE '.$uuser.' :'.$mode );
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
		
		ircd_handle::send( ':'.$unick.' JOIN '.core::$network_time.' '.$chan.' +' );
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
		
		ircd_handle::send( ':'.$unick.' PART '.$chan );
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
		
		ircd_handle::send( ':'.$unick.' TOPIC '.$chan.' :'.$topic );
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
			
			ircd_handle::send( ':'.$unick.' KICK '.$chan.' '.$uuser.' :'.$reason );
			ircd_handle::kick( $nick, $user, $chan, $reason );
			// send the cmd then handle it internally
		}
	}
	
	/*
	* invite
	*
	* @params
	* $nick - who to send it from
	* $user - who to invite
	* $chan - chan to use
	*/
	static public function invite( $nick, $user, $chan )
	{
		$unick = ircd_handle::get_uid( $nick );
		$uuser = ircd_handle::get_uid( $user );
		// get the uid.
			
		ircd_handle::send( ':'.$unick.' INVITE '.$uuser.' '.$chan.' '.core::$chans[$chan]['timestamp'] );
		// send the cmd
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
		if ( self::$chghost && core::$nicks[$nick]['host'] != $host )
		{
			$ufrom = ircd_handle::get_uid( $from );
			$unick = ircd_handle::get_uid( $nick );
			// get the uid.
			
			ircd_handle::send( ':'.$ufrom.' CHGHOST '.$unick.' '.$host );
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
		// we can't even force the change of an ident in charybdis..
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
		// get the uid.
		
		ircd_handle::send( ':'.self::$sid.' ENCAP * RSFNC '.$uold_nick.' '.$new_nick.' '.$timestamp.' '.$timestamp );
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
		
		ircd_handle::send( ':'.$unick.' KILL '.$uuser.'  :Killed ('.$nick.' ('.$message.')))' );
		ircd_handle::svsnick( $nick, $user, $timestamp );
		// send the cmd then handle it internally
	}
	
	/*
	* global_ban
	*
	* @params
	* $nick - who to send it from
	* $mask - *@* hostmask
	* $duration - the duration
	* $message - message to use
	*/
	static public function global_ban( $nick, $mask, $duration, $message )
	{
		if ( $user['ircop'] )
			return false;
		// if ircop ignore
	
		$unick = ircd_handle::get_uid( $nick );
		$mask = explode( '@', $mask );
		// set some vars
		
		ircd_handle::send( ':'.$unick.' ENCAP * KLINE '.$duration.' '.$mask[0].' '.$mask[1].' :'.$message );
		ircd_handle::global_ban( $nick, $mask[0].'@'.$mask[1], $duration, $message );
		// send the cmd then handle it internally
	}
	
	/*
	* global_unban
	*
	* @params
	* $nick - nick
	* $mask - *@* hostmask
	*/
	static public function global_unban( $nick, $mask )
	{
		$unick = ircd_handle::get_uid( $nick );
		$mask = explode( '@', $mask );
		// set some vars
		
		ircd_handle::send( ':'.$unick.' ENCAP * UNKLINE '.$mask[0].' '.$mask[1] );
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
		ircd_handle::send( ':'.core::$config->server->name.' SQUIT :'.$message );
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
		// get the uid.
		
		$message = implode( ' ', $message );
		// implode the message
		
		ircd_handle::send( ':'.self::$sid.' '.$numeric.' '.$unick.' :'.$message );
		ircd_handle::push( $from, $numeric, $nick, $message );
		// send the cmd then handle it internally
	}
	
	/*
	* set_registered_mode
	*
	* @params
	* $nick, $channel
	*/
	static public function set_registered_mode( $nick, $channel ) { }
	
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
	static public function on_user_login( $nick, $account )
	{
		$uid = ircd_handle::get_uid( $nick );
		ircd_handle::send( ':'.self::$sid.' ENCAP * SU '.$uid.' :'.$account );
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
		ircd_handle::send( ':'.self::$sid.' ENCAP * SU '.$uid. ' :' );	
	}
	
	/*
	* on_capab
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_capab( $ircdata )
	{
		ircd_handle::parse_ircd_modules( true, true, true, true, false );
		ircd_handle::parse_ircd_modes( self::$max_params, self::$prefix_data, self::$mode_data, false );
		// we just immediately send true into all the module parses because charybdis doesnt have options to disable CHGHOST etc.
		// parse some data out of CAPABILITIES and send it into parse_ircd_modes
		
		self::$owner = false; 
		self::$restrict_modes .= 'q';
		self::$modes_params .= 'q';
		self::$modes .= 'q';
		// charybdis never has owner, and +q means something different here
	}
	
	/*
	* on_start_burst
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_start_burst( $ircdata )
	{
		core::$end_burst = false;
		core::$burst_time = microtime( true );
		// how long did the burst take?
		
		core::post_boot_server();
		// post boot
	}
	
	/*
	* on_server
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_server( $ircdata )
	{
		if ( $ircdata[0] == 'PASS' )
			self::$last_sid = substr( $ircdata[4], 1 );
		elseif ( $ircdata[0] == 'SERVER' )
			ircd_handle::handle_on_server( $ircdata[1], self::$last_sid, self::$sid );
		elseif ( $ircdata[1] == 'SID' )
			ircd_handle::handle_on_server( $ircdata[2], $ircdata[4], self::$sid );
	}
	
	/*
	* on_squit
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_squit( $ircdata )
	{
		ircd_handle::handle_on_squit( $ircdata[1] );
		// handle squit
	}
	
	/*
	* on_ping
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_ping( $ircdata )
	{
		if ( !core::$end_burst )
		{
			core::$burst_time = round( microtime( true ) - core::$burst_time, 4 );
			if ( core::$burst_time[0] == '-' ) substr( core::$burst_time, 1 );
			// nasty hack to get rid of minus values.. they are sometimes displayed
			// i don't know why.. maybe on clock shifts..
			// how long did the burst take?
			
			core::$end_burst = true;
			core::save_logs();
			// force a log change and stuff
		}
		
		ircd_handle::ping( $ircdata[1] );
		ircd_handle::send( ':'.self::$sid.' PONG '.$ircdata[1] );
		// handle ping and stuff
	}
	
	/*
	* on_connect
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_connect( $ircdata )
	{
		$server = ircd_handle::get_server( $ircdata, 0 );
		$gecos = core::get_data_after( $ircdata, 12 );
		$gecos = explode( ':', $gecos );
		$gecos = $gecos[1];
		// get nick, server, gecos
		
		if ( $gecos[0] == ':' ) $gecos = substr( $gecos, 1 );
		if ( $server[0] == ':' ) $server = substr( $server, 1 );
		// strip :
		
		ircd_handle::handle_on_connect( $ircdata[2], $ircdata[9], $ircdata[6], $ircdata[7], $ircdata[7], $gecos, $ircdata[8], $server, $ircdata[4], $ircdata[5], !core::$end_burst );
	}
	
	/*
	* on_quit
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_quit( $ircdata )
	{
		ircd_handle::handle_quit( ircd_handle::get_nick( $ircdata, 0 ), !core::$end_burst );
	}
	
	/*
	* on_fhost
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_fhost( $ircdata )
	{
		ircd_handle::handle_host_change( ircd_handle::get_nick( $ircdata, 0 ), $ircdata[2] );
	}
	
	/*
	* on_chan_create
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_chan_create( $ircdata )
	{
		$chans = explode( ',', $ircdata[3] );
		$chan = $chans[0];
		// parse the chans sending an array, although we shouldn't actually get these in an FJOIN, do it to be safe.
		
		$nusers_str = implode( ' ', $ircdata );
		$nusers_str = explode( ':', $nusers_str );
		// right here we need to find out where the thing is, because
		// of the way 1.2 handles FJOINs
		$users = core::get_data_after( $nusers_str, 2 );
		$users = explode( ' ', $users );
		
		$nusers = ircd_handle::parse_users( $chan, $users );
		
		$mode_queue = core::get_data_after( $ircdata, 4 );
		$mode_queue = explode( ':', $mode_queue );
		$mode_queue = trim( $mode_queue[0] );
		// get the mode queue from ircdata and explode via :, which is n_users stuff, which we don't want!
	
		ircd_handle::handle_channel_create( $chan, $nusers, $ircdata[2], $mode_queue );
	}
	
	/*
	* on_join
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_join( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		$chans = explode( ',', $ircdata[3] );
		ircd_handle::handle_join( $chans, $nick );
	}
	
	/*
	* on_part
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_part( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		$chan = core::get_chan( $ircdata, 2 );
		ircd_handle::handle_part( $chan, $nick );
	}
	
	/*
	* on_mode
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_mode( $ircdata )
	{
		$nick = core::get_nick( $ircdata, 0 );
		$nick = ( $nick == '' ) ? ircd_handle::get_server( $ircdata, 0 ) : $nick;
		$chan = core::get_chan( $ircdata, 3 );
		// get the channel!
	
		if ( $ircdata[1] == 'BMASK' )
		{
			$params = ( count( $ircdata ) - 5 );
	
			$mode_queue = '+';
			for ( $i = 0; $i < $params; $i++ )
				$mode_queue .= $ircdata[4];
			
			$mode_queue .= ' '.substr( core::get_data_after( $ircdata, 5 ), 1 );
			// setup a string eh!
		}
		else
		{
			$mode_queue = core::get_data_after( $ircdata, 4 );
		}
		// handle BMASK else just handle the mode.
	
		ircd_handle::handle_mode( $nick, $chan, $mode_queue );
	}
	
	/*
	* on_kick
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_kick( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		$chan = core::get_chan( $ircdata, 2 );
		$who = ircd_handle::get_nick( $ircdata, 3 );
		ircd_handle::handle_kick( $nick, $chan, $who );
	}
	
	/*
	* on_topic
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_topic( $ircdata )
	{
		$chan = core::get_chan( $ircdata, 2 );
	
		if ( $ircdata[1] == 'TB' )
		{
			$nick = explode( '!', $ircdata[4] );
			$nick = $nick[0];
			// get the nick
			$topic = trim( substr( core::get_data_after( $ircdata, 5 ), 1 ) );
			// grab the topic
		}
		else if ( $ircdata[1] == 'TOPIC' )
		{
			$nick = ircd_handle::get_nick( $ircdata, 0 );
			$topic = trim( substr( core::get_data_after( $ircdata, 3 ), 1 ) );
			// grab the topic
		}
	
		ircd_handle::handle_topic( $chan, $topic, $nick );
	}
	
	/*
	* on_umode
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_umode( $ircdata )
	{
		if ( ( substr( $ircdata[3], 1, 1 ) == '+' && strpos( $ircdata[3], 'o' ) !== false ) )
		{
			$nick = ircd_handle::get_nick( $ircdata, 0 );
			ircd_handle::handle_oper_up( $nick );
		}
		// mark people gaining +o as oper
	}
	
	/*
	* on_msg
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_msg( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		$msg = core::get_data_after( $ircdata, 3 );
		
		if ( $ircdata[2][0] != '#' )
			$target = ircd_handle::get_nick( $ircdata, 2 );
		else
			$target = core::get_chan( $ircdata, 2 );
		
		ircd_handle::handle_msg( $nick, $target, $msg );
	}
	
	/*
	* on_notice
	*
	* @params
	* $ircdata - ..
	* $where - optional
	*/
	static public function on_notice( $ircdata )
	{
		if ( isset( $ircdata[4] ) && core::get_data_after( $ircdata, 4 ) == self::$trick_capab_start )
			core::$capab_start = true;
		// we've recieved what we want to, set a few important vars.
	
		if ( isset( $ircdata[4] ) && core::get_data_after( $ircdata, 4 ) == self::$trick_capab_end )
		{
			core::$capab_start = false;	
			core::boot_server();
		}
		// introduce server and mark capab_start as false
		
		// we need to respectivly wait for capab end
		// before we're suppost to boot everything
		// we also set the flag to false cause capab has ended.
	}
	
	/*
	* on_nick_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_nick_change( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		$timestamp = substr( $ircdata[3], 1 );
		// strip :
	
		ircd_handle::handle_nick_change( $nick, $ircdata[2], $timestamp, !core::$end_burst );
	}
	
	/*
	* on_gecos_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_gecos_change( $ircdata )
	{
		$nick = ircd_handle::get_nick( $ircdata, 0 );
		$gecos = substr( core::get_data_after( $ircdata, 2 ), 1 );
	
		ircd_handle::handle_gecos_change( $nick, $gecos );
	}
	
	/*
	* on_error
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_error( $ircdata )
	{
		core::alog( 'ERROR: '.core::get_data_after( $ircdata, 1 ), 'BASIC' );
		core::save_logs();
		self::shutdown( 'ERROR', true );
	}
}

// EOF;
