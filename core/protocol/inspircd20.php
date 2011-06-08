<?php

/*
* Acora IRC Services
* core/protocol/inspircd20.php: Provides support for InspIRCd 2.0 (EXPERIMENTAL)
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

	const MOD_VERSION = '0.0.2';
	const MOD_AUTHOR = 'Acora';
	// module info.

	static public $ircd = 'InspIRCd 2.0';
	static public $globops = false;
	static public $chghost = false;
	static public $chgident = false;
	static public $sid;

	static public $restrict_modes = 'bIe';
	static public $status_modes = array();
	static public $owner = false;
	static public $protect = false;
	static public $halfop = false;
	
	static public $modes_params = 'qaohvbIegjfJLlk';
	static public $modes_p_unrequired = 'l';
	static public $modes;
	static public $max_params = 6;
	
	static public $jupes = array();
	static public $motd_start = '- {server} message of the day';
	static public $motd_end = 'End of message of the day.';
	static public $default_c_modes = 'nt';
	
	static public $prefix_modes = array(
		'q'	=>	'~',
		'a'	=>	'&',
		'o'	=>	'@',
		'h'	=>	'%',
		'v'	=>	'+',
	);
	
	static public $service_modes = array(
		'enforcer' 	=> '+i',
		'service'	=> '+io',
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
		modules::init_module( 'inspircd20', self::MOD_VERSION, self::MOD_AUTHOR, 'protocol', 'static' );
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
		core::$servers[$ircdata[1]] = array( 'name' => $ircdata[1], 'sid' => $ircdata[4] );
		// init the server
		
		if ( !core::$end_burst )
			self::send( ':'.self::$sid.' BURST '.core::$network_time );			
		
		core::$pullout = true;
		// MUST MUST MUST be true, otherwise a whole world of problems occur (trust me!)
	}
	
	/*
	* handle_on_squit
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_on_squit( $ircdata )
	{
		$server = str_replace( ':', '', $ircdata[2] );
		
		unset( core::$servers[$server] );
		
		if ( in_array( $server, self::$jupes ) )
		{
			self::send( ':'.core::$config->server->name.' SQUIT '.$server.' :SQUIT' );
			unset( self::$jupes[$server] );
		}
		// if it's one of our servers we act upon the command! :o
		// need to revise this, can't remember the protocol stuff for insp 1.2
		// will have to look into it.
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
		$server = self::get_server( $ircdata, 0 );
		$gecos = core::get_data_after( $ircdata, 11 );
		// get nick, server, gecos
		
		if ( $nick[0] == ':' ) $nick = substr( $nick, 1 );
		if ( $gecos[0] == ':' ) $gecos = substr( $gecos, 1 );
		if ( $server[0] == ':' ) $server = substr( $server, 1 );
		// strip :
		
		core::$nicks[$nick] = array(
			'nick' => $nick,
			'uid' => $ircdata[2],
			'ident' => $ircdata[7],
			'host' => $ircdata[6],
			'oldhost' => $ircdata[5],
			'gecos' => $gecos,
			'server' => $server,
			'timestamp' => $ircdata[3],
			'commands' => null,
			'floodcmds' => 0,
			'failed_attempts' => 0,
			'offences' => 0,
		);
		
		core::$uids[$ircdata[2]] = $nick;
		// yey for this, saves us massive intensive cpu raeps
		// on large networks, uses a little more memory but baah!
		
		if ( core::$config->settings->logconnections && $startup === false )
			core::alog( 'CONNECT: '.$nick.' ('.core::$nicks[$nick]['ident'].'@'.core::$nicks[$nick]['oldhost'].' => '.core::$nicks[$nick]['host'].') ('.core::$nicks[$nick]['gecos'].') connected to the network ('.core::$nicks[$nick]['server'].')' );
		// log
	}
	
	/*
	* handle_nick_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_nick_change( $ircdata, $startup = false )
	{
		$uuid = str_replace( ':', '', $ircdata[0] );
		$nick = core::get_nick( $ircdata, 0 );
		$new_nick = $ircdata[2];
		
		if ( $nick[0] == ':' ) $nick = substr( $nick, 1 );
		if ( $new_nick[0] == ':' ) $new_nick = substr( $new_nick, 1 );
		// strip :
		
		if ( isset( core::$nicks[$nick] ) )
		{
			core::$nicks[$new_nick] = core::$nicks[$nick];
			core::$nicks[$new_nick]['nick'] = $new_nick;
			core::$nicks[$new_nick]['onick'] = $nick;
			core::$uids[$uuid] = $new_nick;
			
			unset( core::$nicks[$nick] );
			// change the nick records
			
			foreach ( core::$chans as $chan => $data )
			{
				if ( !isset( $data['users'][$nick] ) )
					continue;
				// skip to next iteration
				
				core::$chans[$chan]['users'][$new_nick] = $data['users'][$nick];
				unset( core::$chans[$chan]['users'][$nick] );
			}
			// check if they are in any channels, change their nick if they are.
		}
		
		if ( core::$config->settings->logconnections && $startup === false )
			core::alog( 'NICK: '.$nick.' ('.core::$nicks[$new_nick]['ident'].'@'.core::$nicks[$new_nick]['oldhost'].' => '.core::$nicks[$new_nick]['host'].') ('.core::$nicks[$new_nick]['gecos'].') changed nick to '.$new_nick.' ('.core::$nicks[$new_nick]['server'].')' );
		// log
	}
	
		
	/*
	* handle_quit
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_quit( $ircdata, $startup = false )
	{
		$nick = core::get_nick( $ircdata, 0 );
		// nick
		
		if ( $nick[0] == ':' ) $nick = substr( $nick, 1 );
		// strip :
		
		if ( core::$config->settings->logconnections && $startup === false )
			core::alog( 'QUIT: '.$nick.' ('.core::$nicks[$nick]['ident'].'@'.core::$nicks[$nick]['oldhost'].' => '.core::$nicks[$nick]['host'].') ('.core::$nicks[$nick]['gecos'].') left the network ('.core::$nicks[$nick]['server'].')' );
		// log
		
		$uid = str_replace( ':', '', $ircdata[0] );
		
		unset( core::$nicks[$nick] );
		unset( core::$uids[$uid] );
		// remove a user if they've quit..
				
		foreach ( core::$chans as $chan => $data )
		{
			if ( isset( core::$chans[$chan]['users'][$nick] ) )
				unset( core::$chans[$chan]['users'][$nick] );
			else
				continue;
		}
	}
	
	/*
	* handle_host_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_host_change( $ircdata )
	{
		$nick = core::get_nick( $ircdata, 0 );
		
		core::$nicks[$nick]['oldhost'] = core::$nicks[$nick]['host'];	
		core::$nicks[$nick]['host'] = $ircdata[2];
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
			$nick = self::get_nick( $ircdata, 2 );
			$ident = substr( $ircdata[3], 1 );
		}
		elseif ( $ircdata[1] == 'SETIDENT' )
		{
			$nick = self::get_nick( $ircdata, 0 );
			$ident = substr( $ircdata[2], 1 );
		}
		
		core::$nicks[$nick]['ident'] = $ident;
	}
	
	/*
	* handle_gecos_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_gecos_change( $ircdata )
	{
		$nick = self::get_nick( $ircdata, 0 );
		$gecos = core::get_data_after( $ircdata, 2 );
		
		core::$nicks[$nick]['gecos'] = substr( $gecos, 1 );
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
		
		$mode_array = mode::sort_modes( $mode_queue );
		mode::append_modes( $chan, $mode_array );
		mode::handle_params( $chan, $mode_array );
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
			
		core::$chans[$chan]['topic'] = $topic;
		core::$chans[$chan]['topic_setter'] = $nick;
	}
	
	/*
	* handle_topic
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_topic( $ircdata )
	{
		$nick = core::get_nick( $ircdata, 0 );
		$chan = core::get_chan( $ircdata, 2 );
		$topic = trim( substr( core::get_data_after( $ircdata, 3 ), 1 ) );
		// grab the topic
			
		core::$chans[$chan]['topic'] = $topic;
		core::$chans[$chan]['topic_setter'] = $nick;
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
		
		foreach ( $chans as $chan )
		{
			$nusers_str = implode( ' ', $ircdata );
			$nusers_str = explode( ':', $nusers_str );
			// right here we need to find out where the thing is, because
			// of the way 1.2 handles FJOINs
			$nusers = self::parse_users( $chan, $nusers_str, 1 );
			
			core::$chans[$chan]['timestamp'] = $ircdata[3];
			core::$chans[$chan]['p_modes'] = array();
			
			if ( is_array( core::$chans[$chan]['users'] ) )
				core::$chans[$chan]['users'] = array_merge( $nusers, core::$chans[$chan]['users'] );
			else
				core::$chans[$chan]['users'] = $nusers;
			// basically check if we already have an array, because FJOIN can happen on
			// existing channels, idk why, maybe on bursts etc?
		}
	}
	
	/*
	* handle_join
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_join( $ircdata )
	{
		$nick = core::get_nick( $ircdata, 0 );
		$chans = explode( ',', $ircdata[2] );
		
		foreach ( $chans as $chan )
		{
			if ( !isset( core::$chans[$chan]['users'][$nick] ) )
				core::$chans[$chan]['users'][$nick] = '';
			// maintain the logged users array
		}
	}

	/*
	* handle_part
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_part( $ircdata )
	{
		$nick = core::get_nick( $ircdata, 0 );
		$chan = core::get_chan( $ircdata, 2 );
			
		unset( core::$chans[$chan]['users'][$nick] );
		// remove the user out of the array
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
		$who = core::get_nick( $ircdata, 3 );
			
		unset( core::$chans[$chan]['users'][$who] );
		// again, move them out.
	}
	
	/*
	* handle_oper_up
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_oper_up( $ircdata )
	{
		$nick = core::get_nick( $ircdata, 0 );
		
		core::$nicks[$nick]['ircop'] = true;
	}

	/*
	* ircd functions
	*
	* our functions like core::kick, grabbed from the ircd protocol class.
	*/

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
			database::ping();
			// ping the db
			
			self::send( 'PONG '. $ircdata[2], core::$socket );	
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
			if ( strpos( $ircdata[2], 'm_services_account.so' ) === false )
				timer::add( array( 'core', 'check_services', array() ), 1, 1 );
			else
				core::$services_account = true;
			// we have services_account
			
			if ( strpos( $ircdata[2], 'm_globops.so' ) !== false )
				self::$globops = true;
			// we have globops!
			
			if ( strpos( $ircdata[2], 'm_chghost.so' ) !== false )
				self::$chghost = true;
			// we have chghost
			
			if ( strpos( $ircdata[2], 'm_chgident.so' ) !== false )
				self::$chgident = true;
			// and chgident
		}
		// only trigger when our modules info is coming through
		
		if ( isset( $ircdata[0] ) && $ircdata[0] == 'CAPAB' && $ircdata[1] == 'CAPABILITIES' )
		{
			$data = explode( '=', $ircdata[16] );
			$data = $data[1];
			$new_mdata = ( isset( $mdata ) ) ? explode( '=', $mdata ) : '';
			$rmodes = '';
						
			if ( strpos( $data, 'q' ) !== false )
			{
				self::$owner = true;
				self::$status_modes[] .= 'q';
				$rmodes .= 'q';
			}
			// check if +q is there
			
			if ( strpos( $data, 'a' ) !== false )
			{
				self::$protect = true;
				self::$status_modes[] .= 'a';
				$rmodes .= 'a';
			}
			// and +a
			
			$hdata = implode( ' ', $ircdata );
			
			if ( strpos( $hdata, 'HALFOP=1' ) !== false )
			{
				self::$halfop = true;
				self::$status_modes[] .= 'h';
				$rmodes .= 'h';
			}
			// we check halfop differently
			
			self::$status_modes[] .= 'o';
			self::$status_modes[] .= 'v';
			
			$modes = str_replace( ',', '', $data );
			self::$modes = $rmodes.$modes.'ov';
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
		self::$sid = $numeric;
		self::send( 'SERVER '.$name.' '.$pass.' 0 '.self::$sid.' :'.$desc );
		
		core::alog( 'init_server(): '.$name.' introduced :'.$desc, 'BASIC' );
		// log it
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
		self::send( ':'.self::$sid.' VERSION :acora-'.$version.' '.$name.' '.$ircd.' booted: '.date( 'F j, Y, g:i a', core::$network_time ).'' );
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
		$uid = self::$sid.'AAAA'.chr( rand( ord( 'A' ), ord( 'Z' ) ) ).chr( rand( ord( 'A' ), ord( 'Z' ) ) );
		// produce our random UUID.
		core::$times[$nick] = core::$network_time;
		// just so if we do need to change anything, we've still got it.
		
		if ( $enforcer )
		{
			self::send( ':'.self::$sid.' UID '.$uid.' '.core::$times[$nick].' '.$nick.' '.$hostname.' '.$hostname.' '.$ident.' '.core::$config->conn->vhost.' '.core::$network_time.' '.self::$service_modes['enforcer'].' :'.$gecos );
			// this just connects a psuedoclient.
		}
		else
		{
			self::send( ':'.self::$sid.' UID '.$uid.' '.core::$times[$nick].' '.$nick.' '.$hostname.' '.$hostname.' '.$ident.' '.core::$config->conn->vhost.' '.core::$network_time.' '.self::$service_modes['service'].' :'.$gecos );
			// this just connects a psuedoclient.
			self::send( ':'.$uid.' OPERTYPE Service' );
			// set opertype by default	
		}
		
		core::$nicks[$nick] = array(
			'nick' => $nick,
			'uid' => $uid,
			'ident' => $ident,
			'host' => $hostname,
			'gecos' => $gecos,
			'ircop' => ( $enforcer ) ? false : true,
			'timestamp' => core::$network_time,
			'server' => core::$config->server->name,
		);
		// add it to the array.
		core::$uids[$uid] = $nick;
		// add it to the array.
		
		core::alog( 'introduce_client(): introduced '.$nick.'!'.$ident.'@'.$hostname, 'BASIC' );
		// debug
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
		$uid = self::get_uid( $nick );
		// get the uid.
		
		unset( core::$times[$nick] );
		unset( core::$nicks[$nick] );
		unset( core::$uids[$uid] );
		// we unset that, just to save memory
		
		self::send( ':'.$uid.' QUIT '.$message );
		// as simple as.
		
		core::alog( 'remove_client(): removed '.$nick, 'BASIC' );
		// debug
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
			$unick = self::get_uid( $nick );
			// get the uid.
			
			core::alog( 'globops(): '.$nick.' sent a globops', 'BASIC' );
			// debug info
			
			self::send( ':'.$unick.' GLOBOPS :'.$message );
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
		$unick = self::get_uid( $nick );
		// get the uid.
		
		core::alog( 'global_notice(): sent from '.$nick, 'BASIC' );
		// debug info
		
		foreach ( core::$nicks as $user => $data )
		{
			$uuser = self::get_uid( $user );
			// get the uid.
			
			$hostname = core::get_full_hostname( $user );
			// hostname
			
			if ( $data['server'] != core::$config->server_name && services::match( $hostname, $mask ) )
				self::notice( $unick, $uuser, $message );
		}
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
		$nick = self::get_uid( $nick );
		if ( $what[0] != '#' ) $what = self::get_uid( $what );
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
		$nick = self::get_uid( $nick );
		if ( $what[0] != '#' ) $what = self::get_uid( $what );
		// get the uid.
		
		self::send( ':'.$nick.' PRIVMSG '.$what.' :'.$message );
	}
	
	/*
	* chan
	*
	* @params
	* $nick - who to send it from
	* $chan - the channel to use
	* $mode - mode to set
	*/
	static public function mode( $nick, $chan, $mode )
	{
		$unick = self::get_uid( $nick );
		// get the uid.
		
		if ( $mode[0] != '-' && $mode[0] != '+' ) $mode = '+'.$mode;

		echo "We are in function mode before something, mode = ".$mode." nick = ".$nick."\r\n";
		$mode = mode::check_modes( $mode );
		

		echo "We are in function mode after something, mode = ".$mode." nick = ".$nick."\r\n";
		// we don't want nobody messing about

		if ( $mode != '' )
		{
			if ( !isset( core::$chans[$chan]['timestamp'] ) || core::$chans[$chan]['timestamp'] == '' )
				core::$chans[$chan]['timestamp'] = core::$network_time;
			
			self::send( ':'.$unick.' FMODE '.$chan.' '.core::$chans[$chan]['timestamp'].' '.$mode );
	
				
			$mode_array = mode::sort_modes( $mode );
			mode::append_modes( $chan, $mode_array );
			mode::handle_params( $chan, $mode_array );
		}
		// we only send it if the mode actually has anything in it.

		core::alog( 'mode(): '.$nick.' set '.$mode.' on '.$chan, 'BASIC' );
		// debug info
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
		$unick = self::get_uid( $nick );
		$uuser = self::get_uid( $user );
		// get the uid.
		
		core::alog( 'umode(): '.$nick.' set '.$mode.' on '.$user, 'BASIC' );
		// debug info
		
		self::send( ':'.$unick.' SVSMODE '.$uuser.' '.$mode );
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
		$unick = self::get_uid( $nick );
		// get the uid.
		
		core::$chans[$chan]['users'][$nick] = '';
		// add us to the channel array
		
		core::alog( 'join_chan(): '.$nick.' joined '.$chan, 'BASIC' );
		// debug info
		
		self::send( ':'.$unick.' JOIN '.$chan.' '.core::$network_time );
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
		$unick = self::get_uid( $nick );
		// get the uid.
		
		unset( core::$chans[$chan]['users'][$nick] );
		// remove us from the channel
		
		core::alog( 'part_chan(): '.$nick.' left '.$chan, 'BASIC' );
		// debug info
		
		self::send( ':'.$unick.' PART '.$chan );
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
		$unick = self::get_uid( $nick );
		// get the uid.
		
		core::alog( 'topic(): '.$nick.' set a topic for '.$chan, 'BASIC' );
		// debug info
		
		self::send( ':'.$unick.' TOPIC '.$chan.' :'.$topic );
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
			$unick = self::get_uid( $nick );
			$uuser = self::get_uid( $user );
			// get the uid.
			
			core::alog( 'kick(): '.$nick.' kicked '.$user.' from '.$chan, 'BASIC' );
			// debug info
			
			self::send( ':'.$unick.' KICK '.$chan.' '.$uuser.' :'.$reason );
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
			$ufrom = self::get_uid( $from );
			$unick = self::get_uid( $nick );
			// get the uid.
			
			core::alog( 'sethost(): '.$from.' set '.$nick.'\'s host', 'BASIC' );
			// debug info
			
			core::$nicks[$nick]['oldhost'] = core::$nicks[$nick]['host'];
			core::$nicks[$nick]['host'] = $host;
			
			self::send( ':'.$ufrom.' CHGHOST '.$unick.' '.$host );
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
			$ufrom = self::get_uid( $from );
			$unick = self::get_uid( $nick );
			// get the uid.
			
			core::alog( 'setident(): '.$from.' set '.$nick.'\'s ident', 'BASIC' );
			// debug info
			
			core::$nicks[$nick]['ident'] = $ident;
		
			self::send( ':'.$ufrom.' CHGIDENT '.$unick.' '.$ident );
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
		$uold_nick = self::get_uid( $old_nick );
		$unew_nick = self::get_uid( $new_nick );
		// get the uid.
		
		core::alog( 'svsnick(): '.$old_nick.' changed to '.$new_nick, 'BASIC' );
		// debug info
		
		self::send( ':'.self::$sid.' SVSNICK '.$uold_nick.' '.$unew_nick.' '.$timestamp );
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
		$unick = self::get_uid( $nick );
		$uuser = self::get_uid( $user );
		// get the uid.
		
		core::alog( 'kill(): '.$nick.' killed '.$user, 'BASIC' );
		// debug info
		
		self::send( ':'.$unick.' KILL '.$uuser.' :Killed ('.$nick.' ('.$message.')))' );
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
		core::alog( 'gline(): '.$nick.' glined '.$mask, 'BASIC' );
		// debug info
		
		self::send( ':'.core::$config->server->name.' ADDLINE G '.$mask.' '.$nick.' '.core::$network_time.' '.$duration.' :'.$message );
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
		core::alog( 'shutdown(): '.$message, 'BASIC' );
		// debug info
		
		self::send( ':'.core::$config->server->name.' SQUIT '.core::$config->server->name.' :'.$message );
		
		if ( $terminate ) exit;
		// if true, exit;
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
		$unick = self::get_uid( $nick );
		$ufrom = self::get_uid( $from );
		// get the uid.
		
		core::alog( 'push(): '.$from.' pushed text to '.$nick.' on numeric '.$numeric, 'BASIC' );
		// debug info
		$message = implode( ' ', $message );
		// implode the message
		
		self::send( ':'.$ufrom.' PUSH '.$unick.' ::'.$ufrom.' '.$numeric.' '.$unick.' '.$message );
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
		fputs( core::$socket, $command."\r\n", strlen( $command."\r\n" ) );
		// fairly simple, hopefully.
		
		core::$outgoing = core::$outgoing + strlen( $command."\r\n" );
		// add to the outgoing counter.
		
		core::alog( 'send(): '.$command, 'SERVER' );
		// log SERVER
		
		core::$lines_sent++;
		// ++ lines sent
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
		self::send( ':'.self::$sid.' METADATA '.$nick.' accountname :'.$nick );
	}
	
	/*
	* on_user_logout
	*
	* @params
	* $nick - nick
	*/
	static public function on_user_logout( $nick )
	{
		self::send( ':'.self::$sid.' METADATA '.$nick.' accountname :' );	
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
		{
			self::send( 'CAPAB START 1202' );
			self::send( 'CAPAB CAPABILITIES :PROTOCOL=1202' );
			return true;
		}
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
		{
			self::send( 'CAPAB END' );
			core::alog( 'on_capab_end(): finished' );
			return true;
		}
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
			core::alog( 'on_timeset(): force timechange to '.$ircdata[2], 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
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
			core::alog( 'on_connect(): '.$ircdata[4].' connected to '.self::get_server( $ircdata, 0 ), 'BASIC' );
			// i added this to make debbuing a bit more useful.

			return true;
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
			core::alog( 'on_quit(): '.self::get_nick( $ircdata, 0 ).' quit', 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
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
			core::alog( 'on_fhost(): '.self::get_nick( $ircdata, 0 ).'\'s host changed to '.$ircdata[2], 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
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
		{
			core::alog( 'on_chan_create(): '.$ircdata[2].' created', 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
			return true;
		}
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
			core::alog( 'on_join(): '.self::get_nick( $ircdata, 0 ).' joined '.$ircdata[2], 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
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
			core::alog( 'on_part(): '.self::get_nick( $ircdata, 0 ).' left '.$ircdata[2], 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
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
			core::alog( 'on_mode(): '.self::get_nick( $ircdata, 0 ).' set '.core::get_data_after( $ircdata, 4 ).' on '.$ircdata[2], 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
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
			core::alog( 'on_kick(): '.self::get_nick( $ircdata, 0 ).' kicked '.self::get_nick( $ircdata, 3 ).' from '.$ircdata[2], 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
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
			core::alog( 'on_ftopic(): topic for '.$ircdata[2].' changed', 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
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
			core::alog( 'on_ftopic(): topic for '.$ircdata[2].' changed', 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
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
			core::alog( 'on_oper_up(): '.self::get_nick( $ircdata, 0 ).' opered up to '.str_replace( '_', ' ', $ircdata[2] ), 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
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
			if ( $where[0] != '#' ) $where = self::get_uid( $where );
			
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
			if ( $where[0] != '#' ) $where = self::get_uid( $where );
			
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
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'NICK' && count( $ircdata ) == 3 )
		{
			core::alog( 'on_nick_change(): '.self::get_nick( $ircdata, 0 ).' changed nick to '.$ircdata[2], 'BASIC' );
			// debug info
			
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
			core::alog( 'on_ident_change(): '.self::get_nick( $ircdata, 2 ).' changed ident to '.substr( $ircdata[3], 1 ), 'BASIC' );
			// debug info
			
			return true;
		}
		// return true on chgident.
		
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'SETIDENT' )
		{
			core::alog( 'on_ident_change(): '.self::get_nick( $ircdata, 0 ).' changed ident to '.substr( $ircdata[2], 1 ), 'BASIC' );
			// debug info
			
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
			core::alog( 'on_gecos_change(): '.self::get_nick( $ircdata, 0 ).' changed gecos to '.substr( $ircdata[2], 1 ), 'BASIC' );
			// debug info
			
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
		$sid = str_replace( ':', '', $ircdata[$number] );
		
		foreach ( core::$servers as $server => $info )
		{
			if ( $info['sid'] == $sid )
				return $server;
			// had to make a function for this.
		}
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
		$uuid = str_replace( ':', '', $ircdata[$number] );
		
		if ( is_numeric( $uuid[0] ) )
			return core::$uids[$uuid];
		else
			return $uuid;
		// we always display the uid nick here, ALWAYS
		// therefore when we need the uid, we'll change the
		// nick to a uid.	
	}
	
	/*
	* get_uid
	*
	* @params
	* $nick - should be a valid nickname
	*/
	static public function get_uid( $nick )
	{
		$uuid = core::$nicks[$nick]['uid'];
		
		return $uuid;
	}
	
	/*
	* parse_users
	*
	* @params
	* $ircdata - ..
	*/
	static public function parse_users( $chan, $ircdata, $number )
	{
		$users = core::get_data_after( $ircdata, $number );
		$users = explode( ' ', $users );
		
		foreach ( $users as $user )
		{
			if ( $user != null || $user != ' ' )
			{
				$prenick = explode( ',', $user );
				$nick = trim( self::get_nick( $prenick, 1 ) );
				
				if ( $nick != null ) $nusers[$nick] = $prenick[0];
			}
		}
		
		return $nusers;
	}
}
// EOF;