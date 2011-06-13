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

	const MOD_VERSION = '0.0.3';
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
		'enforcer' 	=> '+Si',
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
	}
	
	/*
	* handle_on_server
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_on_server( $ircdata )
	{
		if ( $ircdata[0] == 'PASS' )
			self::$last_sid = substr( $ircdata[4], 1 );
		else	
			ircd_handle::handle_on_server( $ircdata[1], self::$last_sid, self::$sid );
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
		$nick = $ircdata[2];
		$server = ircd_handle::get_server( $ircdata, 0 );
		$gecos = core::get_data_after( $ircdata, 12 );
		$gecos = explode( ':', $gecos );
		$gecos = $gecos[1];
		// get nick, server, gecos
		
		if ( $gecos[0] == ':' ) $gecos = substr( $gecos, 1 );
		if ( $server[0] == ':' ) $server = substr( $server, 1 );
		// strip :
		
		ircd_handle::handle_on_connect( $nick, $ircdata[9], $ircdata[6], $ircdata[7], $ircdata[7], $gecos, $server, $ircdata[4], $ircdata[5], $startup );
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
		$timestamp = substr( $ircdata[3], 1 );
		// strip :
	
		ircd_handle::handle_nick_change( $nick, $new_nick, $timestamp, $startup );
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
		// n/a for charybdis
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
	
		ircd_handle::handle_channel_create( $chans, $nusers, $ircdata[2], $mode_queue );
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
		$chans = explode( ',', $ircdata[3] );
	
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
		self::send( ':'.self::$sid.' SVINFO 6 6 0 '.core::$network_time );
	}
	
	/*
	* send_squit
	*
	* @params
	* $server
	*/
	static public function send_squit( $server )
	{
		self::send( ':'.$server.' SQUIT :SQUIT' );
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
			
			self::send( ':'.self::$sid.' PONG '.$ircdata[1], core::$socket );
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
		if ( isset( $ircdata[0] ) && $ircdata[0] == 'CAPAB' )
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
		self::send( 'PASS '.$pass.' TS 6 '.self::$sid );
		self::send( 'CAPAB :QS EX CHW IE KLN KNOCK TB UNKLN CLUSTER ENCAP SERVICES RSFNC SAVE EUID EOPMOD BAN MLOCK' );
		self::send( ':'.self::$sid.' SERVER '.$name.' 0 :'.$desc );
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
	
		self::send( ':'.self::$sid.' 351 '.$nick.' :acora-'.core::$version.' '.core::$config->server->name.' '.core::$config->server->ircd.' booted: '.date( 'F j, Y, g:i a', core::$network_time ).'' );
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
		
		self::send( ':'.self::$sid.' UID '.$nick.' 0 '.core::$network_time.' '.$service_mode.' '.$ident.' '.$hostname.' '.core::$config->conn->vhost.' '.$uid.' :'.$gecos );		
		
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
		
		self::send( ':'.$uid.' QUIT :'.$message );
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
			self::send( ':'.$unick.' WALLOPS :'.$message );
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
	* $boolean - if set to true sid will be sent instead of nick
	*/
	static public function mode( $nick, $chan, $mode, $boolean = false )
	{
		$unick = ircd_handle::get_uid( $nick );
		// get the uid.
		
		if ( $mode[0] != '-' && $mode[0] != '+' ) $mode = '+'.$mode;
		
		$old_mode = $mode;
		$mode = mode::check_modes( $mode );
		// we don't want nobody messing about

		if ( trim( $mode ) == '' )
			return false;
		
		$from = ( $boolean ) ? self::$sid : $unick;
		// check what we send
		
		self::send( ':'.$from.' TMODE '.core::$chans[$chan]['timestamp'].' '.$chan.' '.$mode );
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
		
		self::send( ':'.$unick.' MODE '.$uuser.' :'.$mode );
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
		
		self::send( ':'.$unick.' JOIN '.core::$network_time.' '.$chan.' +' );
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
		
		self::send( ':'.self::$sid.' ENCAP * RSFNC '.$uold_nick.' '.$new_nick.' '.$timestamp.' '.$timestamp );
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
		
		self::send( ':'.$unick.' KILL '.$uuser.'  :Killed ('.$nick.' ('.$message.')))' );
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
		$unick = ircd_handle::get_uid( $nick );
	
		// TODO
		self::send( ':'.$unick.' GLINE '.$mask.' '.$mask.' :'.$message );
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
		self::send( ':'.core::$config->server->name.' SQUIT :'.$message );
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
		
		self::send( ':'.self::$sid.' '.$numeric.' '.$unick.' :'.$message );
		ircd_handle::push( $from, $numeric, $nick, $message );
		// send the cmd then handle it internally
	}
	
	/*
	* end_burst
	*
	* @params
	* void
	*/
	static public function end_burst( $ircdata )
	{
		if ( !core::$end_burst )
			self::ping( $ircdata );
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
	* set_registered_mode
	*
	* @params
	* $nick, $channel
	*/
	static public function set_registered_mode( $nick, $channel )
	{
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
		// TODO
		$uid = ircd_handle::get_uid( $nick );
		self::send( ':'.$uid.' SIGNON '.$nick.' '.core::$nicks[$nick]['ident'].' '.core::$nicks[$nick]['host'].' '.core::$nicks[$nick]['timestamp'].' '.$nick );
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
		self::send( ':'.self::$sid.' SU '.$uid );	
	}

	/*
	* on_capab_start
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_capab_start( $ircdata )
	{
		if ( isset( $ircdata[1] ) && isset( $ircdata[4] ) && $ircdata[1] == 'NOTICE' && core::get_data_after( $ircdata, 4 ) == self::$trick_capab_start )
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
		if ( isset( $ircdata[1] ) && isset( $ircdata[4] ) && $ircdata[1] == 'NOTICE' && core::get_data_after( $ircdata, 4 ) == self::$trick_capab_end )
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
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'TIME' )
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
		if ( isset( $ircdata[0] ) && $ircdata[0] == 'SVINFO' )
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
		if ( !core::$end_burst && isset( $ircdata[0] ) && $ircdata[0] == 'PING' )
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
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'VERSION' )
			self::send_version( $ircdata );
		// handle version
	
		if ( isset( $ircdata[0] ) && ( $ircdata[0] == 'PASS' || $ircdata[0] == 'SERVER' ) )
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
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'SQUIT' )
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
		if ( isset( $ircdata[0] ) && $ircdata[0] == 'PING' )
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
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'EUID' )
		{
			ircd_handle::on_connect( $ircdata[2], ircd_handle::get_server( $ircdata, 0 ) );
			return core::$nicks[$ircdata[2]];
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
			$nick = ircd_handle::get_nick( $ircdata, 0 );
			ircd_handle::on_connect( $nick );
			return $nick;
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
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'CHGHOST' )
		{
			$return = array(
				'nick' => ircd_handle::get_nick( $ircdata, 0 ),
				'new_host' => $ircdata[2],
			);
			
			ircd_handle::on_fhost( $return['nick'], $return['new_host'] );
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
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'SJOIN' )
			return core::get_chan( $ircdata, 3 );
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
			ircd_handle::on_join( ircd_handle::get_nick( $ircdata, 0 ), $ircdata[3] );
			return $ircdata[3];
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
			$return = array(
				'nick' => ircd_handle::get_nick( $ircdata, 0 ),
				'chan' => core::get_chan( $ircdata, 2 ),
			);
		
			ircd_handle::on_part( $return['nick'], $return['chan'] );
			return $return;
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
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'BMASK' )
		{
			$return = array(
				'nick' => ircd_handle::get_nick( $ircdata, 0 ),
				'chan' => core::get_chan( $ircdata, 3 ),
				'modes' => core::get_data_after( $ircdata, 4 ),
				'mode' => $ircdata[4],
				'params' => substr( core::get_data_after( $ircdata, 5 ), 1 ),
				'bmask' => true,
			);
		
			ircd_handle::on_mode( $return['nick'], '+'.$return['mode'].' '.$return['params'], $return['chan'] );
			return $return;
		}
		// listen for BMASK. handle_mode decides what we want to parse!
	
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'TMODE' )
		{
			$return = array(
				'nick' => ircd_handle::get_nick( $ircdata, 0 ),
				'chan' => core::get_chan( $ircdata, 3 ),
				'modes' => core::get_data_after( $ircdata, 4 ),
				'bmask' => false,
			);
		
			ircd_handle::on_mode( $return['nick'], $return['modes'], $return['chan'] );
			return $return;
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
			$return = array(
				'nick' => ircd_handle::get_nick( $ircdata, 0 ),
				'chan' => core::get_chan( $ircdata, 2 ),
				'who' => ircd_handle::get_nick( $ircdata, 3 ),
			);
		
			ircd_handle::on_kick( $return['nick'], $return['who'], $return['chan'] );
			return $return;
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
			$chan = core::get_chan( $ircdata, 2 );
			ircd_handle::on_topic( $chan );
			return $chan;
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
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'TB' )
		{
			$chan = core::get_chan( $ircdata, 2 );
			ircd_handle::on_ftopic( $chan );
			return $chan;
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
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'MODE' && ( substr( $ircdata[3], 1, 1 ) == '+' && strpos( $ircdata[3], 'o' ) !== false ) )
		{
			$return = array(
				'nick' => ircd_handle::get_nick( $ircdata, 0 ),
				'type' => 'Server Administrator',
			);
			
			ircd_handle::on_oper_up( $return['nick'], $return['type'] );
			return $return;
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
		$return = array(
			'nick' => ircd_handle::get_nick( $ircdata, 0 ),
			'msg' => core::get_data_after( $ircdata, 3 ),
		);
		
		if ( $ircdata[2][0] != '#' )
			$return['target'] = ircd_handle::get_nick( $ircdata, 2 );
		else
			$return['target'] = core::get_chan( $ircdata, 2 );
		
		if ( $where != '' )
		{
			if ( isset( $ircdata[1] ) && ( $ircdata[1] == 'PRIVMSG' && $return['target'] == $where ) )
				return $return;
			// return true providing $where matches where it was sent, crafty.
			// clearly doesn't make much sence imo. lol
		}
		else
		{
			if ( isset( $ircdata[1] ) && $ircdata[1] == 'PRIVMSG' )
				return $return;
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
		$return = array(
			'nick' => ircd_handle::get_nick( $ircdata, 0 ),
			'msg' => core::get_data_after( $ircdata, 3 ),
		);
		
		if ( $ircdata[2][0] != '#' )
			$return['target'] = ircd_handle::get_nick( $ircdata, 2 );
		else
			$return['target'] = core::get_chan( $ircdata, 2 );
	
		if ( $where != '' )
		{
			if ( isset( $ircdata[1] ) && $ircdata[1] == 'NOTICE' && $ircdata[2] == $where )
				return $return;
			// return true providing $where matches where it was sent, crafty.
			// clearly doesn't make much sence imo. lol
		}
		else
		{
			if ( isset( $ircdata[1] ) && $ircdata[1] == 'NOTICE' )
				return $return;
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
			$return = array(
				'nick' => ircd_handle::get_nick( $ircdata, 0 ),
				'new_nick' => $ircdata[2],
			);
			
			ircd_handle::on_nick_change( $return['nick'], $return['new_nick'] );
			return $return;
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
		if ( count( $ircdata ) == 7 && $ircdata[1] == 'SIGNON' || count( $ircdata ) == 9 && $ircdata[3] == 'SIGNON' )
		{
			$return = array(
				'nick' => ( count( $ircdata ) == 7 ) ? ircd_handle::get_nick( $ircdata, 0 ) : ircd_handle::get_nick( $ircdata, 4 ),
				'ident' => ( count( $ircdata ) == 7 ) ? $ircdata[3] : $ircdata[5],
			);
		
			ircd_handle::on_ident_change( $return['nick'], $return['ident'] );
			return $return;
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
		if ( count( $ircdata ) == 7 && $ircdata[1] == 'SIGNON' || count( $ircdata ) == 9 && $ircdata[3] == 'SIGNON' )
		{
			$return = array(
				'nick' => ( count( $ircdata ) == 7 ) ? ircd_handle::get_nick( $ircdata, 0 ) : ircd_handle::get_nick( $ircdata, 4 ),
				'gecos' => ( count( $ircdata ) == 7 ) ? substr( core::get_data_after( $ircdata, 7 ), 1 ) : substr( core::get_data_after( $ircdata, 9 ), 1 ),
			);
			
			ircd_handle::on_gecos_change(  $return['nick'], $return['gecos'] );
			return $return;
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