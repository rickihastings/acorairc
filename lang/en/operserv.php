<?php

/*
* Acora IRC Services
* lang/en/operserv.php: OperServ language file (en)
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

$help = (object) array(
	'OS_HELP_PREFIX' 	=> array(
		'=---- {operserv} Help ----=',
		' ',
		'{operserv} is designed to give IRCops more control',
		'over the network, {operserv} offers a range of commands',
		'to help you and your opers manage your network.',
		' ',
	),
	// HELP PREFIX
	
	'OS_HELP_SUFFIX' 	=> array(
		' ',
		'Notice: All commands sent to {operserv} are logged!',
		' ',
		'=---- End of {operserv} Help ----=',
	),
	// HELP SUFFIX
	
	'OS_HELP_GLOBAL_1'		=> '     GLOBAL    - Send a message to all users',
	'OS_HELP_SHUTDOWN_1'	=> '     SHUTDOWN  - Terminate the Services program',
	'OS_HELP_RESTART_1'		=> '     RESTART   - Restart the Services program',
	'OS_HELP_STATS_1'		=> '     STATS     - Show status of Services and network',
	'OS_HELP_SESSION_1'		=> '     SESSION   - Manage session limits',
	'OS_HELP_AKILL_1'		=> '     AKILL     - Manage network autokills',
	'OS_HELP_MODULES_1'		=> '     MODLIST   - List loaded modules',
	'OS_HELP_MODLOAD_1'		=> '     MODLOAD   - Load a module',
	'OS_HELP_MODUNLOAD_1'	=> '     MODUNLOAD - Unload a module',
	'OS_HELP_IGNORE_1'		=> '     IGNORE    - Modify the services ignore list',
	'OS_HELP_LOGONNEWS_1'	=> '     LOGONNEWS - Define messages to be shown to users at connect',
	'OS_HELP_CHANCLEAR_1'	=> '     CHANCLEAR - Remove all users on a specific channel',
	'OS_HELP_REHASH_1'		=> '     REHASH    - Reload the services configuration file',
	'OS_HELP_JUPE_1'		=> '     JUPE      - Jupe a server',
	'OS_HELP_KICK_1'		=> '     KICK      - Kick a user from a channel',
	'OS_HELP_MODE_1'		=> '     MODE      - Change a channel\'s modes',
	'OS_HELP_VHOST_1'		=> '     VHOST     - Manage nick vHosts',
	'OS_HELP_OVERRIDE_1'	=> '     OVERRIDE  - Gives services roots override access',
	// STANDARD HELP MESSAGES
	
	'OS_HELP_GLOBAL_ALL' 		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: GLOBAL hostmask message',
		' ',
		'Allows IRCops to send messages to all users matching the',
		'hostmask on the network. The message will be sent from the',
		'Global Messenger. The hostmask shoudld be in the format of',
		'nick!user@host',
		' ',
		'Requires "global_op" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// GLOBAL
	
	'OS_HELP_OVERRIDE_ALL' 		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: OVERRIDE [ON|OFF]',
		' ',
		'Gives services roots the ability to override all channel access checks.',
		'This command should be used if your ircd has override options disabled,',
		'and you need to change channel specific settings or deal with a takeover',
		'etc. It is recommended to turn this off once finished with, as it can easily',
		'be constructed as a "power trip".',
		' ',
		'Requires "root" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// SHUTDOWN
	
	'OS_HELP_SHUTDOWN_ALL' 		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: SHUTDOWN',
		' ',
		'Causes the services program to shutdown.',
		' ',
		'Requires "root" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// SHUTDOWN
	
	'OS_HELP_RESTART_ALL' 		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: RESTART',
		' ',
		'Causes the services program to reboot.',
		' ',
		'Requires "root" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// RESTART
	
	'OS_HELP_STATS_ALL' 		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: STATS [UPTIME|NETWORK|OPERS]',
		' ',
		'Shows network and program statistics based on the second',
		'parameter. UPTIME will show technical information about',
		'the services programs performance. NETWORK will show network',
		'recorded information, such as number of users and channels.',
		'OPERS will show a list of all opers, when they connected and',
		'their full hostmask.',
		'=---- End of {operserv} Help ----=',
	),
	// STATS
	
	'OS_HELP_SESSION_ALL' 		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: SESSION ADD ip address limit description',
		'        SESSION DEL ip address',
		'        SESSION LIST',
		' ',
		'Allows management of session limits, if the ircd does not',
		'support propagating IP addresses at all, this module is',
		'essentially useless. Default limit can be set in services.conf',
		'and limits can be raised by using SESSION ADD. Limit cannot',
		'be 0.',
		' ',
		'Warnings are displayed for IP Addresses with multiple clients',
		'in the log channel. When the limit is reached any new clients',
		'are killed.',
		' ',
		'Requires "global_op" oper priv to ADD/DEL.',
		'=---- End of {operserv} Help ----=',
	),
	// SESSION
	
	'OS_HELP_AKILL_ALL' 		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: AKILL ADD hostmask expiry reason',
		'        AKILL DEL hostmask',
		'        AKILL LIST',
		' ',
		'Allows management of network autokills, hostmask can be in the',
		'format of nick!user@host, wildcards supported. Expiry time and',
		'reasons are not required although are recommended. If no expiry',
		'time is added the ban will never expire. Expiry times should be',
		'in the format of 1d2h2m, equating to 1 day 2 hours 2 minutes.',
		' ',
		'Requires "global_op" oper priv to ADD/DEL.',
		'=---- End of {operserv} Help ----=',
	),
	// AKILL
	
	'OS_HELP_MODLIST_ALL'		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: MODLIST',
		' ',
		'Lists all currently loaded modules.',
		'=---- End of {operserv} Help ----=',
	),
	// MODLIST
	
	'OS_HELP_MODLOAD_ALL'		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: MODLOAD module',
		' ',
		'This command loads and initiates the requested',
		'module from the modules directory.',
		' ',
		'Requires "root" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// MODLIST
	
	'OS_HELP_MODUNLOAD_ALL'		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: MODUNLOAD module',
		' ',
		'This command unloads and destructs the requested',
		'module. The module can be reloaded with the',
		'MODLOAD command.',
		' ',
		'Requires "root" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// MODLIST
	
	'OS_HELP_IGNORE_ALL'		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: IGNORE ADD { nickname | hostmask }',
		'        IGNORE DEL { nickname | hostmask }',
		'        IGNORE LIST',
		'        IGNORE CLEAR',
		' ',
		'Allows Services Admins to make services ignore a',
		'nickname or hostmask. Everything sent from an ignored',
		'nickname or hostmask will be ignored, help commands,',
		'commands and fantasy commands. When adding a hostmask',
		'it should be in the format (nick!user@host).',
		' ',
		'Requires "global_op" oper priv ADD/DEL.',
		'=---- End of {operserv} Help ----=',
	),
	// IGNORE
	
	'OS_HELP_LOGONNEWS_ALL'		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: LOGONNEWS ADD title message',
		'        LOGONNEWS DEL title',
		'        LOGONNEWS LIST',
		' ',
		'Edits or displays the list of logon news messages. When a',
		'user connects to the network, these messages will be sent',
		'to them. If there are more than three news messages, only',
		'the three most recent will be sent to avoid flooding the user.',
		' ',
		'Requires "global_op" oper priv ADD/DEL.',
		'=---- End of {operserv} Help ----=',
	),
	// LOGONNEWS
	
	'OS_HELP_CHANCLEAR_ALL'		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: CHANCLEAR [KICK|KILL|BAN] channel reason',
		' ',
		'CHANCLEAR allows admins to clear a channel in one of',
		'three ways, KICK which kicks the user and sets a +b on',
		'their hostmask, KILL which issues a server KILL to the',
		'users, or BAN which sets a one week ban against all the',
		'users in that channel.',
		' ',
		'All IRCops are ignored from this, services staff have',
		'to be opered up for this command to ignore them.',
		' ',
		'Requires "global_op" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// CHANCLEAR
	
	'OS_HELP_REHASH_ALL'		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: REHASH',
		' ',
		'Reloads the services configuration file.',
		' ',
		'Requires "root" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// REHASH
	
	'OS_HELP_JUPE_ALL'		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: JUPE server numeric',
		' ',
		'Creates a "fake" server linked under the services',
		'server. Useful for disallowing servers to relink.',
		'You should consult your ircd documentation when entering',
		'a numeric value, note that some ircds don\'t take this',
		'into account so a dummy value can be entered.',
		' ',
		'Requires "local_op" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// JUPE
	
	'OS_HELP_KICK_ALL'		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: KICK channel user reason',
		' ',
		'Allows staff to kick a user from any channel.',
		'Parameters are the same as for the standard /KICK',
		'command. The kick message will have the nickname of the',
		'IRCop sending the KICK command prepended.',
		' ',
		'Requires "local_op" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// KICK
	
	'OS_HELP_MODE_ALL'		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: MODE channel modes',
		' ',
		'Allows staff to set channel modes for any channel.',
		'Parameters are the same as for the standard /MODE',
		'command.',
		' ',
		'Requires "local_op" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// MODE
	
	'OS_HELP_VHOST_ALL'		=> array(
		'=---- {operserv} Help ----=',
		'Syntax: VHOST SET nickname hostname',
		'        VHOST DEL nickname',
		'        VHOST LIST limit [PENDING]',
		'        VHOST APPROVE nickname',
		'        VHOST REJECT nickname',
		' ',
		'Sets the vhost for the given nickname to that of the given',
		'hostname. The hostname can only contain A-Za-z0-9 dashes',
		'and dots. These can only be set by people with {operserv}',
		'access. When a user with a vhost identifies the vhost',
		'will automatically be assigned for them.',
		' ',
		'If your ircd supports changing ident you can specify',
		'an username along with the host, for example user@host.',
		' ',
		'The pending parameter lists vhosts waiting to be approved. The',
		'APPROVE and REJECT commands can be used to approve',
		'and decline requested vhosts.',
		' ',
		'The limit parameter for LIST can be used to grab a',
		'certain amount of results, for instance starting at result',
		'0, and returning the next 30 results you\'d do 0-30 in',
		'the limit parameter. Starting at result 30 and returning',
		'10 would be 30-10. The basic format for limit is',
		'offset-max.',
		' ',
		'Requires "local_op" oper priv.',
		'=---- End of {operserv} Help ----=',
	),
	// VHOST
	
	'OS_INVALID_SYNTAX' 	=> 	'Invalid syntax: /msg {operserv} HELP for more information',
	'OS_INVALID_SYNTAX_RE'	=> 	'Invalid syntax: /msg {operserv} HELP {help} for more information',
	
	'OS_ACCESS_DENIED' 		=> 	'You do not have access to do this',
	'OS_DENIED_ACCESS'		=>	'{operserv} is not available to you',
	'OS_CHAN_INVALID'		=> 	'{chan} doesn\'t exist',	
	'OS_UNREGISTERED_NICK'	=>	'{nick} isn\'t a registered nickname',
	'OS_COMMAND_LIMIT_1'	=>	'You have triggered services flood protection',
	'OS_COMMAND_LIMIT_2'	=>	'{message}, you will be ignored for 2 minutes',
	'OS_ISNT_REGISTERED'	=>	'{nick} isn\'t registered',
	'OS_INVALID_HOSTNAME'	=>	'Invalid characters in hostname',
	'OS_JUPE_1'				=>	'{server} is already on the network',
	'OS_JUPE_2'				=>	'{server} has been juped',
	// standard messages
	'OS_VHOST_SET'		=> 'vHost for {nick} set to {host}',
	'OS_VHOST_DELETED'	=> 'vHost for {nick} deleted',
	'OS_VHOST_LIST_T'	=> 'Entry  Nickname          vHost',
	'OS_VHOST_LIST_D'	=> '-----  ----------------  -----',
	'OS_VHOST_LIST_R'	=> '{num} {nick}[{info}]',
	'OS_VHOST_LIST_B'	=> 'End of list - {num}/{total} vhosts(s) shown',
	'OS_NO_VHOST'		=> '{nick} doesn\'t have a vhost set',
	'OS_VHOST_NO_REQ'	=> '{nick} has not requested a vHost',
	'OS_VHOST_APPROVED'	=> 'vHost for {nick} approved',
	'OS_VHOST_REJECTED'	=> 'vHost for {nick} rejected',
	// vhosts
	'OS_STATS_U_1'		=> '         Uptime: {time}',
	'OS_STATS_U_2'		=> '   Memory usage: {memory} ({real} bytes)',
	'OS_STATS_U_3'		=> '  Incoming data: {memory} ({real} bytes)',
	'OS_STATS_U_4'		=> '  Outgoing data: {memory} ({real} bytes)',
	'OS_STATS_U_5'		=> 'Lines Processed: {lines}',
	'OS_STATS_U_6'		=> '     Lines Sent: {lines}',
	'OS_STATS_U_7'		=> '   Burst Length: {time}',
	// stats UPTIME
	'OS_STATS_N_1'		=> '   Network Name: {network}',
	'OS_STATS_N_2'		=> '        Version: {version}',
	'OS_STATS_N_3'		=> '      Max users: {users}',
	'OS_STATS_N_4'		=> '  Current users: {users}',
	'OS_STATS_N_5'		=> '  Current chans: {chans}',
	// stats NETWORK
	'OS_STATS_O_T'		=> 'Entry  Hostmask                                          Details',
	'OS_STATS_O_D'		=> '-----  ------------------------------------------------  -------',
	'OS_STATS_O_L'		=> '{num} {host}[{time}]{privs}',
	'OS_STATS_O_B'		=> 'End of list - {num} opers online',
	// stats OPERS
	'OS_MODLIST_TOP'	=> 'Module           Version       Module Info',
	'OS_MODLIST_DLM'	=> '---------------  ------------  -----------',
	'OS_MODLIST_3'		=> '{name}[{version}]       [{author}/{extra}/{type}]',
	'OS_MODLIST_BTM'	=> 'End of list - {num} loaded modules',
	'OS_MODLOAD_1'		=> 'Unable to load {name}',
	'OS_MODLOAD_2'		=> 'Loaded {name} [{version}] [{extra}]',
	'OS_MODLOAD_3'		=> '{name} is already loaded',	
	'OS_MODUNLOAD_1'	=> '{name} isn\'t loaded',
	'OS_MODUNLOAD_2'	=> 'Unable to unload {name}',
	'OS_MODUNLOAD_3'	=> 'Unloaded {name} [{version}] [{extra}]',
	// modules
	'OS_IGNORE_CLEARED'	=> 'Services ignore list cleared, {users} removed',
	'OS_IGNORE_ADD'		=> '{nick} has been added to the services ignore list',
	'OS_IGNORE_DEL'		=> '{nick} has been deleted from the services ignore list',
	'OS_IGNORE_NONE'	=> '{nick} isn\'t on the services ignore list',
	'OS_IGNORE_LIST_T'	=> 'Entry  Nickname/Hostmask                                 Date Added',
	'OS_IGNORE_LIST_D'	=> '-----  ------------------------------------------------  ----------',
	'OS_IGNORE_LIST'	=> '{num} {nick}[{time}]',
	'OS_IGNORE_LIST_B'	=> 'End of list - {num} records',
	'OS_IGNORE_EXISTS'	=> '{nick} is already on the services ignore list',	
	// ignore
	'OS_LOGON_NEWS_1'	=> '[{title}] Notice from {user}, posted {date}:',
	'OS_LOGON_NEWS_2'	=> '{message}',
	'OS_LOGONNEWS_ADD'	=> 'Added new logon news item',
	'OS_LOGONNEWS_DEL'	=> 'Deleted "{title}" from logon news',
	'OS_LOGONNEWS_EMPTY'=> 'There are no logon news messages',
	'OS_LOGONNEWS_NONE'	=> 'No matching logon news messages were found',
	'OS_LOGONNEWS_EXISTS'	=> 'There is already a message with the same title',
	// logonnews
	'OS_GLOBAL_INVALID'	=> 'You have specified an invalid mask, the mask should be in the format of nick!user@host',
	// global
	'OS_OVERRIDE_ON'	=> 'Override mode has been ENABLED for your nickname',
	'OS_OVERRIDE_OFF'	=> 'Override mode has been DISABLED for your nickname',
	'OS_OVERRIDE_IS_ON'	=> 'You already have override mode enabled',
	'OS_OVERRIDE_IS_OFF'=> 'You don\'t have override mode enabled',
	// override
	'OS_EXCP_NOLIMIT'	=> 'You cannot set a 0 limit on an ip address',
	'OS_EXCP_ADD'		=> 'Session limit for {ip_addr} set to {limit}',
	'OS_EXCP_EXISTS'	=> 'Session limit for {ip_addr} already exists',
	'OS_EXCP_DEL'		=> 'Session limit for {ip_addr} removed',
	'OS_EXCP_NOEXISTS'	=> 'No session limit exists for {ip_addr}',
	'OS_EXCP_LIST_T'	=> 'Entry  IP Address       Limit  Details',
	'OS_EXCP_LIST_D'	=> '-----  ---------------  -----  -------',
	'OS_EXCP_LIST1'		=> '1      *                {limit} [Added by: services.conf]',
	'OS_EXCP_LIST'		=> '{num} {ip_addr}  {limit} [Added by: {nick}] [Reason: {desc}]',
	'OS_EXCP_LIST_B'	=> 'End of list - {num} exception(s)',
	// session
	'OS_AKILL_ADD'		=> 'Auto kill added for {hostname} set to expire in {expire}',
	'OS_AKILL_EXISTS'	=> 'Auto kill for {hostname} already exists',
	'OS_AKILL_DEL'		=> 'Auto kill for {hostname} removed',
	'OS_AKILL_NOEXISTS'	=> 'No auto kill exists for {hostname}',
	'OS_AKILL_LIST_T'	=> 'Entry  Hostmask                                          Details',
	'OS_AKILL_LIST_D'	=> '-----  ------------------------------------------------  -------',
	'OS_AKILL_LIST'		=> '{num} {hostname}[Added by: {nick}] [Expires in: {expire}] [Reason: {desc}]',
	'OS_AKILL_LIST_B'	=> 'End of list - {num} exception(s)',
	// akill
);

// EOF;