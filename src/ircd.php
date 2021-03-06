<?php

/*
* Acora IRC Services
* src/ircd.php: IRCD interface
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

class ircd_handle
{
	
	static public $commands = array();
	
	/*
	* handle_on_server
	*
	* @params
	* $server - server name
	* $sid - server sid (n/a in some circumstances, no biggie)
	* $our_sid - also n/a in some circumstances
	*/
	static public function add_command( $command, $method )
	{
		self::$commands[$command] = $method;
	}
	
	/*
	* handle_on_server
	*
	* @params
	* $server - server name
	* $sid - server sid (n/a in some circumstances, no biggie)
	* $our_sid - also n/a in some circumstances
	*/
	static public function handle_on_server( $server, $sid, $our_sid )
	{
		core::$servers[$server] = array( 'name' => $server, 'sid' => $sid );
		
		if ( !core::$end_burst )
			ircd::send_burst( $our_sid );
	}
	
	/*
	* handle_on_squit
	*
	* @params
	* $server - server
	*/
	static public function handle_on_squit( $server )
	{
		$server = self::get_server( array( $server ), 0 );
		unset( core::$servers[$server] );
		
		if ( in_array( $server, ircd::$jupes ) )
		{
			ircd::send_squit( $server );
			unset( ircd::$jupes[$server] );
		}
		// if it's one of our servers we act upon the command! :o
		// need to revise this, can't remember the protocol stuff for insp 1.2
		// will have to look into it.
		
		while ( list( $nick, $data ) = each( core::$nicks ) )
		{
			if ( $data['server'] == $server )
				unset( core::$nicks[$nick] );
		}
		reset( core::$nicks );
		// unset all nicks that were connected to $server
	}
	
	/*
	* handle_on_connect
	*
	* @params
	* $nick, $uid, $ident, $host, $oldhost, $gecos, $ip_addr, $server, $timestamp, $modes, $startup
	*/
	static public function handle_on_connect( $nick, $uid, $ident, $host, $oldhost, $gecos, $ip_addr, $server, $timestamp, $modes, $startup = false )
	{
		core::$uids[$uid] = $nick;
		// yey for this, saves us massive intensive cpu raeps
		// on large networks, uses a little more memory but baah!
		
		core::$ips[$ip_addr] += 1;
		// add an ip_address array
		
		core::$nicks[$nick] = array(
			'nick' => $nick,
			'uid' => $uid,
			'ident' => $ident,
			'host' => $host,
			'oldhost' => $oldhost,
			'gecos' => $gecos,
			'ip_address' => $ip_addr,
			'server' => $server,
			'timestamp' => $timestamp,
			'identified' => false,
			'ircop' => ( strpos( $modes, 'o' ) === false ) ? false : true,
			'commands' => null,
			'floodcmds' => 0,
			'failed_attempts' => 0,
			'offences' => 0,
		);
		// add the array eh
		
		$method = 'on_burst_connect';
		if ( !$startup )
		{
			if ( core::$config->settings->logconnections )
				core::alog( 'CONNECT: '.$nick.' ('.core::$nicks[$nick]['ident'].'@'.core::$nicks[$nick]['oldhost'].' => '.core::$nicks[$nick]['host'].') ('.core::$nicks[$nick]['gecos'].') connected to the network ('.core::$nicks[$nick]['server'].')' );
			core::alog( 'on_connect(): '.$nick.' connected to '.$server, 'MISC' );
			core::max_users();
			$method = 'on_connect';
		}
		// log and stuff
		
		foreach ( modules::$event_methods[$method] as $l => $class )
			$class::$method( core::$nicks[$nick] );
		// call the event method
	}
	
	/*
	* handle_nick_change
	*
	* @params
	* $nick, $new_nick, $timestamp, $startup
	*/
	static public function handle_nick_change( $nick, $new_nick, $timestamp, $startup = false )
	{
		$uuid = self::get_uid( $nick );
		
		if ( $new_nick == '' || $nick == $new_nick )
			return false;
		// fixing this function being called twice, not sure why thats happening..
		
		if ( isset( core::$nicks[$nick] ) )
		{
			core::$nicks[$new_nick] = core::$nicks[$nick];
			unset( core::$nicks[$nick] );
			// change the nick records
			
			core::$nicks[$new_nick]['nick'] = $new_nick;
			core::$nicks[$new_nick]['onick'] = $nick;
			core::$nicks[$new_nick]['timestamp'] = $timestamp;
			core::$uids[$uuid] = $new_nick;
			
			while ( list( $chan, $data ) = each( core::$chans ) )
			{
				if ( !isset( $data['users'][$nick] ) )
					continue;
				// skip to next iteration
				
				core::$chans[$chan]['users'][$new_nick] = $data['users'][$nick];
				unset( core::$chans[$chan]['users'][$nick] );
			}
			reset( core::$chans );
			// check if they are in any channels, change their nick if they are.
		}
		
		if ( core::$config->settings->logconnections && $startup === false )
			core::alog( 'NICK: '.$nick.' ('.core::$nicks[$new_nick]['ident'].'@'.core::$nicks[$new_nick]['oldhost'].' => '.core::$nicks[$new_nick]['host'].') ('.core::$nicks[$new_nick]['gecos'].') changed nick to '.$new_nick.' ('.core::$nicks[$new_nick]['server'].')' );
		// log
		
		core::alog( 'on_nick_change(): '.$nick.' changed nick to '.$new_nick, 'MISC' );
		
		foreach ( modules::$event_methods['on_nick_change'] as $l => $class )
			$class::on_nick_change( $nick, $new_nick );
		// call the event method
	}
	
		
	/*
	* handle_quit
	*
	* @params
	* $nick, $startup
	*/
	static public function handle_quit( $nick, $startup = false )
	{
		if ( core::$config->settings->logconnections && $startup === false )
			core::alog( 'QUIT: '.$nick.' ('.core::$nicks[$nick]['ident'].'@'.core::$nicks[$nick]['oldhost'].' => '.core::$nicks[$nick]['host'].') ('.core::$nicks[$nick]['gecos'].') left the network ('.core::$nicks[$nick]['server'].')' );
		// log
		
		$ip_addr = core::$nicks[$nick]['ip_address'];
		$uid = self::get_uid( $nick );
		
		if ( core::$ips[$ip_addr] == 1 )
			unset( core::$ips[$ip_addr] );
		else
			core::$ips[$ip_addr]--;
		// check if ip record is set
		
		unset( core::$nicks[$nick] );
		unset( core::$uids[$uid] );
		// remove a user if they've quit..
				
		while ( list( $chan, $data ) = each( core::$chans ) )
		{
			if ( isset( core::$chans[$chan]['users'][$nick] ) )
				unset( core::$chans[$chan]['users'][$nick] );
			else
				continue;
		}
		reset( core::$chans );
		
		core::alog( 'on_quit(): '.$nick.' quit', 'MISC' );
		
		foreach ( modules::$event_methods['on_quit'] as $l => $class )
			$class::on_quit( $nick, $startup );
		// call the event method
	}
	
	/*
	* handle_host_change
	*
	* @params
	* $nick - NOT A UID
	* $host
	*/
	static public function handle_host_change( $nick, $host )
	{
		core::$nicks[$nick]['oldhost'] = core::$nicks[$nick]['host'];	
		core::$nicks[$nick]['host'] = $ircdata[2];
		core::alog( 'on_host_change(): '.$nick.'\'s host changed to '.$host, 'MISC' );
	}
	
	/*
	* handle_ident_change
	*
	* @params
	* $nick - NOT A UID
	* $ident
	*/
	static public function handle_ident_change( $nick, $ident )
	{
		core::$nicks[$nick]['ident'] = $ident;
		core::alog( 'on_ident_change(): '.$nick.' changed ident to '.$ident, 'MISC' );
	}
	
	/*
	* handle_gecos_change
	*
	* @params
	* $nick - NOT A UID
	* $gecos
	*/
	static public function handle_gecos_change( $nick, $gecos )
	{
		core::$nicks[$nick]['gecos'] = $gecos;
		core::alog( 'on_gecos_change(): '.$nick.' changed gecos to '.$gecos, 'MISC' );
	}
	
	/*
	* handle_mode
	*
	* @params
	* $chan
	* $mode_queue
	*/
	static public function handle_mode( $nick, $chan, $mode_queue )
	{
		$mode_array = mode::sort_modes( $mode_queue );
		mode::append_modes( $chan, $mode_array );
		mode::handle_params( $chan, $mode_array );
		// we only send it if the mode actually has anything in it.
	
		core::alog( 'on_mode(): '.$nick.' set '.$mode_queue.' on '.$chan, 'MISC' );
		
		foreach ( modules::$event_methods['on_mode'] as $l => $class )
			$class::on_mode( $nick, $chan, $modes );
		// call the event method
	}
	
	/*
	* handle_topic
	*
	* @params
	* $chan, $topic, $nick
	*/
	static public function handle_topic( $chan, $topic, $nick )
	{
		core::$chans[$chan]['topic'] = $topic;
		core::$chans[$chan]['topic_setter'] = $nick;
		core::alog( 'on_ftopic(): topic for '.$chan.' changed', 'MISC' );
		
		foreach ( modules::$event_methods['on_topic'] as $l => $class )
			$class::on_topic( $nick, $chan, $topic );
		// call the event method
	}
	
	/*
	* handle_channel_create
	*
	* @params
	* $chans, $nusers, $timestamp, $mode_queue
	*/
	static public function handle_channel_create( $chan, $nusers, $timestamp, $mode_queue )
	{
		if ( !isset( core::$chans[$chan] ) )
		{
			core::$chans[$chan]['channel'] = $chan;
			core::$chans[$chan]['timestamp'] = $timestamp;
			core::$chans[$chan]['p_modes'] = array();
			core::$chans[$chan]['joins'] = 0;
		}
		// we don't count bursts as joins as this is only here for flood protection
		// and flood protection would be activated on bursts which is what we DON'T want
		// for instance if a server splits loses 30 users on a channel, when it reconnects
		// 30 users join in a second causing chanserv to trigger flood.
		
		if ( is_array( core::$chans[$chan]['users'] ) )
			core::$chans[$chan]['users'] = array_merge( $nusers, core::$chans[$chan]['users'] );
		else
			core::$chans[$chan]['users'] = $nusers;
		// basically check if we already have an array, because FJOIN can happen on
		// existing channels, idk why, maybe on bursts etc?
		
		if ( $mode_queue != '+' || $mode_queue != '-' || trim( $mode_queue ) == '' )
		{
			$mode_array = mode::sort_modes( $mode_queue );
			mode::append_modes( $chan, $mode_array );
			mode::handle_params( $chan, $mode_array );
		}
		// parse modes, modes in inspircd 1.2 > (1202 protocol) are sent in SJOIN/FJOIN now, upon bursts, and also resent
		// when users join channels
		
		foreach ( modules::$event_methods['on_chan_create'] as $l => $class )
			$class::on_chan_create( $chan );
		// call the event method
	}
	
	/*
	* handle_join
	*
	* @params
	* $chans, $nick
	*/
	static public function handle_join( $chans, $nick )
	{
		foreach ( $chans as $chan )
		{
			core::$chans[$chan]['joins']++;
			// ++ joins
		
			if ( !isset( core::$chans[$chan]['users'][$nick] ) )
				core::$chans[$chan]['users'][$nick] = '';
			// maintain the logged users array
			
			core::alog( 'on_join(): '.$nick.' joined '.$chan, 'MISC' );
			
			core::join_flood_check( $nick, $chan );
			// flood check
			
			foreach ( modules::$event_methods['on_join'] as $l => $class )
				$class::on_join( $nick, $chan );
			// call the event method
		}
	}

	/*
	* handle_part
	*
	* @params
	* $chan, $nick
	*/
	static public function handle_part( $chan, $nick )
	{
		unset( core::$chans[$chan]['users'][$nick] );
		// remove the user out of the array
		core::alog( 'on_part(): '.$nick.' left '.$chan, 'MISC' );
		
		foreach ( modules::$event_methods['on_part'] as $l => $class )
			$class::on_part( $nick, $chan );
		// call the event method
	}
	
	/*
	* handle_kick
	*
	* @params
	* $chan, $who
	*/
	static public function handle_kick( $nick, $chan, $who )
	{
		unset( core::$chans[$chan]['users'][$who] );
		// again, move them out.
		core::alog( 'on_kick(): '.$nick.' kicked '.$user.' from '.$chan, 'MISC' );
		
		foreach ( modules::$event_methods['on_kick'] as $l => $class )
			$class::on_kick( $nick, $who, $chan );
		// call the event method
	}
	
	/*
	* handle_oper_up
	*
	* @params
	* $nick
	*/
	static public function handle_oper_up( $nick, $type = '' )
	{
		core::$nicks[$nick]['ircop'] = true;
		core::alog( 'on_oper_up(): '.core::get_full_hostname( $nick ).' OPER up', 'BASIC' );
		
		foreach ( modules::$event_methods['on_oper_up'] as $l => $class )
			$class::on_oper_up( $nick );
		// call the event method
	}
	
	/*
	* handle_msg
	*
	* @params
	* $nick, $target, $msg
	*/
	static public function handle_msg( $nick, $target, $msg )
	{
		foreach ( modules::$event_methods['on_msg'] as $l => $class )
			$class::on_msg( $nick, $target, $msg );
		// call the event method
	
		commands::ctcp( $nick, $target, $msg );
		core::flood_check( $nick, $target, $msg );
		// commands and flood checking!
	}

	/*
	* ping
	*
	* @params
	* $response
	*/
	static public function ping( $response )
	{
		database::ping();
		// ping the db	
	}
	
	/*
	* parse_ircd_modules
	*
	* @params (all booleans)
	* $services_account
	*/
	static public function parse_ircd_modules( $services_account, $hidechans, $chghost, $chgident )
	{
		if ( !$services_account )
			timer::add( array( 'core', 'check_services', array() ), 3, 1 );
		// we dont? have services_account
		
		if ( $hidechans )
		{
			foreach ( ircd::$service_modes as $s_type => $s_mode )
				ircd::$service_modes[$s_type] = str_replace( 'I', '', $s_mode );
			// remove +I
			
			timer::add( array( 'core', 'check_services', array() ), 3, 1 );
			// hide chans isnt found, call check_services to notify the opers
		}
		// we don't have m_hidechans, not a major issue, just remove +I from ircd::$service_modes
		
		core::$services_account = $services_account;
		core::$hide_chans = $hidechans;
		ircd::$chghost = $chghost;
		ircd::$chgident = $chgident;
		// pass variables over.
	}
	
	/*
	* parse_ircd_modes
	*
	* @params
	* $max_modes, $pdata, $data
	*/
	static public function parse_ircd_modes( $max_modes, $pdata, $data, $hdata )
	{
		ircd::$max_params = $max_modes;
		// set $max_params to MAXMODES everything below is mode related
		
		$split_data = explode( ',', $data );
		ircd::$restrict_modes = $split_data[0];
		ircd::$modes_p_unrequired = $split_data[2];
		// explode our modes up and assign them
		
		if ( strpos( ircd::$restrict_modes, 'q' ) !== false )
		{
			ircd::$owner = true;
			ircd::$status_modes[] .= 'q';
			ircd::$restrict_modes = str_replace( 'q', '', ircd::$restrict_modes );
			// remove from $restrict_modes and add to $status_modes IN ORDER ;)
		}
		// search restrict modes for 'aq' as we want them in status modes
		
		if ( strpos( ircd::$restrict_modes, 'a' ) !== false )
		{
			ircd::$protect = true;
			ircd::$status_modes[] .= 'a';
			ircd::$restrict_modes = str_replace( 'a', '', ircd::$restrict_modes );
			// remove from $restrict_modes and add to $status_modes IN ORDER ;)
		}
		// search restrict modes for 'aq' as we want them in status modes
		
		if ( $hdata )
			ircd::$halfop = true;
		// we check halfop differently
		
		ircd::$status_modes = array_merge( ircd::$status_modes, str_split( preg_replace( '/[^A-Za-z]/', '', $pdata ) ) );			
		ircd::$modes_params = implode( '', ircd::$status_modes ).ircd::$restrict_modes.$split_data[1].ircd::$modes_p_unrequired;
		ircd::$modes = ircd::$modes_params.$split_data[3];
		// causing status modes not to be in ircd::$modes
		
		$parsed_pdata = explode( ')', $pdata );
		$pdata_modes = str_split( str_replace( '(', '', $parsed_pdata[0] ) );
		$pdata_prefix = str_split( $parsed_pdata[1] );
		// parse up PREFIX=(ohv) data etc
		
		if ( strpos( $parsed_pdata[0], 'q' ) === false )
			ircd::$prefix_modes['q'] = $pdata_prefix[0];
		if ( strpos( $parsed_pdata[0], 'a' ) === false )
			ircd::$prefix_modes['a'] = $pdata_prefix[0];
		// if q and a arn't in $parsed_pdata[0] that means there are no prefixes for them modes set
		// so standard @ (well, not technically, but in our case, yes.
		
		foreach ( $pdata_modes as $i => $ix )
			ircd::$prefix_modes[$ix] = $pdata_prefix[$i];
		// loop em n sort it out
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
		core::alog( 'init_server(): '.$name.' introduced :'.$desc, 'BASIC' );
		// log it
	}
	
	/*
	* introduce_client
	*
	* @params
	* $nick, $uid, $ident, $hostname, $gecos, $enforcer
	*/
	static public function introduce_client( $nick, $uid, $ident, $hostname, $gecos, $enforcer )
	{
		core::$times[$nick] = core::$network_time;
		// just so if we do need to change anything, we've still got it.
		
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
	* $uid - uid
	* $message - quit message
	*/
	static public function remove_client( $nick, $uid, $message )
	{
		unset( core::$times[$nick] );
		unset( core::$nicks[$nick] );
		unset( core::$uids[$uid] );
		// we unset that, just to save memory
		
		core::alog( 'remove_client(): removed '.$nick, 'MISC' );
		// debug
	}
	
	/*
	* wallops
	*
	* @params
	* $nick, $message
	*/
	static public function wallops( $nick, $message )
	{
		core::alog( 'wallops(): '.$nick.' sent a wallops', 'MISC' );
		// debug info
	}
	
	/*
	* global_notice
	*
	* @params
	* $nick, $mask, $message
	*/
	static public function global_notice( $nick, $mask, $message )
	{
		core::alog( 'global_notice(): sent from '.$nick, 'MISC' );
		// debug info
		
		while ( list( $user, $data ) = each( core::$nicks ) )
		{
			$hostname = core::get_full_hostname( $user );
			// hostname
			
			if ( $data['server'] != core::$config->server->name && services::match( $hostname, $mask ) )
				ircd::notice( $nick, $user, $message );
		}
		reset( core::$nicks );
	}
	
	/*
	* mode
	*
	* @params
	* $nick, $chan, $mode
	*/
	static public function mode( $nick, $chan, $mode )
	{
		if ( !isset( core::$chans[$chan]['timestamp'] ) || core::$chans[$chan]['timestamp'] == '' )
			core::$chans[$chan]['timestamp'] = core::$network_time;
		// update channel timestamp
	
		$mode_array = mode::sort_modes( $mode );
		mode::append_modes( $chan, $mode_array );
		mode::handle_params( $chan, $mode_array );
		// we only send it if the mode actually has anything in it.
		
		core::alog( 'mode(): '.$nick.' set '.$mode.' on '.$chan, 'MISC' );
		// debug info
	}
	
	/*
	* umode
	*
	* @params
	* $nick, $user, $mode
	*/
	static public function umode( $nick, $user, $mode )
	{
		core::alog( 'umode(): '.$nick.' set '.$mode.' on '.$user, 'MISC' );
		// debug info
	}
	
	/*
	* join_chan
	*
	* @params
	* $nick, $chan
	*/
	static public function join_chan( $nick, $chan )
	{
		core::$chans[$chan]['users'][$nick] = '';
		// add us to the channel array
		
		core::alog( 'join_chan(): '.$nick.' joined '.$chan, 'MISC' );
		// debug info
	}
	
	/*
	* part_chan
	*
	* @params
	* $nick, $chan
	*/
	static public function part_chan( $nick, $chan )
	{
		unset( core::$chans[$chan]['users'][$nick] );
		// remove us from the channel
		
		core::alog( 'part_chan(): '.$nick.' left '.$chan, 'MISC' );
		// debug info
	}
	
	/*
	* topic
	*
	* @params
	* $nick, $chan, $topic
	*/
	static public function topic( $nick, $chan, $topic )
	{
		core::alog( 'topic(): '.$nick.' set a topic for '.$chan, 'MISC' );
		// debug info
	}
	
	/*
	* kick
	*
	* @params
	* $$nick, $user, $chan, $reason
	*/
	static public function kick( $nick, $user, $chan, $reason = '' )
	{				
		core::alog( 'kick(): '.$nick.' kicked '.$user.' from '.$chan, 'MISC' );
		// debug info
	}
		
	/*
	* sethost
	*
	* @params
	* $from, $nick, $host
	*/
	static public function sethost( $from, $nick, $host )
	{
		core::alog( 'sethost(): '.$from.' set '.$nick.'\'s host', 'MISC' );
		// debug info
		
		core::$nicks[$nick]['oldhost'] = core::$nicks[$nick]['host'];
		core::$nicks[$nick]['host'] = $host;
	}
	
	/*
	* setident
	*
	* @params
	* $from, $nick, $ident
	*/
	static public function setident( $from, $nick, $ident )
	{
		core::alog( 'setident(): '.$from.' set '.$nick.'\'s ident', 'MISC' );
		// debug info
			
		core::$nicks[$nick]['ident'] = $ident;
	}
	
	/*
	* svsnick
	*
	* @params
	* $old_nick, $new_nick, $timestamp
	*/
	static public function svsnick( $old_nick, $new_nick, $timestamp )
	{
		core::alog( 'svsnick(): '.$old_nick.' changed to '.$new_nick, 'MISC' );
		// debug info
	}
	
	/*
	* kill
	*
	* @params
	* $nick, $user, $message
	*/
	static public function kill( $nick, $user, $message )
	{
		core::alog( 'kill(): '.$nick.' killed '.$user, 'MISC' );
		// debug info
	}
	
	/*
	* global_ban
	*
	* @params
	* $nick, $mask, $duration, $message
	*/
	static public function global_ban( $nick, $mask, $duration, $message )
	{
		core::alog( 'global_ban(): '.$nick.' set a global ban on '.$mask.' for '.$duration.' minutes', 'BASIC' );
		// debug info
	}
	
	/*
	* shutdown
	*
	* @params
	* $message, $terminate
	*/
	static public function shutdown( $message, $terminate = false )
	{
		core::alog( 'shutdown(): '.$message, 'BASIC' );
		// debug info
		
		socket_engine::close( 'core' );
		if ( $terminate ) exit;
		// if true, exit;
	}
	
	/*
	* push
	*
	* @params
	* $from, $numeric, $nick, $message
	*/
	static public function push( $from, $numeric, $nick, $message )
	{
		core::alog( 'push(): '.$from.' pushed text to '.$nick.' on numeric '.$numeric, 'MISC' );
		// debug info
	}
	
	/*
	* send
	*
	* @params
	* $command
	*/
	static public function send( $command )
	{
		$command = trim( $command );
		$bytes = strlen( $command."\r" );
		socket_write( socket_engine::$sockets['core'], $command."\r", $bytes );
		// fairly simple, hopefully.
		
		core::$outgoing = core::$outgoing + $bytes;
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
	* not many of these apply to this class.
	*/
	
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
		
		if ( isset( core::$uids[$uuid] ) )
			return core::$uids[$uuid];
		else
			return $ircdata[$number];
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
	static public function parse_users( $chan, $users )
	{
		if ( trim( $users[0] ) == '' )
			return array();
			
		foreach ( $users as $i => $user )
		{
			if ( $user == null || $user == ' ' )
				continue;
			$user = trim( $user );
			
			if ( !preg_match( '/[^a-zA-Z0-9\s]/', $user ) )
			{
				$nick = self::get_nick( $users, $i );
				$nusers[$nick] = '';
				// just a normal unprivilaged user
			}
			else
			{
				if ( strpos( $user, ',' ) !== false )
				{
					$n_user = explode( ',', $user );
					$nick = self::get_nick( $n_user, 1 );
					// split it via ,
					
					$nusers[$nick] = $n_user[0];
				}
				else
				{
					$modes = preg_replace( '/[a-zA-Z0-9\s]/', '', $user );
					$nick = array( preg_replace( '/[^a-zA-Z0-9\s]/', '', $user ) );
					$nick = self::get_nick( $nick, 0 );
					
					$modes = str_replace( '@', 'o', $modes );
					$modes = str_replace( '%', 'h', $modes );
					$modes = str_replace( '+', 'v', $modes );
					// replace @ to o, % to h, + to v etc.
					
					$nusers[$nick] = $modes;
				}
				// if it has a , parse it.
			}
		}
		
		return $nusers;
	}
}
// EOF;
