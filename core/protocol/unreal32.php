<?php

/*
* Acora IRC Services
* core/protocol/unreal32.php: Provides support for UnrealIRCd 3.2.x
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

class ircd implements protocol
{

	const MOD_VERSION = '0.0.2';
	const MOD_AUTHOR = 'Acora';
	// module info.

	static public $ircd = 'UnrealIRCd 3.2.x';
	static public $globops = false;
	static public $chghost = false;
	static public $chgident = false;

	static public $restrict_modes = 'bIe';
	static public $status_modes = array();
	static public $owner = true;
	static public $protect = true;
	static public $halfop = false;
	
	static public $modes_params = 'qaohvbIegjfJLlk';
	static public $modes;
	static public $max_params = 6;
	
	static public $jupes = array();
	static public $motd_start = 'Message of the day on {server}';
	static public $motd_end = 'End of {server} message of the day';
	static public $default_c_modes = 'nt';
	
	static public $reg_modes = array(
		'nick'	=>	'r',
		'chan'	=>	'r',
	);
	
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
		modules::init_module( 'unreal32', self::MOD_VERSION, self::MOD_AUTHOR, 'protocol', 'static' );
		// these are standard in module constructors
	}
	
	/*
	* handle_on_server
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_on_server( &$ircdata )
	{
		core::$servers[$ircdata[1]] = array( 'name' => $ircdata[1], 'sid' => $ircdata[4] );
	}
	
	/*
	* handle_on_squit
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_on_squit( &$ircdata )
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
	static public function handle_on_connect( &$ircdata, $startup = false )
	{
		$nick = $ircdata[1];
		$server = $ircdata[6];
		$gecos = core::get_data_after( &$ircdata, 8 );
		// get nick, server, gecos
		
		if ( $gecos[0] == ':' ) $gecos = substr( $gecos, 1 );
		if ( $server[0] == ':' ) $server = substr( $server, 1 );
		// strip the : off the start of the gecos & server.
		
		core::$nicks[$nick] = array(
			'nick' => $nick,
			'ident' => $ircdata[4],
			'host' => $ircdata[5],
			'oldhost' => $ircdata[5],
			'gecos' => $gecos,
			'server' => $server,
			'timestamp' => $ircdata[3],
			'commands' => null,
			'floodcmds' => 0,
			'ignore' => false,
			'failed_attempts' => 0,
			'offences' => 0,
		);
		
		core::$uids[$ircdata[2]] = $nick;
		// yey for this, saves us massive intensive cpu raeps
		// on large networks, uses a little more memory but baah!
		
		if ( core::$config->settings->logconnections && $startup === false )
		{
			core::alog( 'CONNECT: '.$nick.' ('.core::$nicks[$nick]['ident'].'@'.core::$nicks[$nick]['oldhost'].' => '.core::$nicks[$nick]['host'].') ('.core::$nicks[$nick]['gecos'].') connected to the network ('.core::$nicks[$nick]['server'].')' );
		}
		// log
	}
	
	/*
	* handle_nick_change
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_nick_change( &$ircdata, $startup = false )
	{
		$nick = core::get_nick( &$ircdata, 0 );
		$new_nick = $ircdata[2];
		
		if ( isset( core::$nicks[$nick] ) )
		{
			core::$nicks[$new_nick] = core::$nicks[$nick];
			core::$nicks[$new_nick]['nick'] = $new_nick;
			core::$nicks[$new_nick]['onick'] = $nick;
			
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
		{
			core::alog( 'NICK: '.$nick.' ('.core::$nicks[$new_nick]['ident'].'@'.core::$nicks[$new_nick]['oldhost'].' => '.core::$nicks[$new_nick]['host'].') ('.core::$nicks[$new_nick]['gecos'].') changed nick to '.$new_nick.' ('.core::$nicks[$new_nick]['server'].')' );
		}
		// log
	}
	
		
	/*
	* handle_quit
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_quit( &$ircdata, $startup = false )
	{
		$nick = core::get_nick( &$ircdata, 0 );
		// nick
		
		if ( core::$config->settings->logconnections && $startup === false )
		{
			core::alog( 'QUIT: '.$nick.' ('.core::$nicks[$nick]['ident'].'@'.core::$nicks[$nick]['oldhost'].' => '.core::$nicks[$nick]['host'].') ('.core::$nicks[$nick]['gecos'].') left the network ('.core::$nicks[$nick]['server'].')' );
		}
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
	static public function handle_host_change( &$ircdata )
	{
		$nick = core::get_nick( &$ircdata, 0 );
		
		core::$nicks[$nick]['oldhost'] = core::$nicks[$nick]['host'];	
		core::$nicks[$nick]['host'] = $ircdata[2];
	}
	
	/*
	* handle_mode
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_mode( &$ircdata )
	{
		$chan = core::get_chan( &$ircdata, 2 );
		// get the chan
		
		if ( $chan[0] == '#' )
		{
			core::$chans[$chan]['timestamp'] = $ircdata[count( $ircdata )];
			// set the timestamp
			
			if ( preg_match( '~^[1-9][0-9]*$~', $ircdata[count( $ircdata )] ) )
				unset( $ircdata[count( $ircdata )] );
			// unset the timestamp from the array
			
			$mode_queue = core::get_data_after( &$ircdata, 3 );
			$mode_array = mode::sort_modes( $mode_queue );
			
			mode::append_modes( $chan, $mode_array );
			mode::handle_params( $chan, $mode_array );
		}
	}
	
	/*
	* handle_ftopic
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_ftopic( &$ircdata )
	{
		$nick = explode( '!', $ircdata[2] );
		$nick = $nick[0];
		// get the nick
		$chan = core::get_chan( &$ircdata, 1 );
		$topic = trim( substr( core::get_data_after( &$ircdata, 4 ), 1 ) );
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
	static public function handle_topic( &$ircdata )
	{
		$nick = core::get_nick( &$ircdata, 0 );
		$chan = core::get_chan( &$ircdata, 2 );
		$topic = trim( substr( core::get_data_after( &$ircdata, 5 ), 1 ) );
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
	static public function handle_channel_create( &$ircdata )
	{
		$nick = core::get_nick( &$ircdata, 0 );
		$chans = explode( ',', $ircdata[2] );
		
		core::$chans[$chan]['p_modes'] = array();
		
		foreach ( $chans as $chan )
		{
			if ( !isset( core::$chans[$chan]['users'][$nick] ) )
				core::$chans[$chan]['users'][$nick] = '';
			// maintain the logged users array
		}
	}
	
	/*
	* handle_join
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_join( &$ircdata )
	{
		$nick = core::get_nick( &$ircdata, 0 );
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
	static public function handle_part( &$ircdata )
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
	static public function handle_kick( &$ircdata )
	{
		$chan = core::get_chan( &$ircdata, 2 );
		$who = core::get_nick( &$ircdata, 3 );
			
		unset( core::$chans[$chan]['users'][$who] );
		// again, move them out.
	}
	
	/*
	* handle_oper_up
	*
	* @params
	* $ircdata - ..
	*/
	static public function handle_oper_up( &$ircdata )
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
	static public function ping( &$ircdata )
	{
		if ( self::on_ping( &$ircdata ) )
		{
			database::ping();
			// ping the db
			
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
	static public function get_information( &$ircdata )
	{
		if ( isset( $ircdata[0] ) && $ircdata[0] == 'PROTOCTL' )
		{
			$data = explode( '=', $ircdata[13] );
			$data = $data[1];
			$new_mdata = ( isset( $mdata ) ) ? explode( '=', $mdata ) : '';
			$rmodes = '';
			
			if ( strpos( $data, 'h' ) !== false )
			{
				self::$halfop = true;
				self::$status_modes[] .= 'h';
				$rmodes .= 'h';
			}
			// and +h
			
			self::$status_modes[] .= 'q';
			self::$status_modes[] .= 'a';
			$rmodes .= 'q';
			$rmodes .= 'a';
			
			self::$status_modes[] .= 'o';
			self::$status_modes[] .= 'v';
			// we dont check for q/a, cause unreal supports them no matter what
			
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
		self::send( 'PASS :'.$pass );
		self::send( 'PROTOCTL :' );
		self::send( 'SERVER '.$name.' 1 :'.$name.' '.$pass );
		self::send( 'ES' );
		//self::send( ':'.core::$config->server->name.' BURST '.core::$network_time );
		// init the server
		
		//self::send( ':'.core::$config->server->name.' VERSION :' );
		// ooh, version?
		
		core::alog( 'init_server(): '.$name.' introduced :'.$desc, 'BASIC' );
		// log it
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
		core::$times[$nick] = core::$network_time;
		// just so if we do need to change anything, we've still got it.
		
		if ( $enforcer )
		{
			self::send( 'NICK '.$nick.' 1 '.core::$times[$nick].' '.$ident.' '.$hostname.' '.core::$config->server->name.' 0 :'.$gecos );
			self::send( ':'.$nick.' MODE '.$nick.' :'.self::$service_modes['enforcer'] );
			// this just connects a psuedoclient.
		}
		else
		{
			self::send( 'NICK '.$nick.' 1 '.core::$times[$nick].' '.$ident.' '.$hostname.' '.core::$config->server->name.' 0 :'.$gecos );
			self::send( ':'.$nick.' MODE '.$nick.' :'.self::$service_modes['service'] );
			// this just connects a psuedoclient.
		}
		
		core::$nicks[$nick] = array(
			'nick' => $nick,
			'ident' => $ident,
			'host' => $hostname,
			'gecos' => $gecos,
			'ircop' => ( $enforcer ) ? false : true,
			'timestamp' => core::$network_time,
			'server' => core::$config->server->name,
		);
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
		unset( core::$times[$nick] );
		unset( core::$nicks[$nick] );
		// we unset that, just to save memory
		
		self::send( ':'.$nick.' QUIT '.$message );
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
			core::alog( 'globops(): '.$nick.' sent a globops', 'BASIC' );
			// debug info
			
			self::send( ':'.$nick.' GLOBOPS :'.$message );
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
		core::alog( 'global_notice(): sent from '.$nick, 'BASIC' );
		// debug info
		
		foreach ( core::$nicks as $user => $data )
		{
			$hostname = core::get_full_hostname( $user );
			// hostname
			
			if ( $data['server'] != core::$config->server->name && services::match( $hostname, $mask ) )
				services::communicate( $nick, $user, $message );
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
		if ( $mode[0] != '-' && $mode[0] != '+' ) $mode = '+'.$mode;
		
		$mode = mode::check_modes( $mode );
		// we don't want nobody messing about
		
		if ( $mode != '' )
		{
			if ( !isset( core::$chans[$chan]['timestamp'] ) || core::$chans[$chan]['timestamp'] == '' )
				core::$chans[$chan]['timestamp'] = core::$network_time;
			
			self::send( ':'.$nick.' MODE '.$chan.' '.$mode.' '.core::$chans[$chan]['timestamp'] );
			
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
		core::alog( 'umode(): '.$nick.' set '.$mode.' on '.$user, 'BASIC' );
		// debug info
		
		self::send( ':'.$nick.' MODE '.$user.' :'.$mode );
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
		core::$chans[$chan]['users'][$nick] = '';
		// add us to the channel array
		
		core::alog( 'join_chan(): '.$nick.' joined '.$chan, 'BASIC' );
		// debug info
		
		self::send( ':'.$nick.' JOIN '.$chan.' '.core::$network_time );
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
		unset( core::$chans[$chan]['users'][$nick] );
		// remove us from the channel
		
		core::alog( 'part_chan(): '.$nick.' left '.$chan, 'BASIC' );
		// debug info
		
		self::send( ':'.$nick.' PART '.$chan );
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
		core::alog( 'topic(): '.$nick.' set a topic for '.$chan, 'BASIC' );
		// debug info
		
		self::send( ':'.$nick.' TOPIC '.$chan.' :'.$topic );
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
			core::alog( 'kick(): '.$nick.' kicked '.$user.' from '.$chan, 'BASIC' );
			// debug info
			
			self::send( ':'.$nick.' KICK '.$chan.' '.$user.' :'.$reason );
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
			core::alog( 'sethost(): '.$from.' set '.$nick.'\'s host', 'BASIC' );
			// debug info
			
			core::$nicks[$nick]['oldhost'] = core::$nicks[$nick]['host'];
			core::$nicks[$nick]['host'] = $host;
			
			self::send( ':'.$from.' CHGHOST '.$nick.' '.$host );
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
			core::alog( 'setident(): '.$from.' set '.$nick.'\'s ident', 'BASIC' );
			// debug info
			
			core::$nicks[$nick]['ident'] = $ident;
		
			self::send( ':'.$from.' CHGIDENT '.$nick.' '.$ident );
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
		core::alog( 'svsnick(): '.$old_nick.' changed to '.$new_nick, 'BASIC' );
		// debug info
		
		self::send( ':'.core::$config->server->name.' SVSNICK '.$old_nick.' '.$new_nick.' '.$timestamp );
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
		core::alog( 'kill(): '.$nick.' killed '.$user, 'BASIC' );
		// debug info
		
		self::send( ':'.$nick.' KILL '.$user.' :Killed ('.$nick.' ('.$message.')))' );
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
		core::alog( 'push(): '.$from.' pushed text to '.$nick.' on numeric '.$numeric, 'BASIC' );
		// debug info
		$message = implode( ' ', $message );
		// implode the message
		
		self::send( ':'.$from.' PUSH '.$nick.' ::'.$from.' '.$numeric.' '.$nick.' '.$message );
	}
	
	/*
	* end_burst
	*
	* @params
	* void
	*/
	static public function end_burst()
	{
		self::send( ':'.core::$config->server->name.' EOS' );
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
	* on_capab_start
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_capab_start( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'NOTICE' && $ircdata[2] == 'AUTH' )
			return true;
		// on capab start, in our case we use something else
		// because unreal doesn't have this, fucking SUCKY!
		
		return false;
	}

	/*
	* on_capab_end
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_capab_end( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'NOTICE' && $ircdata[2] == 'AUTH' )
			return true;
		// on capab start, in our case we use something else
		// because unreal doesn't have this, fucking SUCKY!
		
		return false;
	}

	/*
	* on_timeset
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_timeset( &$ircdata )
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
	static public function on_start_burst( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'PROTOCTL' )
			return true;
		
		return false;
	}
	
	/*
	* on_end_burst
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_end_burst( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'EOS' )
			return true;
		
		return false;
	}
	
	/*
	* on_server
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_server( &$ircdata )
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
	static public function on_squit( &$ircdata )
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
	static public function on_ping( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[0] == 'PING' )
			return true;
		
		return false;
	}
	
	/*
	* on_connect
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_connect( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[0] == 'NICK' && count( $ircdata ) > 4 )
		{
			core::alog( 'on_connect(): '.$ircdata[3].' connected to '.$ircdata[0], 'BASIC' );
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
	static public function on_quit( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'QUIT' )
		{
			core::alog( 'on_quit(): '.$ircdata[0].' quit', 'BASIC' );
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
	static public function on_fhost( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'FHOST' )
		{
			core::alog( 'on_fhost(): '.$ircdata[0].'\'s host changed to '.$ircdata[2], 'BASIC' );
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
	static public function on_chan_create( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'JOIN' && count( core::$chans[$chan]['users'] ) == 0 )
		{
			core::alog( 'on_join(): '.$ircdata[0].' joined '.$ircdata[2], 'BASIC' );
			// i added this to make debbuing a bit more useful.
			
			return true;
		}
		// return true when any channel is joined, because $chan isnt set.
		
		return false;
	}
	
	/*
	* on_join
	*
	* @params
	* $ircdata - ..
	*/
	static public function on_join( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'JOIN' && count( core::$chans[$chan]['users'] ) > 0 )
		{
			core::alog( 'on_join(): '.$ircdata[0].' joined '.$ircdata[2], 'BASIC' );
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
	static public function on_part( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'PART' )
		{
			core::alog( 'on_part(): '.$ircdata[0].' left '.$ircdata[2], 'BASIC' );
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
	static public function on_mode( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'MODE' )
		{
			core::alog( 'on_mode(): '.$ircdata[0].' set '.core::get_data_after( $ircdata, 4 ).' on '.$ircdata[2], 'BASIC' );
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
	static public function on_kick( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'KICK' )
		{
			core::alog( 'on_kick(): '.$ircdata[0].' kicked '.$ircdata[3].' from '.$ircdata[2], 'BASIC' );
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
	static public function on_topic( &$ircdata )
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
	static public function on_ftopic( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[0] == 'TOPIC' )
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
	static public function on_oper_up( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'OPERTYPE' )
		{
			core::alog( 'on_oper_up(): '.$ircdata[0].' opered up to '.str_replace( '_', ' ', $ircdata[2] ), 'BASIC' );
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
	static public function on_msg( &$ircdata, $where = '' )
	{
		if ( $where != '' )
		{
			if ( isset( $ircdata[1] ) && ( $ircdata[1] == 'PRIVMSG' && strtolower( $ircdata[2] ) == strtolower( $where ) ) )
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
	static public function on_notice( &$ircdata, $where = '' )
	{
		if ( $where != '' )
		{
			if ( isset( $ircdata[1] ) && $ircdata[1] == 'NOTICE' && strtolower( $ircdata[2] ) == strtolower( $where ) )
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
	static public function on_nick_change( &$ircdata )
	{
		if ( isset( $ircdata[1] ) && $ircdata[1] == 'NICK' && count( $ircdata ) == 4 )
		{
			core::alog( 'on_nick_change(): '.$ircdata[0].' changed nick to '.$ircdata[2], 'BASIC' );
			// debug info
			
			return true;
		}
		// return true on any nick change.
		
		return false;
	}
	
	/*
	* get_nick
	*
	* @params
	* $ircdata - ..
	* $number - ..
	*/
	static public function get_nick( &$ircdata, $number )
	{
		$nick = $ircdata[$number];
		$nick = trim( $nick );
		$nick = str_replace( ':', '', $nick ); // just incase.
		// do some splitting and shitting, erm.. -shitting.
		
		return $nick;
	}
	
	/*
	* parse_users
	*
	* @params
	* $ircdata - ..
	*/
	static public function parse_users( $chan, &$ircdata, $number )
	{
		return core::$chans[$chan]['users'];
	}
}
// EOF;