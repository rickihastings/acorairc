<?php

/*
* Acora IRC Services
* src/core.php: Core class that initiates everything.
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

class core
{
	
	static public $times;
	static public $network_time = 0;
	static public $uptime = 0;
	static public $config = array();
	static public $max_users;
	static public $incoming = 0;
	static public $outgoing = 0;
	static public $log_data = array();
	static public $debug_data = array();
	static public $debug = false;
	static public $services_account = false;
	static public $hide_chans = true;
	static public $log_levels = array();
	// our main static variables, these are all very important.
	
	static public $bots = array();
	static public $servers = array();
	static public $chans = array();
	static public $nicks = array();
	static public $uids = array();
	static public $ips = array();
	static public $help;
	// we set $uids anyway, although its only used on networks
	// that use TS6 - UUID/SID style protocol, it's here anyway.
	
	static public $version = 'MC42LjFiZXRhK3RoZXJvbWFu';
	// version
	
	static public $end_burst = false;
	static public $capab_start = false;
	static public $lines_processed;
	static public $lines_sent;
	static public $service_bots;
	static public $booted = false;
	static public $burst_time;
	// these HAVE to be declared unfortunatly
	// never the less all these variables are here, buffer, flags etc.
	
	/*
	* __construct
	*
	* @params
	* void
	*/
	public function __construct( $argv )
	{
		self::$network_time = time();
		// network time, sets to self::$network_time until we recieve some info from a main server.
		
		$parser = new parser( CONFPATH.'services.conf' );
		// get the config values.
		
		self::$log_levels = explode( ' ', self::$config->settings->loglevel );
		self::$debug = ( $argv[1] == 'debug' ) ? true : false;
		// is debug mode running? y/n
		foreach ( self::$log_levels as $type )
		{
			$message = 'FILE: log/'.$type.'.log; DATE: '.date( 'd/m/Y H:i:s', time() );
			$length = strlen( $message );
			$divider = '';
			for ( $i = 0; $i < $length; $i++ )
				$divider .= '-';
			// set up some variables
			
			self::$log_data[$type][] = $divider;
			self::$log_data[$type][] = $message;
			self::$log_data[$type][] = $divider;
		}
		// immediately populate log data with some values that will be written to the files first
		// just a date/divider etc
		
		parser::check();
		// check for required vars etc
		
		if ( isset( self::$config->operserv ) ) self::$service_bots[] = 'operserv';
		if ( isset( self::$config->nickserv ) ) self::$service_bots[] = 'nickserv';
		if ( isset( self::$config->chanserv ) ) self::$service_bots[] = 'chanserv';
		// setup our $config->service_bots dir
		
		require( BASEPATH.'/lang/'.self::$config->server->lang.'/core.php' );
		self::$help = $help;
		// load the help file
		
		new timer();
		new mode();
		new services();
		new commands();
		new modules();
		new ircd_handle();
		new socket_engine();
		// setup all the subclasses.
		
		database::factory( self::$config->database->driver );
		self::connect();
		self::protocol_init();
		// setup the db, connect to the socket,load the protocol class
		
		$select = database::select( 'core', array( 'max_users', 'max_userstime' ) );
		if ( database::num_rows( $select ) == 0 )
		{
			database::insert( 'core', array( 'max_users' => 0, 'max_userstime' => self::$network_time ) );
			$select = database::select( 'core', array( 'max_users', 'max_userstime' ) );
		}
		// if there is no max users, create a row
		
		$max_users = database::row( $select );
		// get the max users
		
		self::$max_users = $max_users[0];
		// set a global variable
		
		timer::add( array( 'core', 'reset_flood_cache', array() ), 120, 0 );
		// add a timer to reset the flood cache every 120 seconds, indefinatly
		
		if ( ( self::$config->settings->loglevel != 'off' || !isset( self::$config->settings->loglevel ) ) )
			timer::add( array( 'core', 'save_logs', array() ), 300, 0 );	
		// add another timer to save logs every 5 mins
		
		timer::add( array( 'core', 'check_unused_chans', array() ), 5, 0 );
		// and another one to check for unused channels every 5 seconds XD
		
		if ( is_resource( socket_engine::$sockets['core'] ) && self::$config->conn )
			$this->main_loop();
		else
			exit;
		// execute the main program loop
	}
	
	/*
	* __destruct
	*
	* @params
	* void
	*/
	public function __destruct()
	{
		self::save_logs();
		// we also save logs on destruct, incase of a crash etc normally this doesn't
		// work because the order in which the classes are destroyed are random, this 
		// function isn't relying on anything other than it's self so it should work 
		// perfectly.
	}
	
	/*
	* main_loop
	*
	* @params
	* void
	*/
	public function main_loop()
	{
		while ( true )
		{
			timer::loop();
			// this is our timer counting function
			
			$tinybuffer = socket_engine::read( 'core', 16384 );
			// read from the socket
			
			foreach ( $tinybuffer as $l => $raw )
			{
				$raw = trim( $raw );
				if ( $raw == '' ) continue;
				$ircdata = explode( ' ', $raw );
				self::$lines_processed++;
				// grab the data
				
				if ( $raw != '' )
					self::alog( 'recv(): '.$raw, 'SERVER' );
				// log SERVER
				
				self::$incoming = self::$incoming + strlen( $raw );
				// log our incoming bandwidth
				
				unset( $tinybuffer[$l] );
				
				if ( isset( ircd_handle::$commands[$ircdata[1]] ) )
					$method = ircd_handle::$commands[$ircdata[1]];
				elseif ( isset( ircd_handle::$commands[$ircdata[0]] ) )
					$method = ircd_handle::$commands[$ircdata[0]];
				
				ircd::$method( $ircdata );
				// call the event handler
			}
			
			if ( self::$debug && count( self::$debug_data ) > 0 )
			{
				foreach ( self::$debug_data as $line => $message )
				{
					if ( trim( $message ) != '' || trim( $message ) != null )
						print "[".date( 'd/m/Y H:i:s', time() )."] ".$message."\r\n";
					// only print if we have something to print
						
					unset( self::$debug_data[$line] );
					// ALWAYS unset it.
				}
			}
			elseif ( self::$debug && count( self::$debug_data ) == 0 )
				self::$debug_data = array();
			// here we output debug data, if there is any.
			
			usleep( 15000 );
			// 50000 breaks /hop and /cycle
			// 40000 is quite slow when handling alot of data
			// 15/20/25 000 has high cpu usage for 10 mins or so, i'm settling at 15000
			// as after about 5 mins the usage drops dramatically, eventually to 0.0, and performance is increased
		}
	}
	
	/*
	* boot_server (private)
	*
	* @params
	* void
	*/
	static public function boot_server()
	{
		if ( !self::$booted )
		{
			self::$booted = true;
			
			ircd::init_server( self::$config->server->name, self::$config->conn->password, self::$config->server->desc, self::$config->server->numeric );
			// init the server
		}
	}
	
	/*
	* post_boot_server (private)
	*
	* @params
	* void
	*/
	static public function post_boot_server()
	{
		if ( self::$capab_start && !self::$end_burst )
			ircd::send_version( array() );
	
		foreach ( self::$service_bots as $bot )
		{
			require( BASEPATH.'/src/services/'.$bot.'.php' );
			self::$bots[$bot] = new $bot();
		}
		// start our bots up.
		
		foreach ( self::$config->core_modules as $id => $module )
			modules::load_module( 'core_'.$module, $module.'.core.php' );
		// we load core modules before the bots, incase there
		// is a module that changes an existing function w/e
		
		services::sort_flags( 'chanserv', 'csflags' );
		services::sort_flags( 'chanserv', 'cslevels' );
		services::sort_flags( 'nickserv', 'nsflags' );
		// sort flags up
		
		database::delete( 'ignored_users', array( 'temp', '=', '1' ) );
		// remove all temp ignore bans, services may have shutdown before the timer removed their ban
		// leaving them permanently banned.
		
		timer::init();
		// setup the timer, socket_blocking to 0 is required.
	}
	
	/*
	* check_services
	*
	* @params
	* void
	*/
	static public function check_services()
	{
		if ( !self::$services_account )
		{
			self::alog( 'ERROR: service accounts is required, startup halted', 'BASIC' );
			// log it
			
			self::save_logs();
			// save logs.
			
			ircd::shutdown( 'ERROR: service accounts is required, startup halted', true );
			// exit
		}
		// services account isn't found, quit out letting them know.
		
		if ( !self::$hide_chans )
			self::alog( 'WARNING: +I is either not loaded or not available, this is NOT advised' );
		// let the dude know that we don't have a module that hides chans +I
	}
	
	/*
	* core_error
	*
	* @params
	* $severity - error severity
	* $message - error string
	* $filepath - error file
	* $line - error line
	*/
	static public function core_error( $severity, $message, $filepath, $line )
	{
		if ( ( error_reporting() & $severity ) != $severity )
			return;
		// this error code is not included in error_reporting
		
		self::alog( 'error: '.trim( $message ).' on line '.$line.' in '.$filepath, 'BASIC' );
		self::save_logs();
		// save logs.
		
		if ( $severity != E_WARNING && $severity != E_NOTICE )
			ircd::shutdown( 'ERROR: '.$message, true );
		// exit the program	
		
		return true;
		// don't execute php internal error handler
	}
	
	/*
	* max_users
	*
	* @params
	* $ircdata - ..
	*/
	static public function max_users()
	{
		if ( count( self::$nicks ) > self::$max_users )
		{
			$update_array = array(
				'max_users' => count( self::$nicks ),
				'max_userstime' => self::$network_time
			);
			
			$update = database::update( 'core', $update_array, array( 'id', '=', '1' ) );
			self::$max_users = count( self::$nicks );
			// update the max users
			
			self::alog( self::$config->operserv->nick.': New user peak: '.count( self::$nicks ).' users' );
			// logchan
			
			return true;
		}
		// if the current number of users is more than the previous max
	}
	
	/*
	* save_logs
	*
	* @params
	*/
	static public function save_logs()
	{
		if ( !is_dir( BASEPATH.'/log' ) )
			mkdir( BASEPATH.'/log' );
		// does the log dir exist?	
		
		foreach ( self::$log_data as $type => $messages )
		{
			$filepath = BASEPATH.'/log/'.$type.'.log';
			if ( file_exists( $filepath ) )
				chmod( $filepath, 0777 );
			else
				touch( $filepath );
			// do some checks
			
			if ( !$fp[$type] = fopen( $filepath, 'a' ) )
				return false;
			// STILL can't open the files :@
			
			$filemsg = '';
			foreach ( $messages as $line => $message )
				$filemsg .= $message."\n";
			
			fwrite( $fp[$type], $filemsg, strlen( $filemsg ) );
			fclose( $fp[$type] );
			// and we ALSO send it into the logfile
			
			unset( self::$log_data[$type] );
		}
	}
	
	/*
	* check_unused_chans
	*
	* @params
	* void
	*/
	static public function check_unused_chans()
	{
		while ( list( $chan, $data ) = each( self::$chans ) )
		{
			self::$chans[$chan]['joins'] = 0;
		
			if ( count( self::$chans[$chan]['users'] ) == 0 )
			{
				if ( strstr( self::$config->server->ircd, 'inspircd' ) && strstr( self::$chans[$chan]['modes'], 'P' ) )
					continue;
				// there isnt any users, BUT, does the channel have +P set?
				// if it does, continue;
			
				unset( self::$chans[$chan] );
				// unset it
			}
			// no users, unset chan
		}
		
		reset( self::$chans );
		// reset the internal pointer
	}
	
	/*
	* reset_flood_cache
	*
	* @params
	* void
	*/
	static public function reset_flood_cache()
	{
		while ( list( $nick, $data ) = each( self::$nicks ) )
		{
			self::$nicks[$nick]['commands'] = null;
			self::$nicks[$nick]['floodcmds'] = 0;
			self::$nicks[$nick]['failed_attempts'] = 0;
		}
		// loop though our users, setting everything to false/0/null
		// everything flood related, ofc.
		
		reset( self::$nicks );
		// reset the internal pointer, again
	}
	
	/*
	* remove_ignore
	*
	* @params
	* void
	*/
	static public function remove_ignore( $who )
	{
		database::delete( 'ignored_users', array( 'who', '=', $who ) );
		// remove it.
	}
	
	/*
	* join_flood_check
	*/
	static public function join_flood_check( $nick, $chan )
	{
		if ( self::$chans[$chan]['joins'] >= 10 )
		{
			mode::set( self::$config->chanserv->nick, $chan, '+isb *!*@*', true );
			self::alog( self::$config->operserv->nick.': Flood protection triggered for '.$chan.', +isb *!*@* set' );
		}
		// trigger flood protection
	}
	
	/*
	* flood_check
	*/
	static public function flood_check( $nick, $target, $msg )
	{
		if ( $target[0] == '#' && $msg[1] != self::$config->chanserv->fantasy_prefix )
			return true;
		// this is just here to instantly ignore any normal channel messages 
		// otherwise we get lagged up on flood attempts
		
		if ( $target[0] != '#' )
		{
			if ( self::$config->settings->flood_msgs == 0 || self::$config->settings->flood_time == 0 )
				return false;	
			// check if it's disabled.
			
			$time_limit = time() - self::$config->settings->flood_time;
			self::$nicks[$nick]['commands'][] = time();
			
			if ( self::$nicks[$nick]['ircop'] )
				return false;
			// ignore ircops (with caution!)
			
			$inc = false;
			foreach ( self::$nicks[$nick]['commands'] as $index => $timestamp )
				if ( $timestamp > $time_limit ) $inc = true;

			if ( $inc )
				self::$nicks[$nick]['floodcmds']++;
			// we've ++'d the floodcmds, if this goes higher than self::flood_trigger
			// they're flooding, floodcmds is cleared every 100 seconds.
 
			if ( self::$nicks[$nick]['floodcmds'] > self::$config->settings->flood_msgs )
			{
				if ( services::check_mask_ignore( $nick ) === true )
					return false;
				
				if ( self::$nicks[$nick]['offences'] == 0 || self::$nicks[$nick]['offences'] == 1 )
				{
					self::$nicks[$nick]['offences']++;
					database::insert( 'ignored_users', array( 'who' => '*!*@'.self::$nicks[$nick]['host'], 'time' => self::$network_time, 'temp' => '1' ) );
					timer::add( array( 'core', 'remove_ignore', array( '*!*@'.self::$nicks[$nick]['host'] ) ), 120, 1 );
					// add them to the ignore list.
					// also, add a timer to unset it in 2 minutes.
					
					$message = ( self::$nicks[$nick]['offences'] == 1 ) ? 'This is your first offence' : 'This is your last warning';
					// compose a message.
					
					services::communicate( $target, $nick, operserv::$help->OS_COMMAND_LIMIT_1 );
					services::communicate( $target, $nick, operserv::$help->OS_COMMAND_LIMIT_2, array( 'message' => $message ) );
					self::alog( self::$config->operserv->nick.': Offence #'.self::$nicks[$nick]['offences'].' for '.self::get_full_hostname( $nick ).' being ignored for 2 minutes' );
					self::alog( 'flood_check(): Offence #'.self::$nicks[$nick]['offences'].' for '.self::get_full_hostname( $nick ), 'BASIC' );
					
					return true;
				}
				elseif ( self::$nicks[$nick]['offences'] >= 2 )
				{
					self::alog( self::$config->operserv->nick.': Offence #'.self::$nicks[$nick]['offences'].' for '.self::get_full_hostname( $nick ).' being glined for 10 minutes' );
					self::alog( 'flood_check(): Offence #'.self::$nicks[$nick]['offences'].' for '.self::get_full_hostname( $nick ), 'BASIC' );
					
					ircd::global_ban( self::$config->operserv->nick, '*@'.self::$nicks[$nick]['oldhost'], 600, 'Flooding services, 10 minute ban.' );
					// third offence, wtf? add a 10 minute ban.
					
					return true;
				}
			}
			// they're flooding
		}
	}
	
	/*
	* protocol_init
	*
	* @params
	* void
	*/
	static public function protocol_init()
	{
		if ( !file_exists( BASEPATH.'/src/protocol/'.self::$config->server->ircd.'.php' ) )
		{
			self::alog( 'protocol_init(): failed to initiate protocol module '.self::$config->server->ircd, 'BASIC' );
			
			self::save_logs();
			// force a log save.
		}
		else
		{
			require( BASEPATH.'/src/protocol/'.self::$config->server->ircd.'.php' );
			new ircd();
		}
		// can we initiate the protocol module? :S
	}
	
	/*
	* connect
	*
	* @params
	* void
	*/
	static public function connect()
	{
		$info = self::$config->uplink;
		// setup $info
	
		do
		{
			$run = socket_engine::create( 'core', $info->host, $info->port, false );
			// attempt to create a socket
			
			if ( $run )
			{
				self::$config->conn = new stdClass;
				self::$config->conn->password = $info->password;
				self::$config->conn->server = $info->server;
				self::$config->conn->port = $info->port;
				self::$config->conn->vhost = $info->vhost;
				// we've connected, set our config details up.
			
				self::alog( 'connect(): core socket created' );
				break;
			}
			// socket was created
			else
			{
				self::alog( 'connect(): failed to create socket, attempting again in '.self::$config->server->recontime );
				sleep( self::$config->server->recontime );
			}
			// it wasen't created..
			
		} while ( $run === false );
		// keep attempting to create the socket
		
		self::save_logs();
		// force a log save.
	}
	
	/*
	* alog
	*
	* @params
	* $message - message to log
	* $type - this should be either BASIC, SERVER, DATABASE or CHAN
	*/
	static public function alog( $message, $type = 'CHAN' )
	{
		$type = strtolower( $type );
		
		if ( $type != 'chan' && ( self::$config->settings->loglevel != 'off' || !isset( self::$config->settings->loglevel ) ) )
		{
			if ( in_array( $type, self::$log_levels ) || self::$config->settings->loglevel == 'all' )
			{
				if ( self::$debug && !isset( self::$config->conn ) )
					print '['.date( 'd/m/Y H:i:s', time() ).'] '.$message."\n";
				if ( !in_array( $message, self::$debug_data ) )
					self::$debug_data[] = $message;
				if ( !in_array( $message, self::$log_data ) )
					self::$log_data[$type][] = '['.date( 'd/m/Y H:i:s', time() ).'] '.$message;
				// if we're not connected, and in debug mode
				// just send it out, else they wont actually see the message and
				// it'll just end up in the log file
			}
		}
		// debug on?
		elseif ( $type == 'chan' && isset( self::$config->settings->logchan ) && isset( modules::$list['os_global'] ) )
		{
			if ( !in_array( $message, self::$log_data ) )
				self::$log_data[$type][] = '['.date( 'd/m/Y H:i:s', time() ).'] '.self::$config->global->nick.'/'.self::$config->settings->logchan.': '.$message;
			// we also log logchan stuff to file, as this can prove very useful.
			ircd::msg( self::$config->global->nick, self::$config->settings->logchan, $message );
			// send the message into the logchan
		}
		// logging is enabled, so send the message into the channel.
	}
	
	/*
	* information functions
	*
	* Yeah, stuff like self::get_nick/chan, etc..
	*/
	
	/*
	* get_full_hostname
	*
	* @params
	* $nick - ..
	*/
	static public function get_full_hostname( $nick )
	{
		return self::$nicks[$nick]['nick'].'!'.self::$nicks[$nick]['ident'].'@'.self::$nicks[$nick]['host'];
	}
	
	/*
	* search_nick
	*
	* @params
	* $nick - ..
	*/
	static public function search_nick( $nick )
	{
		while ( list( $unick, $uarray ) = each( self::$nicks ) )
		{
			if ( strcasecmp( $nick, $unick ) == 0 )
			{
				$break = $uarray;
				break;
			}
		}
		
		reset( self::$nicks );
		return $break;
		// i can see this being a bit resourcive on larger networks
		// but bearing in mind it isnt used often, only on fantasy
		// and xcommands, using bans.
		
		// consider re-thinking this, maybe doing some benchmarks on an xxxx count user network
		// to see whats quicker, I'm gonna try convert everything that needs to find an active
		// nick to using this function, I think most of it's done - n0valyfe
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
		// moved this into the protocol module
	}
	
	/*
	* get_chan
	*
	* @params
	* $ircdata - ..
	* $number - ..
	*/
	static public function get_chan( $ircdata, $number )
	{
		$chan = $ircdata[$number];
		
		return strtolower( $chan );
	}
	
	/*
	* get_data_after
	*
	* @params
	* $ircdata - ..
	* $number - ..
	*/
	static public function get_data_after( $ircdata, $number )
	{
		$new_ircdata = $ircdata;
		
		for ( $i = 0; $i < $number; $i++ )
			unset( $new_ircdata[$i] );
		// the for loop lets us determine where to go, how many to get etc.. so hard to explain
		// but so easy to understand when your working with it :P
		// we reset the variable and unset everything that isnt needed
		// just to make sure we dont fuck something up with $ircdata (fragile x])
		$new = implode( ' ', $new_ircdata );
		
		return trim( $new );
	}
	
	/*
	* get_size (private)
	* 
	* @params
	* $size = bytes to convert to
	*/
	static public function get_size( $size )
	{
		$bytes = array( 'bytes', 'KB', 'MB', 'GB', 'TB' );
		
		foreach ( $bytes as $val )
		{
			if ( $size > 1024 ) $size = $size / 1024;
			else break;
		}
		
		return round( $size, 2 ).' '.$val;
		// pretty simple function, made by WinSrev @ SrevSpace
	}
	
	/*
	* format_time (private)
	* 
	* @params
	* $seconds - Number of seconds to format into days, hours, mins & seconds
	*/
	static public function format_time( $seconds )
	{
		// haha, this was epically messy, now it's epically
		// sexy.. nah, it does the job i reckon.
		
		$return = '';

		$days = floor( $seconds / 86400 );
		$remaining = $seconds - ( $days * 86400 );
		if ( $days > 0 ) $return .= $days.'d ';
		// days
		
		$hours = floor( $remaining / 3600 );
		$remaining = $remaining - ( $hours * 3600 );
		if ( $hours > 0 ) $return .= $hours.'h ';
		// hours
		
		$mins = floor( $remaining / 60 );
		$remaining = $remaining - ( $mins * 60 );
		if ( $mins > 0 ) $return .= $mins.'m ';
		// minutes
		
		if ( $remaining > 0 ) $return .= $remaining.'s';
		// seconds
		
		return trim( $return );
		// return the result.
	}
}

// EOF;
