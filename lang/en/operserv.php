<?php

/*
* Acora IRC Services
* lang/en/operserv.php: OperServ language file (en)
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

$help = (object) array(
	'OS_HELP_PREFIX' 	=> array(
		'=---- OperServ Help ----=',
		' ',
		'OperServ is designed to give IRCops more control',
		'over the network, OperServ offers a range of commands',
		'to help you and your opers manage your network.',
		' ',
	),
	// HELP PREFIX
	
	'OS_HELP_SUFFIX' 	=> array(
		' ',
		'Notice: All commands sent to OperServ are logged!',
		' ',
		'=---- End of OperServ Help ----=',
	),
	// HELP SUFFIX
	
	'OS_HELP_GLOBAL_1'		=> '     GLOBAL    - Send a message to all users',
	'OS_HELP_SHUTDOWN_1'	=> '     SHUTDOWN  - Terminate the Services program',
	'OS_HELP_RESTART_1'		=> '     RESTART   - Restart the Services program',
	'OS_HELP_STATS_1'		=> '     STATS     - Show status of Services and network',
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
	// STANDARD HELP MESSAGES
	
	'OS_HELP_GLOBAL_ALL' 		=> array(
		'=---- OperServ Help ----=',
		'Syntax: GLOBAL message',
		' ',
		'Allows IRCops to send messages to all users on the network.',
		'The message will be sent from the Global Messenger.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of OperServ Help ----=',
	),
	// SHUTDOWN
	
	'OS_HELP_SHUTDOWN_ALL' 		=> array(
		'=---- OperServ Help ----=',
		'Syntax: SHUTDOWN',
		' ',
		'Causes the services program to shutdown.',
		' ',
		'Command limited to Services Roots.',
		'=---- End of OperServ Help ----=',
	),
	// SHUTDOWN
	
	'OS_HELP_RESTART_ALL' 		=> array(
		'=---- OperServ Help ----=',
		'Syntax: RESTART',
		' ',
		'Causes the services program to reboot.',
		' ',
		'Command limited to Services Roots.',
		'=---- End of OperServ Help ----=',
	),
	// RESTART
	
	'OS_HELP_STATS_ALL' 		=> array(
		'=---- OperServ Help ----=',
		'Syntax: STATS [UPTIME|NETWORK|OPERS]',
		' ',
		'Shows network and program statistics based on the second',
		'parameter. UPTIME will show technical information about',
		'the services programs performance. NETWORK will show network',
		'recorded information, such as number of users and channels.',
		'OPERS will show a list of all opers, when they connected and',
		'their full hostmask.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of OperServ Help ----=',
	),
	// STATS
	
	'OS_HELP_MODLIST_ALL'		=> array(
		'=---- OperServ Help ----=',
		'Syntax: MODLIST',
		' ',
		'Lists all currently loaded modules.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of OperServ Help ----=',
	),
	// MODLIST
	
	'OS_HELP_MODLOAD_ALL'		=> array(
		'=---- OperServ Help ----=',
		'Syntax: MODLOAD module',
		' ',
		'This command loads and initiates the requested',
		'module from the modules directory.',
		' ',
		'Command limited to Services Roots.',
		'=---- End of OperServ Help ----=',
	),
	// MODLIST
	
	'OS_HELP_MODUNLOAD_ALL'		=> array(
		'=---- OperServ Help ----=',
		'Syntax: MODUNLOAD module',
		' ',
		'This command unloads and destructs the requested',
		'module. The module can be reloaded with the',
		'MODLOAD command.',
		' ',
		'Command limited to Services Roots.',
		'=---- End of OperServ Help ----=',
	),
	// MODLIST
	
	'OS_HELP_IGNORE_ALL'		=> array(
		'=---- OperServ Help ----=',
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
		'Command limited to IRC Operators.',
		'=---- End of OperServ Help ----=',
	),
	// IGNORE
	
	'OS_HELP_LOGONNEWS_ALL'		=> array(
		'=---- OperServ Help ----=',
		'Syntax: LOGONNEWS ADD title message',
		'        LOGONNEWS DEL title',
		'        LOGONNEWS LIST',
		' ',
		'Edits or displays the list of logon news messages. When a',
		'user connects to the network, these messages will be sent',
		'to them. If there are more than three news messages, only',
		'the three most recent will be sent to avoid flooding the user.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of OperServ Help ----=',
	),
	// LOGONNEWS
	
	'OS_HELP_CHANCLEAR_ALL'		=> array(
		'=---- OperServ Help ----=',
		'Syntax: CHANCLEAR [KICK|KILL|GLINE] channel reason',
		' ',
		'CHANCLEAR allows admins to clear a channel in one of',
		'three ways, KICK which kicks the user and sets a +b on',
		'their hostmask, KILL which issues a server KILL to the',
		'users, or GLINE which sets a one week network ban against',
		'all the users in that channel.',
		' ',
		'All IRCops are ignored from this, services staff have',
		'to be opered up for this command to ignore them.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of OperServ Help ----=',
	),
	// CHANCLEAR
	
	'OS_HELP_REHASH_ALL'		=> array(
		'=---- OperServ Help ----=',
		'Syntax: REHASH',
		' ',
		'Reloads the services configuration file.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of OperServ Help ----=',
	),
	// REHASH
	
	'OS_HELP_JUPE_ALL'		=> array(
		'=---- OperServ Help ----=',
		'Syntax: JUPE server numeric',
		' ',
		'Creates a "fake" server linked under the services',
		'server. Useful for disallowing servers to relink.',
		'You should consult your ircd documentation when entering',
		'a numeric value, note that some ircds don\'t take this',
		'into account so a dummy value can be entered.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of OperServ Help ----=',
	),
	// JUPE
	
	'OS_HELP_KICK_ALL'		=> array(
		'=---- OperServ Help ----=',
		'Syntax: KICK channel user reason',
		' ',
		'Allows staff to kick a user from any channel.',
		'Parameters are the same as for the standard /KICK',
		'command. The kick message will have the nickname of the',
		'IRCop sending the KICK command prepended.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of OperServ Help ----=',
	),
	// KICK
	
	'OS_HELP_MODE_ALL'		=> array(
		'=---- OperServ Help ----=',
		'Syntax: MODE channel modes',
		' ',
		'Allows staff to set channel modes for any channel.',
		'Parameters are the same as for the standard /MODE',
		'command.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of OperServ Help ----=',
	),
	// MODE
	
	'OS_HELP_VHOST_ALL'		=> array(
		'=---- OperServ Help ----=',
		'Syntax: VHOST SET nickname hostname',
		'        VHOST DEL nickname',
		'        VHOST LIST limit',
		' ',
		'Sets the vhost for the given nickname to that of the given',
		'hostname. The hostname can only contain A-Za-z0-9 dashes',
		'and dots. These can only be set by people with OperServ',
		'access. When a user with a vhost identifies the vhost',
		'will automatically be assigned for them.',
		' ',
		'If your ircd supports changing ident you can specify',
		'an username along with the host, for example user@host.',
		' ',
		'The limit parameter for LIST can be used to grab a',
		'certain amount of results, for instance starting at result',
		'0, and returning the next 30 results you\'d do 0-30 in',
		'the limit parameter. Starting at result 30 and returning',
		'10 would be 30-10. The basic format for limit is',
		'offset-max.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of OperServ Help ----=',
	),
	// VHOST
	
	'OS_INVALID_SYNTAX' 	=> 'Invalid syntax: /msg OperServ HELP for more information',
	'OS_INVALID_SYNTAX_RE'	=> 'Invalid syntax: /msg OperServ HELP {help} for more information',
	
	'OS_DENIED_ACCESS' 		=> 'OperServ is not available to you',
	'OS_ACCESS_DENIED' 		=> 'Access denied',
	'OS_CHAN_INVALID'		=> '{chan} doesn\'t exist',	
	'OS_UNREGISTERED_NICK'	=>	'{nick} isn\'t a registered nickname',
	'OS_COMMAND_LIMIT_1'	=>	'You have triggered services flood protection',
	'OS_COMMAND_LIMIT_2'	=>	'{message}, you will be ignored for approx 2 minutes',
	'OS_ISNT_REGISTERED'	=>	'{nick} isn\'t registered',
	'OS_INVALID_HOSTNAME'	=>	'Invalid characters in hostname',
	// standard messages
	'OS_VHOST_SET'		=> 'vHost for {nick} set to {host}',
	'OS_VHOST_DELETED'	=> 'vHost for {nick} deleted',
	'OS_VHOST_LIST_T'	=> 'Listing all vHosts',
	'OS_VHOST_LIST_T2'	=> '    #  Nickname          Hostname',
	'OS_VHOST_LIST_R'	=> '    {num}. {nick} ({info})',
	'OS_VHOST_LIST_B'	=> 'End of list - {num}/{total} nickname(s) shown',
	'OS_NO_VHOST'		=> '{nick} doesn\'t have a vhost set',
	// vhosts
	'OS_STATS_U_1'		=> '         Uptime: {time}',
	'OS_STATS_U_2'		=> '   Memory usage: {memory} ({real} bytes)',
	'OS_STATS_U_3'		=> '  Incoming data: {memory} ({real} bytes)',
	'OS_STATS_U_4'		=> '  Outgoing data: {memory} ({real} bytes)',
	'OS_STATS_U_5'		=> 'Lines Processed: {lines}',
	'OS_STATS_U_6'		=> '     Lines Sent: {lines}',
	'OS_STATS_U_7'		=> '   Burst Length: {time}',
	// stats UPTIME
	'OS_STATS_N_1'		=> '   Network Name: {network}',
	'OS_STATS_N_2'		=> '        Version: {version}',
	'OS_STATS_N_3'		=> '      Max users: {users}',
	'OS_STATS_N_4'		=> '  Current users: {users}',
	'OS_STATS_N_5'		=> '  Current chans: {chans}',
	// stats NETWORK
	'OS_STATS_O_1'		=> '    #  Hostmask                                     Online Since',
	'OS_STATS_O_2'		=> '    {num}. {host} {time}',
	// stats OPERS
	'OS_MODLIST_1'		=> 'Listing all loaded modules',
	'OS_MODLIST_2'		=> '    Module         Version          Info',
	'OS_MODLIST_3'		=> '    {name} version({version})    {author} ({extra}, {type})',
	'OS_MODLOAD_1'		=> 'Unable to load {name}',
	'OS_MODLOAD_2'		=> 'Loaded {name}',
	'OS_MODLOAD_3'		=> '{name} is already loaded',	
	'OS_MODUNLOAD_1'	=> '{name} isn\'t loaded',
	'OS_MODUNLOAD_2'	=> 'Unable to unload {name}',
	'OS_MODUNLOAD_3'	=> 'Unloaded {name}',
	// modules
	'OS_IGNORE_EMPTY'	=> 'There are currently no ignored nicknames',
	'OS_IGNORE_CLEARED'	=> 'Services ignore list cleared, {users} removed',
	'OS_IGNORE_ADD'		=> '{nick} has been added to the services ignore list',
	'OS_IGNORE_DEL'		=> '{nick} has been deleted from the services ignore list',
	'OS_IGNORE_NONE'	=> '{nick} isn\'t on the services ignore list',
	'OS_IGNORE_LIST_T'	=> 'Listing ignored users',
	'OS_IGNORE_LIST_T2'	=> '    #  Nickname          Date Added',
	'OS_IGNORE_LIST'	=> '    {num}. {nick} (Added: {time})',
	'OS_IGNORE_EXISTS'	=> '{nick} is already on the services ignore list',	
	// ignore
	'OS_LOGON_START'	=> '=---- Message(s) of the Day ----=',
	'OS_LOGON_END'		=> '=---- End of Message(s) of the Day ----=',
	'OS_LOGON_NEWS_1'	=> '[{title}] Notice from {user}, posted {date}:',
	'OS_LOGON_NEWS_2'	=> '{message}',
	'OS_LOGONNEWS_ADD'	=> 'Added new logon news item',
	'OS_LOGONNEWS_DEL'	=> 'Deleted "{title}" from logon news',
	'OS_LOGONNEWS_EMPTY'=> 'There are no logon news messages',
	'OS_LOGONNEWS_NONE'	=> 'No matching logon news messages were found',
	'OS_LOGONNEWS_EXISTS'	=> 'There is already a message with the same title',
	// logonnews
);

// EOF;