<?php

/*
* Acora IRC Services
* core/core.php: Core class that initiates everything.
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
	
	static public $socket;
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
	// our main static variables, these are all very important.
	
	static public $servers = array();
	static public $chans = array();
	static public $nicks = array();
	static public $uids = array();
	static public $help;
	// we set $uids anyway, although its only used on networks
	// that use TS6 - UUID/SID style protocol, it's here anyway.
	
	static public $version = '0.4.5beta+Cymric';
	// version
	
	static public $end_burst = false;
	static public $capab_start = false;
	static public $pullout = false;
	static public $lines_processed;
	static public $lines_sent;
	static public $buffer;
	static public $nbuffer;
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
		
		self::$debug = ( $argv[1] == 'debug' ) ? true : false;
		// is debug mode running? y/n
		
		if ( isset( self::$config->nickserv ) ) self::$service_bots[] = 'nickserv';
		if ( isset( self::$config->chanserv ) ) self::$service_bots[] = 'chanserv';
		if ( isset( self::$config->operserv ) ) self::$service_bots[] = 'operserv';
		// setup our $config->service_bots dir
		
		require( BASEPATH.'/lang/'.core::$config->server->lang.'/core.php' );
		self::$help = $help;
		// load the help file
		
		$this->timer = new timer();
		$this->mode = new mode();
		$this->services = new services();
		$this->commands = new commands();
		$this->modules = new modules();
		$this->ircd_handle = new ircd_handle();
		// setup all the subclasses.
		
		database::factory( self::$config->database->driver );
		// setup the db.
		
		self::$socket = self::connect();
		// connect to the socket
		
		self::protocol_init();
		// load the protocol class
		
		$select = database::select( 'core', array( 'max_users' ), array( 'id', '=', '1' ) );
		$max_users = database::row( $select );
		// get the max users
		
		self::$max_users = $max_users[0];
		// set a global variable
		
		timer::add( array( 'core', 'reset_flood_cache', array() ), 120, 0 );
		// add a timer to reset the flood cache every
		// 120 seconds, indefinatly
		
		if ( ( self::$config->settings->loglevel != 'off' || !isset( self::$config->settings->loglevel ) ) )
			timer::add( array( 'core', 'save_logs', array() ), 300, 0 );	
		// add another timer to save logs every 5 mins
		
		timer::add( array( 'core', 'check_unused_chans', array() ), 5, 0 );
		// and another one to check for unused channels every 5 seconds XD
		
		if ( is_resource( self::$socket ) )
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
			
			if ( self::$end_burst && count( self::$nbuffer ) > 0 )
			{
				foreach ( self::$nbuffer as $index => $ircdata )
				{
					self::$incoming = self::$incoming + strlen( implode( ' ', $ircdata ) );
					// log our incoming bandwidth
					
					if ( $this->process( $ircdata, true ) ) continue;
					// process the data from the buffer
					
					unset( self::$nbuffer[$index], $index, $ircdata );
				}
				// is there any data in the burst buffer?
				
				self::$nbuffer = array();
			}
			// but only when the burst has finished ^_^
			
			if ( self::$end_burst && count( self::$buffer ) > 0 )
			{
				foreach ( self::$buffer as $index => $ircdata )
				{
					self::$incoming = self::$incoming + strlen( implode( ' ', $ircdata ) );
					// log our incoming bandwidth
					
					if ( $this->process( $ircdata, false ) ) continue;
					// process normal incoming data
					
					unset( self::$buffer[$index], $index, $ircdata );
				}
				// is there any data in the buffer?
				
				self::$buffer = array();
			}
			// this is for normal data, eg. post burst.
				
			if ( $raw = stream_get_line( self::$socket, 4092, "\r\n" ) )
			{
				$raw = trim( $raw );
				$ircdata = explode( ' ', $raw );
				self::$lines_processed++;
				// grab the data
				
				if ( $raw != '' )
					self::alog( 'recv(): '.$raw, 'SERVER' );
				// log SERVER
				
				if ( ( self::$config->settings->loglevel != 'off' || !isset( self::$config->settings->loglevel ) ) && self::$end_burst ) self::save_logs();
				// we also logfile here, and stop logfiling until we've
				// reached the end of the burst, then we do it every 5 mins
				
				if ( ircd::on_capab_start( $ircdata ) ) self::$capab_start = true;
				// if capab has started we set a true flag, just like
				// we do with burst
				
				if ( ircd::on_capab_end( $ircdata ) )
				{
					self::$capab_start = false;
					
					$this->boot_server();
					// introduce server etc
				}
				// we need to respectivly wait for capab end
				// before we're suppost to boot everything
				// we also set the flag to false cause capab has ended.
				
				ircd::get_information( $ircdata );
				// modes and stuff we check for here.
				
				if ( !self::$pullout )
					$this->log_changes( $ircdata, false );
				
				if ( ircd::on_start_burst( $ircdata ) )
				{
					self::$end_burst = false;
					
					if ( strstr( core::$config->server->ircd, 'inspircd' ) )
						self::$network_time = $ircdata[2];
					
					self::$burst_time = microtime( true );
					// how long did the burst take?
					
					$this->post_boot_server();
					// post boot
				}
				// if we recieve a start burst, we also adopt the time given to us
				
				if ( ircd::on_end_burst( $ircdata ) )
				{
					self::$burst_time = round( microtime( true ) - self::$burst_time, 4 );
					if ( self::$burst_time[0] == '-' ) substr( self::$burst_time, 1 );
					// nasty hack to get rid of minus values.. they are sometimes displayed
					// i don't know why.. maybe on clock shifts..
					// how long did the burst take?
					
					ircd::end_burst( $ircdata );
					self::$end_burst = true;
				}
				// here we check if we're recieving an endburst
				
				if ( !self::$end_burst ) 
					self::$nbuffer[] = $ircdata;
				else
					self::$buffer[] = $ircdata;
				// we should really only be processing the data if the burst has finished
				// so we add it to a buffer and process it in each main loop :)
				
				unset( $ircdata, $raw );
				// unset the variables on each process loop
			}
			
			if ( self::$debug && count( self::$debug_data ) > 0 )
			{
				foreach( self::$debug_data as $line => $message )
				{
					if ( trim( $message ) != '' || trim( $message ) != null )
						print "[".date( 'H:i:s', time() )."] ".$message."\r\n";
					// only print if we have something to print
						
					unset( self::$debug_data[$line] );
					// ALWAYS unset it.
				}
			}
			elseif ( self::$debug && count( self::$debug_data ) == 0 )
			{
				self::$debug_data = array();
			}
			// here we output debug data, if there is any.
			
			usleep( 20000 );
			// 50000 breaks /hop and /cycle
			// 40000 is quite slow when handling alot of data
			// 15/20/25 000 has high cpu usage for 10 mins or so, i'm settling at 15000
			// as after about 5 mins the usage drops dramatically, eventually to 0.0, and performance is increased
		}
	}
	
	/*
	* process (private)
	*
	* @params
	* $ircdata - ..
	* $startup - boolean to indicate if we're booting or not.
	*/
	public function process( $ircdata, $startup = false )
	{
		if ( self::$end_burst )
			ircd::ping( $ircdata );
		// pingpong my name is tingtong
	
		self::log_changes( $ircdata, $startup );
		// log peoples hostnames, used for bans etc.
		
		if ( self::max_users( $ircdata ) ) return true;
		// check for max users.
		
		if ( self::flood_check( $ircdata ) ) return true;
		// this just does some checking, this is quite
		// important as it deals with the main anti-flood support
		
		if ( commands::ctcp( $ircdata ) ) return true;
		// ctcp stuff :D
		
		if ( commands::motd( $ircdata ) ) return true;
		// motd
		
		if ( ircd::on_timeset( $ircdata ) && $ircdata[3] == 'FORCE' )
			self::$network_time = $ircdata[2];
		// we're getting a new time, update it
		
		if ( $ircdata[0] == 'ERROR' )
		{
			self::alog( 'ERROR: '.self::get_data_after( $ircdata, 1 ), 'BASIC' );
			self::save_logs();
			ircd::shutdown( 'ERROR', true );
		}
		// act upon ERROR messages.
		
		foreach ( modules::$list as $module => $data )
		{
			if ( $data['type'] == 'core' )
				modules::$list[$module]['class']->main( $ircdata, $startup );
		}	
		// any core modules? humm
			
		foreach ( self::$service_bots as $bot )
			$this->$bot->main( $ircdata, $startup );
		// we hook to each of our bots
	}
	
	/*
	* log_changes
	*
	* @params
	* $ircdata - ..
	*/
	static public function log_changes( $ircdata, $startup = false )
	{
		if ( ircd::on_server( $ircdata ) !== false )
			ircd::handle_on_server( $ircdata );
		// let's us keep track of the linked servers
		
		if ( ircd::on_squit( $ircdata ) !== false )
			ircd::handle_on_squit( $ircdata );
		// let's us keep track of the linked servers
		
		if ( ircd::on_connect( $ircdata ) !== false )
			ircd::handle_on_connect( $ircdata, $startup );
		// log shit on connect, basically the users host etc.
		
		if ( ircd::on_nick_change( $ircdata ) !== false )
			ircd::handle_nick_change( $ircdata, $startup );
		// on nick change, make sure the variable changes too.
		
		if ( ircd::on_quit( $ircdata ) !== false )
			ircd::handle_quit( $ircdata, $startup );
		// on quit.
		
		if ( ircd::on_fhost( $ircdata ) !== false )
			ircd::handle_host_change( $ircdata );
		// on hostname change.
		
		if ( ircd::on_ident_change( $ircdata ) !== false )
			ircd::handle_ident_change( $ircdata );
		// on ident change
		
		if ( ircd::on_gecos_change( $ircdata ) !== false )
			ircd::handle_gecos_change( $ircdata );
		// on realname (gecos) change
		
		if ( ircd::on_mode( $ircdata ) !== false )
			ircd::handle_mode( $ircdata );	
		// on mode
		
		if ( ircd::on_ftopic( $ircdata ) !== false )
			ircd::handle_ftopic( $ircdata );
		// on ftopic
		
		if ( ircd::on_topic( $ircdata ) !== false )
			ircd::handle_topic( $ircdata );	
		// on topic
		
		if ( ircd::on_chan_create( $ircdata ) !== false )
			ircd::handle_channel_create( $ircdata );
		// on channel create
		
		if ( ircd::on_join( $ircdata ) !== false )
			ircd::handle_join( $ircdata );
		// on join
		
		if ( ircd::on_part( $ircdata ) !== false )
			ircd::handle_part( $ircdata );
		// and on part.
		
		if ( ircd::on_kick( $ircdata ) !== false )
			ircd::handle_kick( $ircdata );
		// and on kick.
		
		if ( ircd::on_oper_up( $ircdata ) !== false )
			ircd::handle_oper_up( $ircdata );
		// on oper ups
	}
	
	/*
	* boot_server (private)
	*
	* @params
	* void
	*/
	public function boot_server()
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
	public function post_boot_server()
	{
		if ( self::$capab_start && !self::$end_burst )
			ircd::send_version( array() );
	
		foreach ( self::$service_bots as $bot )
		{
			require( BASEPATH.'/core/services/'.$bot.'.php' );
			$this->$bot = new $bot();
		}
		// start our bots up.
		
		foreach ( self::$config->core_modules as $id => $module )
			modules::load_module( 'core_'.$module, $module.'.core.php' );
		// we load core modules before the bots, incase there
		// is a module that changes an existing function w/e
		
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
		if ( core::$services_account === false )
		{
			self::alog( 'ERROR: service accounts is required, startup halted.', 'BASIC' );
			// log it
			
			self::save_logs();
			// save logs.
			
			ircd::shutdown( 'ERROR: service accounts is required, startup halted.', true );
			// exit
		}
		// services account isn't found, quit out letting them know.
		
		if ( core::$hide_chans === false )
		{
			core::alog( 'WARNING: +I is either not loaded or not available, this is NOT advised.' );
			// alog, don't exit, not needed.
		}
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
		if ( ( $severity & error_reporting() ) == $severity )
		{
			self::save_logs();
			// save logs.
				
			self::alog( 'error: '.trim( $message ).' on line '.$line.' in '.$filepath, 'BASIC' );#
					
			ircd::shutdown( 'ERROR: '.$message, true );
			// exit the program	
		}
		
		return true;
		// don't execute php internal error handler
	}
	
	/*
	* max_users
	*
	* @params
	* $ircdata - ..
	*/
	static public function max_users( $ircdata )
	{
		if ( ircd::on_connect( $ircdata ) !== false )
		{
			if ( count( self::$nicks ) > self::$max_users )
			{
				$update_array = array(
					'max_users'		=>	count( self::$nicks ),
					'max_userstime'	=>	self::$network_time
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
		// if someone has logged in
	}
	
	/*
	* save_logs
	*
	* @params
	*/
	static public function save_logs()
	{
		$filepath = BASEPATH.'/log/services-'.date( 'd-m-Y', self::$network_time ). '.log';
		
		if ( file_exists( $filepath ) ) chmod( $filepath, 0777 );
		// if the file exists, chmod it to 0777
		
		if ( count( self::$log_data ) > 0 )
		{
			foreach( self::$log_data as $line => $message )
			{
				$filemsg = "[".date( 'H:i:s', time() )."] ".$message."\r\n";
					
				if ( !$fp = fopen( $filepath, 'a' ) )
					return false;
					
				fwrite( $fp, $filemsg, strlen( $filemsg ) );
				fclose( $fp );	
				// and we ALSO send it into the logfile
				
				unset( self::$log_data[$line] );
			}
		
		}
		else
		{
			self::$log_data = array();
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
		foreach ( self::$chans as $chan => $data )
		{
			if ( count( self::$chans[$chan]['users'] ) == 0 )
			{
				if ( strstr( core::$config->server->ircd, 'inspircd' ) && strstr( self::$chans[$chan]['modes'], 'P' ) )
					continue;
				// there isnt any users, BUT, does the channel have +P set?
				// if it does, continue;
			
				unset( self::$chans[$chan] );
				// unset it
			}
			// no users, unset chan
		}
	}
	
	/*
	* reset_flood_cache
	*
	* @params
	* void
	*/
	static public function reset_flood_cache()
	{
		foreach ( self::$nicks as $nick => $data )
		{
			self::$nicks[$nick]['commands'] = null;
			self::$nicks[$nick]['floodcmds'] = 0;
			self::$nicks[$nick]['failed_attempts'] = 0;
		}
		// loop though our users, setting everything to false/0/null
		// everything flood related, ofc.
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
	* flood_check
	*
	* @params
	* $ircdata - ..
	*/
	static public function flood_check( $ircdata )
	{
		if ( trim( $ircdata[0] ) == '' )
			return true;
		// the data is empty, omgwtf..
		
		$return = ircd::on_msg( $ircdata );
		if ( $return !== false && $return['target'][0] == '#' && $return['msg'][1] != self::$config->chanserv->fantasy_prefix )
			return true;
		// this is just here to instantly ignore any normal channel messages 
		// otherwise we get lagged up on flood attempts
		
		if ( ircd::on_notice( $ircdata ) !== false )
			return true;
		// and ignore notices, since we shouldnt respond to any 
		// notices what so ever, just saves wasting cpu cycles when we get a notice
		
		if ( $return !== false && $return['target'][0] == '#' )
		{
			if ( self::$config->settings->flood_msgs == 0 || self::$config->settings->flood_time == 0 )
				return false;	
			// check if it's disabled.
			
			$nick = self::get_nick( $ircdata, 0 );
			$time_limit = time() - self::$config->settings->flood_time;
			self::$nicks[$nick]['commands'][] = time();
			$from = self::get_nick( $ircdata, 2 );
			
			if ( self::$nicks[$nick]['ircop'] )
				return false;
			// ignore ircops
			
			$inc = 0;
			foreach ( self::$nicks[$nick]['commands'] as $index => $timestamp )
				if ( $timestamp > $time_limit ) $inc = 1;

			if ( $inc == 1 )
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
					database::insert( 'ignored_users', array( 'who' => '*!*@'.self::$nicks[$nick]['host'], 'time' => core::$network_time, 'temp' => '1' ) );
					timer::add( array( 'core', 'remove_ignore', array( '*!*@'.self::$nicks[$nick]['host'] ) ), 120, 1 );
					// add them to the ignore list.
					// also, add a timer to unset it in 2 minutes.
					
					$message = ( self::$nicks[$nick]['offences'] == 1 ) ? 'This is your first offence' : 'This is your last warning';
					// compose a message.
					
					services::communicate( $from, $nick, operserv::$help->OS_COMMAND_LIMIT_1 );
					services::communicate( $from, $nick, operserv::$help->OS_COMMAND_LIMIT_2, array( 'message' => $message ) );
					self::alog( self::$config->operserv->nick.': Offence #'.self::$nicks[$nick]['offences'].' for '.self::get_full_hostname( $nick ).' being ignored for 2 minutes' );
					self::alog( 'flood_check(): Offence #'.self::$nicks[$nick]['offences'].' for '.self::get_full_hostname( $nick ), 'BASIC' );
					
					return true;
				}
				elseif ( self::$nicks[$nick]['offences'] >= 2 )
				{
					self::alog( self::$config->operserv->nick.': Offence #'.self::$nicks[$nick]['offences'].' for '.self::get_full_hostname( $nick ).' being glined for 10 minutes' );
					self::alog( 'flood_check(): Offence #'.self::$nicks[$nick]['offences'].' for '.self::get_full_hostname( $nick ), 'BASIC' );
					
					ircd::gline( self::$config->operserv->nick, '*@'.self::$nicks[$nick]['oldhost'], 600, 'Flooding services, 10 minute ban.' );
					// third offence, wtf? add a 10 minute gline.
					
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
		if ( !file_exists( BASEPATH.'/core/protocol/'.self::$config->server->ircd.'.php' ) )
		{
			self::alog( 'protocol_init(): failed to initiate protocol module '.self::$config->server->ircd, 'BASIC' );
			
			self::save_logs();
			// force a log save.
		}
		else
		{
			require( BASEPATH.'/core/protocol/'.self::$config->server->ircd.'.php' );
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
		foreach ( self::$config->uplink as $server => $info )
		{
			if ( !$socket = @fsockopen( $info->host, $info->port, $errno, $errstr, self::$config->server->recontime ) )
			{
				self::alog( 'connect(): failed to connect to '.$info->host.':'.$info->port, 'BASIC' );
				// alog.
				
				continue;
				// continue to next uplink (if specified)
			}
			else
			{
				self::alog( 'connect(): established connection to '.$info->uplink.':'.$info->port, 'BASIC' );
				// alog.
				
				self::$config->conn->password = $info->password;
				self::$config->conn->server = $info->uplink;
				self::$config->conn->port = $info->port;
				self::$config->conn->vhost = $info->vhost;
				// we've connected, set our config details up.
				
				stream_set_blocking( $socket, 0 );
				// set this to blocking
				
				return $socket;
			}
			// try and connect to the server.
		}
		
		// effectively we should have already connected providing the uplink
		// info is correct, if we've connected we'll have already returned the
		// socket information, if we haven't we'll be here, and here is where
		// we tell them that we can't connect :D
		
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
		if ( trim( $message ) == '' ) return false;
		// if there is no message at all just bail.
	
		if ( ( isset( self::$config->settings->logchan ) || self::$config->settings->logchan != null ) && $type == 'CHAN' && isset( modules::$list['os_global'] ) )
		{
			ircd::msg( self::$config->global->nick, self::$config->settings->logchan, $message );
			// send the message into the logchan
		}
		// logging is enabled, so send the message into the channel.
		
		if ( ( self::$config->settings->loglevel != 'off' || !isset( self::$config->settings->loglevel ) ) && $type != 'CHAN' )
		{
			if ( self::$config->settings->loglevel == strtolower( $type ) || self::$config->settings->loglevel == 'all' )
			{
				if ( !in_array( $message, self::$log_data ) )
					self::$log_data[] = $message;
			}
		}
		// is logging to file enabled? if so, log to file.
		
		if ( self::$debug && $type != 'CHAN' )
		{
			if ( self::$config->settings->loglevel == strtolower( $type ) || self::$config->settings->loglevel == 'all' )
			{
				if ( !is_resource( self::$socket ) && self::$debug )
					print "[".date( 'H:i:s', time() )."] ".$message."\r\n";
				elseif ( !in_array( $message, self::$debug_data ) )
					self::$debug_data[] = $message;
				// if we're not connected, and in debug mode
				// just send it out, else they wont actually see the message and
				// it'll just end up in the log file
			}
		}
		// debug on?
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
		foreach( self::$nicks as $unick => $uarray )
		{
			if ( strcasecmp( $nick, $unick ) == 0 )
			{
				return $uarray;
			}
		}
		
		return false;
		// i can see this being a bit resourcive on larger networks
		// but bearing in mind it isnt used often, only on fantasy
		// and xcommands, using bans.
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
		{
			unset( $new_ircdata[$i] );
		}
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
		$return .= $days.'d ';
		// days
		
		$hours = floor( $remaining / 3600 );
		$remaining = $remaining - ( $hours * 3600 );
		$return .= $hours.'h ';
		// hours
		
		$mins = floor( $remaining / 60 );
		$remaining = $remaining - ( $mins * 60 );
		$return .= $mins.'m ';
		// minutes
		
		$return .= $remaining.'s';
		// seconds
		
		return $return;
		// return the result.
	}
}

// EOF;