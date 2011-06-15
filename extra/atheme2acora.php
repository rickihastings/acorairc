<?php

/*
* Acora IRC Services
* extra/atheme2acora.php: Atheme to Acora database convert.
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

// NOTE
// enc_none MUST be used in anope for this to work
// if you have ever used an encryption method for
// anope this wont work.

$mysql_host		= 'localhost';
$mysql_user 	= 'root';
$mysql_pass 	= '';
// these values should be pretty self explanitory

$atheme_db		= '/home/ricki/atheme/etc/services.db';

$acora_db		= 'services';
$acora_prefix	= 'system_';
// the value acora_db should already be created and
// the tables should already be there, for info on how to
// setup the tables see docs/INSTALL

$data_users			= array();
$data_user_flags	= array();
$data_channels		= array();
$data_chan_levels	= array();
$data_chan_flags	= array();
// ignore these above values

function get_data_after( $ircdata, $number )
{
	$new_ircdata = $ircdata;

	for ( $i = 0; $i < $number; $i++ )
		unset( $new_ircdata[$i] );

	$new = implode( ' ', $new_ircdata );

	return trim( $new );
}

$handle = fopen( $atheme_db, 'r' );
$contents = explode( "\n", fread( $handle, filesize( $atheme_db ) ) );
fclose( $handle );
// open file handle

$uid = 0;
foreach ( $contents as $line => $data )
{
	$split_data = explode( ' ', $data );
	
	if ( $split_data[0] != 'MU' && $split_data[0] != 'MDU' && $split_data[0] != 'MC' && $split_data[0] != 'CA' && $split_data[0] != 'MDC' )
		continue;
	// discard shit we don't need.
	
	if ( $split_data[0] == 'MU' )
	{
		++$uid;
		
		$salt = '';
			
		for ( $i = 0; $i < 8; $i++ )
		{
			$possible = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$salt .= substr( $possible, rand( 0, strlen( $possible ) - 1 ), 1 );
		}
		
		$data_users[$split_data[1]]['id'] = $uid;
		$data_users[$split_data[1]]['display'] = $split_data[1];
		$data_users[$split_data[1]]['timestamp'] = $split_data[5];
		$data_users[$split_data[1]]['last_timestamp'] = $split_data[4];
		$data_users[$split_data[1]]['last_hostmask'] = $split_data[1].'!@ircnode.org';
		$data_users[$split_data[1]]['email'] = $split_data[3];
		$data_users[$split_data[1]]['validated'] = 1;
		$data_users[$split_data[1]]['pass'] = sha1( $split_data[2].$salt );
		$data_users[$split_data[1]]['salt'] = $salt;
		
		$data_user_flags[] = array(
			'nickname' => $split_data[1],
			'flags' => 'Se',
			'email' => $split_data[3],
		);
	}
	// if MN, which are user records
	
	if ( $split_data[0] == 'MDU' && $split_data[2] == 'private:host:vhost' )
		$data_users[$split_data[1]]['last_hostmask'] = $split_data[3];
	if ( $split_data[0] == 'MDU' && $split_data[2] == 'private:usercloak' )
		$data_users[$split_data[1]]['vhost'] = $split_data[3];
	// MDU, vhosts and shit!
	
	// MC channel register
	$cid = 0;
	if ( $split_data[0] == 'MC' )
	{
		++$cid;
		
		$data_channels[$split_data[1]]['id'] = $cid;
		$data_channels[$split_data[1]]['channel'] = $split_data[1];
		$data_channels[$split_data[1]]['timestamp'] = $split_data[2];
		$data_channels[$split_data[1]]['last_timestamp'] = $split_data[3];
		
		$data_chan_flags[$split_data[1]] = array(
			'channel' => $split_data[1],
			'flags' => 'FSKd',
			'desc' => 'none',
		);
	}
	
	if ( $split_data[0] == 'MDC' && $split_data[2] == 'private:topic:setter' )
		$data_channels[$split_data[1]]['topic_setter'] = $split_data[3];
	if ( $split_data[0] == 'MDC' && $split_data[2] == 'private:topic:text' )
		$data_channels[$split_data[1]]['topic'] = get_data_after( $split_data, 3 );
	if ( $split_data[0] == 'MDC' && $split_data[2] == 'url' )
	{
		$data_chan_flags[$split_data[1]]['url'] = $split_data[3];
		$data_chan_flags[$split_data[1]]['flags'] = $data_chan_flags[$split_data[1]]['flags'] . 'u';
	}
	if ( $split_data[0] == 'MDC' && $split_data[2] == 'private:entrymsg' )
	{
		$data_chan_flags[$split_data[1]]['welcome'] = $split_data[3];
		$data_chan_flags[$split_data[1]]['flags'] = $data_chan_flags[$split_data[1]]['flags'] . 'w';
	}
	if ( $split_data[0] == 'MDC' && $split_data[2] == 'email' )
	{
		$data_chan_flags[$split_data[1]]['email'] = $split_data[3];
		$data_chan_flags[$split_data[1]]['flags'] = $data_chan_flags[$split_data[1]]['flags'] . 'e';
	}
	// MDC, channel info, such as topics and settings
	
	if ( $split_data[0] == 'CA' )
	{
		$flags = '';
		
		if ( strpos( $split_data[3], '+AFORafhioqrstv' ) !== false )
			$flags = 'Fqaosrft';
		if ( strpos( $split_data[3], '+AOafhiorstv' ) !== false )
			$flags = 'aosrft';
		if ( strpos( $split_data[3], '+AOhiortv' ) !== false )
			$flags = 'ort';
		if ( strpos( $split_data[3], '+AHVhtv' ) !== false )
			$flags = 'ht';	
		if ( strpos( $split_data[3], '+AV' ) !== false )
			$flags = 'v';
		// if we've found +F replace with qaosrft
	
		$data_chan_levels[] = array(
			'channel' => $split_data[1],
			'target' => $split_data[2],
			'flags' => $flags,
		);
	}
	// CA channel access
}
// loop data

print count( $data_users ) . "\r\n";
print count( $data_user_flags ) . "\r\n";
print count( $data_channels ) . "\r\n";
print count( $data_chan_flags ) . "\r\n";
print count( $data_chan_levels ) . "\r\n";

/*mysql_close( $dblink );
/*mysql_close( $dblink );
// close our connection to the anope datbase
$dblink = mysql_connect( $mysql_host, $mysql_user, $mysql_pass );
mysql_select_db( $acora_db, $dblink );
// connect to the acora database.

foreach ( $data_users as $user => $array )
{
	mysql_query( "INSERT INTO `".$acora_prefix."users` 
	(`id`,`display`,`timestamp`,`last_timestamp`,`last_hostmask`,`pass`,`salt`,`email`,`url`,`validated`) 
	VALUES('".$array['id']."','".$array['display']."','".$array['timestamp']."','".$array['last_timestamp']."','".$array['last_hostmask']."','".$array['pass']."','".$array['salt']."','".$array['email']."','".$array['url']."','".$array['validated']."')" );
}
// insert our users

foreach ( $data_user_flags as $user => $array )
{
	mysql_query( "INSERT INTO `".$acora_prefix."users_flags`
	(`nickname`,`flags`,`url`,`email`)
	VALUES('".$array['nickname']."','".$array['flags']."','".$array['url']."','".$array['email']."')" );
}
// insert our access

foreach ( $data_channels as $channel => $array )
{
	mysql_query( "INSERT INTO `".$acora_prefix."chans`
	(`id`,`channel`,`timestamp`,`last_timestamp`,`topic`,`topic_setter`,)
	VALUES('".$array['id']."','".$array['channel']."','".$array['timestamp']."','".$array['last_timestamp']."','".$array['topic']."','".$array['topic_setter']."')" );
}
// insert our channels

foreach ( $data_chan_levels as $channel => $array )
{
	mysql_query( "INSERT INTO `".$acora_prefix."chans_levels`
	(`channel`,`target`,`flags`)
	VALUES('".$array['channel']."','".$array['target']."','".$array['flags']."')" );
}
// insert our access

foreach ( $data_chan_flags as $channel => $array )
{
	mysql_query( "INSERT INTO `".$acora_prefix."chans_flags`
	(`channel`,`flags`,`desc`,`url`,`email`,`welcome`)
	VALUES('".$array['channel']."','".$array['flags']."','".$array['desc']."','".$array['url']."','".$array['email']."','".$array['welcome']."')" );
}
// insert our access

mysql_close( $dblink );*/

// EOF;