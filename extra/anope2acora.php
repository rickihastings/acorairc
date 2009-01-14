<?php

/*
* Acora IRC Services
* extra/anope2acora.php: Anope to Acora database convert.
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

// NOTE
// enc_none MUST be used in anope for this to work
// if you have ever used an encryption method for
// anope this wont work.

$mysql_host		= 'localhost';
$mysql_user 	= 'root';
$mysql_pass 	= '';
// these values should be pretty self explanitory

$anope_db		= 'ircnode_services';
$anope_prefix	= 'anope_';

$acora_db		= 'services';
$acora_prefix	= 'system_';
// the value acora_db should already be created and
// the tables should already be there, for info on how to
// setup the tables see docs/INSTALL

$data_users			= array();
$data_user_flags	= array();
$data_chans			= array();
$data_chan_levels	= array();
$data_chan_flags	= array();
// ignore these above values

$dblink = mysql_connect( $mysql_host, $mysql_user, $mysql_pass );
mysql_select_db( $anope_db, $dblink );
// connect to the anope database.

$get_users = mysql_query( "SELECT * FROM `".$anope_prefix."ns_alias` ORDER by `time_registered` ASC" );

$uid = 0;
while ( $users = mysql_fetch_array( $get_users ) )
{
	$uid++;
	$data_users[$users['display']]['id'] = $uid;
	$data_users[$users['display']]['display'] = $users['display'];
	$data_users[$users['display']]['timestamp'] = $users['time_registered'];
	$data_users[$users['display']]['last_timestamp'] = $users['last_seen'];
	$data_users[$users['display']]['last_hostmask'] = $users['display'].'!'.$users['last_usermask'];
}
// anope has 2 tables for users, one for aliases, one for groups w/e
// this one sets up the aliases

$get_users_core = mysql_query( "SELECT * FROM `".$anope_prefix."ns_core`" );

while ( $users_core = mysql_fetch_array( $get_users_core ) )
{
	$salt = '';
		
	for ( $i = 0; $i < 8; $i++ )
	{
		$possible = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$salt .= substr( $possible, rand( 0, strlen( $possible ) - 1 ), 1 );
	}
	
	$data_users[$users_core['display']]['pass'] = sha1( $users_core['pass'].$salt );
	$data_users[$users_core['display']]['salt']	= $salt;
	$data_users[$users_core['display']]['email'] = $users_core['email'];
	$data_users[$users_core['display']]['url'] = $users_core['url'];
	$data_users[$users_core['display']]['validated'] = $users_core['active'];
	
	$data_user_flags[] = array(
		'nickname' => $users_core['display'],
		'flags' => 'Seu',
		'email' => $users_core['email'],
		'url' => $users_core['url'],
	);
}
// this one combines the both into one array, soon to be a table

$get_channels = mysql_query( "SELECT * FROM `".$anope_prefix."cs_info` ORDER by `time_registered` ASC" );

$cid = 0;
while ( $channels = mysql_fetch_array( $get_channels ) )
{
	$cid++;
	$data_channels[$channels['name']]['id'] = $cid;
	$data_channels[$channels['name']]['channel'] = $channels['name'];
	$data_channels[$channels['name']]['founder'] = $data_users[$channels['founder']]['id'];
	$data_channels[$channels['name']]['timestamp'] = $channels['time_registered'];
	$data_channels[$channels['name']]['last_timestamp'] = $channels['last_used'];
	$data_channels[$channels['name']]['topic'] = $channels['last_topic'];
	$data_channels[$channels['name']]['topic_setter'] = $channels['last_topic_setter'];
	
	$data_chan_flags[] = array(
		'channel' => $channels['name'],
		'flags' => 'FSGKduew',
		'desc' => $channels['descr'],
		'url' => $channels['url'],
		'email' => $channels['email'],
		'welcome' => $channels['entry_message'],
	),
	
	$data_chan_levels[] = array(
		'channel' => $channels['name'],
		'target' => $channels['founder'],
		'flags' => 'qaosrft',
	);
}
// we get the channels here, also setting up the access record
// for the found

$get_access = mysql_query( "SELECT * FROM `".$anope_prefix."cs_access`" );

while ( $access = mysql_fetch_array( $get_access ) )
{
	if ( $access['level'] == '10' )
	{
		$level = 'aosrft';
		$priority = 4;
	}
	elseif ( $access['level'] == '5' )
	{
		$level = 'ort';
		$priority = 3;
	}
	elseif ( $access['level'] == '4' )
	{
		$level = 'ht';
		$priority = 2;
	}
	elseif ( $access['level'] == '3' )
	{
		$level = 'v';
		$priority = 1;
	}
	
	$data_chan_levels[] = array(
		'channel' => $access['channel'],
		'target' => $access['display'],
		'flags' => $level,
	);
}
// here we get all the access records, other than the founder
// cause anope doesn't store the founders access in the access
// table.

mysql_close( $dblink );
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
	(`nickname`,`flags`,`url`,`email`,`msn`)
	VALUES('".$array['nickname']."','".$array['flags']."','".$array['url']."','".$array['email']."','".$array['msn']."')" );
}
// insert our access

foreach ( $data_channels as $channel => $array )
{
	mysql_query( "INSERT INTO `".$acora_prefix."chans`
	(`id`,`channel`,`founder`,`timestamp`,`last_timestamp`,`topic`,`topic_setter`,)
	VALUES('".$array['id']."','".$array['channel']."','".$array['founder']."','".$array['timestamp']."','".$array['last_timestamp']."','".$array['topic']."','".$array['topic_setter']."')" );
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

mysql_close( $dblink );

// EOF;