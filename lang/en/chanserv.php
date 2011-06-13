<?php

/*
* Acora IRC Services
* lang/en/chanserv.php: ChanServ language file (en)
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
	'CS_HELP_PREFIX' 	=> array(
		'=---- ChanServ Help ----=',
		' ',
		'ChanServ is a service that lets you register',
		'channels under your nickname only giving you access',
		'to manage that channel.',
		' ',
		'If your channel is registered with ChanServ your',
		'channel is totally protected from unauthorized use',
		'making channel takeovers almost impossible. For more',
		'information on how to use ChanServ type:',
		'/msg ChanServ HELP command.',
		' ',
	),
	// HELP PREFIX
	
	'CS_HELP_CLEAR_1'		=> '     CLEAR     - Clears ALL modes on a channel',
	'CS_HELP_LEVELS_1' 		=> '     LEVELS    - Shows or modifies user access on a channel',
	'CS_HELP_FLAGS_1' 		=> '     FLAGS     - Changes the flags on a channel',	
	'CS_HELP_XCOMMANDS_1'	=> '     COMMANDS  - Channel management commands',
	'CS_HELP_REGISTER_1' 	=> '     REGISTER  - Register a channel',
	'CS_HELP_DROP_1' 		=> '     DROP      - Cancel the registration of a channel',
	'CS_HELP_SET_1' 		=> '     SET       - Set channel options and information',
	'CS_HELP_TOPIC_1' 		=> '     TOPIC     - Set a topic respecting the TOPICMASK',
	'CS_HELP_LIST_1' 		=> '     LIST      - Allows you to search for registered channels',
	'CS_HELP_SUSPEND_1' 	=> '     SUSPEND   - Prevent a registered channel from being used',
	'CS_HELP_UNSUSPEND_1' 	=> '     UNSUSPEND - Reverses the effect of SUSPEND',
	'CS_HELP_INFO_1' 		=> '     INFO      - Lists information about the named registered channel',
	// STANDARD HELP MESSAGES
	
	'CS_HELP_SUFFIX' 		=> array(
		' ',
		'=---- End of ChanServ Help ----=',
	),
	// HELP SUFFIX
	
	'CS_XCOMMANDS_PREFIX' 	=> array(
		'=---- ChanServ Help ----=',
		' ',
		'A list of channel management commands, these commands',
		'should be executed like any other command:',
		'/msg ChanServ command and NOT with the "COMMANDS"',
		'prefix.',
		' ',
	),
	// COMMANDS PREFIX
	
	'CS_XCOMMANDS_SUFFIX' 	=> array(
		' ',
		'=---- End of ChanServ Help ----=',
	),
	// COMMANDS SUFFIX
	
	'CS_HELP_KICK_1'		=> '     KICK      - Kicks a user from the channel',
	'CS_HELP_KICKBAN_1'		=> '     KICKBAN   - Kick bans a user from the channel',
	'CS_HELP_BAN_1'			=> '     BAN       - Bans a user or hostmask in the channel',
	'CS_HELP_UNBAN_1'		=> '     UNBAN     - Unbans a user or hostmask in the channel',
	'CS_HELP_OWNER_1'		=> '     OWNER     - Grants owner (+q) to a user in the channel',
	'CS_HELP_DEOWNER_1'		=> '     DEOWNER   - Removes owner (-q) from a user in the channel',
	'CS_HELP_PROTECT_1'		=> '     PROTECT   - Protects (+a) a user in the channel',
	'CS_HELP_DEPROTECT_1'	=> '     DEPROTECT - Deprotects (-a) a user in the channel',
	'CS_HELP_OP_1'			=> '     OP        - Ops a user in the channel',
	'CS_HELP_DEOP_1'		=> '     DEOP      - Deops a user in the channel',
	'CS_HELP_HALFOP_1'		=> '     HALFOP    - Halfops a user in the channel',
	'CS_HELP_DEHALFOP_1'	=> '     DEHALFOP  - Dehalfops a user in the channel',
	'CS_HELP_VOICE_1'		=> '     VOICE     - Voices a user in the channel',
	'CS_HELP_DEVOICE_1'		=> '     DEVOICE   - Devoices a user in the channel',
	'CS_HELP_MODE_1' 		=> '     MODE      - Sets a mode in the channel',
	'CS_HELP_SYNC_1'		=> '     SYNC      - Synchronizes the channel access list',
	'CS_HELP_TYPEMASK_1' 	=> '     TYPEMASK  - Help on how to use the TYPE:MASK commands',
	// COMMANDS
	
	'CS_HELP_FANTASY_ALL1' 	=> array(
		'=---- ChanServ Help ----=',
		'Fantasy Commands Help',
		'',
	),
	// FANTASY HELP !help
	
	'CS_HELP_FANTASY_ALL_OWNER' 	=> array(
		'    {p}owner     - Gives a user +q in the channel',
		'    {p}deowner   - Takes +q from a user in the channel',
	),
	// FANTASY HELP OWNER
	
	'CS_HELP_FANTASY_ALL_PROTECT' 	=> array(
		'    {p}protect   - Gives a user +a in the channel',
		'    {p}deprotect - Takes +a from a user in the channel',
	),
	// FANTASY HELP PROTECT
	
	'CS_HELP_FANTASY_ALL_OP' 	=> array(
		'    {p}op        - Gives a user +o in the channel',
		'    {p}deop      - Takes +o from a user in the channel',
	),
	// FANTASY HELP OP
	
	'CS_HELP_FANTASY_ALL_HALFOP' 	=> array(
		'    {p}halfop    - Gives a user +h in the channel',
		'    {p}dehalfop  - Takes +h from a user in the channel',
	),
	// FANTASY HELP HALFOP
	
	'CS_HELP_FANTASY_ALL2' 	=> array(
		'    {p}voice     - Gives a user +v in the channel',
		'    {p}devoice   - Takes +v from a user in the channel',
		'    {p}topic     - Changes the topic, using a TOPICMASK if there is one set',
		'    {p}mode      - Sets a channel mode',
		'    {p}m         - An alias for {p}mode',
		'    {p}kick      - Kicks a user from the channel',
		'    {p}kickban   - Kickbans a user from the channel',
		'    {p}ban       - Bans a user or a host from the channel',
		'    {p}unban     - Unbans a user or a host from the channel',
		'    {p}flags     - Lets you set channel flags via a fantasy command',
		'    {p}levels    - Lets you set channel levels via a fantasy command',
		'    {p}sync      - Synchronizes the channel access list',
		'',
		'=---- End of ChanServ Help ----=',
	),
	// FANTASY HELP !help
	
	'CS_HELP_REGISTER_ALL' 	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: REGISTER channel description',
		' ',
		'Registers a channel in the ChanServ database.',
		'The last parameter, which must be included, is a',
		'general description of the channel\'s purpose.',
		' ',
		'When you register a channel, you are recorded as the',
		'"founder" of the channel. ChanServ will also',
		'automatically give the founder channel-operator',
		'privileges when s/he enters the channel.',
		' ',
		'NOTICE: In order to register a channel, you must have',
		'first registered your nickname. If you haven\'t,',
		'/msg NickServ HELP for information on how to do so.',
		'=---- End of ChanServ Help ----=',
	),
	// REGISTER
	
	'CS_HELP_FLAGS_ALL'		=> array(
		'=---- ChanServ Help ----=',
		'Syntax: FLAGS channel flags [params]',
		' ',
		'The FLAGS command allows for setting channel specific settings.',
		'Some flags flags require a parameter to be set, for instance +m',
		'(modelock), when setting multiple settings at once that both require',
		'parameters, you can split them with ||, for example: "FLAGS #channel',
		'+mtK some modes to lock || a topicmask to use: *". Lowercase flags will',
		'require a parameter, while uppercase flags wont. You will need the +s',
		'channel level to use this command.',
		' ',
		'Channel flags:',
		'    +d - Changes or unsets the channel description.',
		'    +u - Associates a url with the channel.',
		'    +e - Associates an email address with the channel.',
		'    +w - Sets or unsets a welcome message for the channel, this will',
		'         be noticed to all users joining the channel.',
		'    +m - Allows a set of modes to be locked with this flag. Note that.',
		'         unlike flags, the parameter for this will REPLACE the existing',
		'         modelock, not append or change them.',
		'    +t - Sets a topic mask which may be used with the TOPIC or !topic',
		'         commands, the topicmask must contain a wildcard (*).',
		'    +S - Enables channel security features, which means a nickname must',
		'         be registered and identified to gain access on the channel,',
		'         things like +v *!*@* will stop working with this on.',
		'    +F - Enables channel fantasy commands, which allows use of in-channel',
		'         commands such as !op/!deop.',
		'    +G - If enabled ChanServ will occupy your channel, +F won\'t work',
		'         without this enabled.',
		'    +T - Prevents channel ops from changing the topic. This can be',
		'         overriden with TOPIC or !topic.',
		'    +K - Saves the topic when it changes. Saved topics are restored when',
		'         the channel has been recreated.',
		'    +L - Enables auto-limit, updates the channel limit at regular intervals',
		'         to keep a certain number of free spaces. This can prevent join floods.',
		'    +I - Enables known-only, any user who does not have access (at least +k)',
		'         will be kickbanned.',
		' ',
		'More than one flag can be set at once, for example "-ros+v". With multiple',
		'parameters.',
		'=---- End of ChanServ Help ----=',
	),
	// CS FLAGS
	
	'CS_HELP_LEVELS_ALL' 	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: LEVELS channel flags { nickname | hostmask }',
		'        LEVELS channel',
		' ',
		'The LEVELS command allows for the granting/removal of channel',
		'privileges on a more specific, non-generalized level. It',
		'supports both nicknames and hostmasks as targets.',
		' ',
		'When only the channel argument is given a list of the current',
		'channel flags will be displayed.',
		' ',
		'Access flags:',
		'    +k - Grants known-user access.',
		'    +v - Enables automatic voice.',
		'    +h - Enables automatic halfop.',
		'    +o - Enables automatic op.',
		'    +a - Enables automatic protect.',
		'    +q - Enables automatic owner.',
		'    +s - Enables use of the flags command.',
		'    +r - Enables use of the kick, kickban, ban and unban commands.',
		'    +f - Enables modification of channel access lists.',
		'    +t - Enables use of the topic and topicappend commands.',
		'    +F - Grants full founder access.',
		'    +b - Enables automatic kickban.',
		' ',
		'More than one flag can be set at once, for example "-ros+v".',
		'Only one target can be specified for this command.',
		' ',
		'Note that +a and +q will only work if your server has +qa modes',
		'enabled.',
		'=---- End of ChanServ Help ----=',
	),
	// LEVELS W/HOP
	
	'CS_HELP_LEVELS_ALL2' 	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: LEVELS channel flags { nickname | hostmask }',
		'        LEVELS channel',
		' ',
		'The LEVELS command allows for the granting/removal of channel',
		'privileges on a more specific, non-generalized level. It',
		'supports both nicknames and hostmasks as targets.',
		' ',
		'When only the channel argument is given a list of the current',
		'channel flags will be displayed.',
		' ',
		'Access flags:',
		'    +k - Grants known-user access.',
		'    +v - Enables automatic voice.',
		'    +h - Enables automatic halfop.',
		'    +o - Enables automatic op.',
		'    +a - Enables automatic protect.',
		'    +q - Enables automatic owner.',
		'    +s - Enables use of the flags command.',
		'    +r - Enables use of the kick, kickban, ban and unban commands.',
		'    +f - Enables modification of channel access lists.',
		'    +t - Enables use of the topic and topicappend commands.',
		'    +F - Grants full founder access.',
		'    +b - Enables automatic kickban.',
		' ',
		'More than one flag can be set at once, for example "-ros+v".',
		'Only one target can be specified for this command.',
		' ',
		'Note that +a and +q will only work if your server has +qa modes',
		'enabled.',
		'=---- End of ChanServ Help ----=',
	),
	// LEVELS WO/HOP
	
	'CS_HELP_LIST_ALL' 		=> array(
		'=---- ChanServ Help ----=',
		'Syntax: LIST pattern limit [SUSEPENDED]',
		' ',
		'Searches for all channels matching the given pattern',
		'then lists all the found channels with their description.',
		'The third parameter limit can be used to grab a certain',
		'amount of results, for instance starting at result 0, and',
		'returning the next 30 results you\'d do 0-30 in the limit',
		'parameter. Starting at result 30 and returning 10 would be',
		'30-10. The basic format for limit is offset-max.',
		' ',
		'An optional fourth parameter can be set to SUSPENDED',
		'to search through suspended channels.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of ChanServ Help ----=',
	),
	// LIST
	
	'CS_HELP_DROP_ALL' 		=> array(
		'=---- ChanServ Help ----=',
		'Syntax: DROP channel',
		' ',
		'Unregisters the given channel providing you have +F channel',
		'level. Once a channel is dropped all data associated with the',
		'channel is removed and cannot be restored. IRC Operators can',
		'drop any channel without needing +F channel level.',
		'=---- End of ChanServ Help ----=',
	),
	// DROP
	
	'CS_HELP_INFO_ALL' 		=> array(
		'=---- ChanServ Help ----=',
		'Syntax: INFO channel',
		' ',
		'Lists information about the named registered channel,',
		'including its founder, time of registration, last time',
		'description, entry message, and mode lock, if any.',
		'=---- End of ChanServ Help ----=',
	),
	// INFO
	
	'CS_HELP_TOPIC_ALL' 	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: TOPIC channel [topic]',
		' ',
		'Causes ChanServ to set the channel topic to the one',
		'specified.',
		'This command is most useful in conjunction with',
		'SET TOPICMASK. See /msg ChanServ HELP SET TOPICMASK',
		'for more information.',
		'=---- End of ChanServ Help ----=',
	),
	// TOPIC
	
	'CS_HELP_SUSPEND_ALL'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: SUSPEND channel reason',
		' ',
		'Disallows anyone from using the given channel, can',
		'be cancelled by using the UNSUSPEND command to preserve',
		'all previous settings and data.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of ChanServ Help ----=',
	),
	// SUSPEND
	
	'CS_HELP_UNSUSPEND_ALL'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: UNSUSPEND channel',
		' ',
		'Releases a suspended channel. All data and settings',
		'are preserved from before the suspension.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of ChanServ Help ----=',
	),
	// UNSUSPEND
	
	'CS_HELP_CLEAR_ALL'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: CLEAR channel',
		' ',
		'Clears ALL modes in a channel including all modes with parameters',
		'such as channel keys, bans, status modes are all dropped. The',
		'default channel modes are re-set onto the channel.',
		'=---- End of ChanServ Help ----=',
	),
	// CLEAR
	
	'CS_HELP_MODE_ALL'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: MODE channel [modes]',
		' ',
		'Changes a channel mode, if no parameter is given',
		'all modes are dropped. The default channel modes',
		'are re-set onto the channel',
		' ',
		'Channel bans/keys/status/limit modes etc are not dropped.', 
		'=---- End of ChanServ Help ----=',
	),
	// MODE
	
	'CS_HELP_SYNC_ALL'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: SYNC channel',
		' ',
		'Changes the channel access list, anyone who isnt on the list',
		'and has channel access will be removed, this only applies to',
		'operators and half operators. People on the access list but',
		'without access will be given access.',
		'=---- End of ChanServ Help ----=',
	),
	// SYNC
	
	'CS_HELP_TYPEMASK_ALL'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: COMMAND TYPE:TARGET',
		' ',
		'Type mask or Type target is used to "mass" mode people',
		'in channels, it can be used with a number of status',
		'altering commands, such as OP, DEOP etc.',
		' ',
		'Commands are issued in a type:target format, for example',
		'type could be either level, or mask. To voice all users',
		'without voice in a channel you could do VOICE level:0.',
		' ',
		'Below is a list of options available:',
		'    mask:nick!user@host - Matches any user against the',
		'                          supplied mask, wildcards can',
		'                          be used.',
		'    level:<mode letter> - Can be v, h, o, a, q',
		'    level:<mode_symbol> - Can be +, %, @, &, ~',
		' ',
		'Only modes that are enabled can be used in the level:mode',
		'commands, so if halfop was disabled level:h/% wouldn\'t',
		'work. A wildcard can be specified for everyone, and a 0',
		'can also be used for statusless users. Typemask can also',
		'be used on fantasy commands, such as !voice level:0.',
		'=---- End of ChanServ Help ----=',
	),
	// TYPEMASK

	'CS_HELP_XCOMMANDS_OWNER'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: OWNER channel { nickname | type:target }',
		'        DEOWNER channel { nickname | type:target }',
		' ',
		'These commands perform status mode changes on a channel.',
		'Some commands require different access',
		' ',
		'If the second parameter is not given the requested action',
		'will be performed on the person issuing the command',
		'=---- End of ChanServ Help ----=',
	),
	// XCOMMANDS OWNER
	
	'CS_HELP_XCOMMANDS_PROTECT'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: PROTECT channel { nickname | type:target }',
		'        DEPROTECT channel { nickname | type:target }',
		' ',
		'These commands perform status mode changes on a channel.',
		'Some commands require different access',
		' ',
		'If the second parameter is not given the requested action',
		'will be performed on the person issuing the command',
		'=---- End of ChanServ Help ----=',
	),
	// XCOMMANDS PROTECT
	
	'CS_HELP_XCOMMANDS_OP'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: OP channel { nickname | type:target }',
		'        DEOP channel { nickname | type:target }',
		' ',
		'These commands perform status mode changes on a channel.',
		'Some commands require different access',
		' ',
		'If the second parameter is not given the requested action',
		'will be performed on the person issuing the command',
		'=---- End of ChanServ Help ----=',
	),
	// XCOMMANDS OP
	
	'CS_HELP_XCOMMANDS_HALFOP'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: HALFOP channel { nickname | type:target }',
		'        DEHALFOP channel { nickname | type:target }',
		' ',
		'These commands perform status mode changes on a channel.',
		'Some commands require different access',
		' ',
		'If the second parameter is not given the requested action',
		'will be performed on the person issuing the command',
		'=---- End of ChanServ Help ----=',
	),
	// XCOMMANDS PROTECT
	
	'CS_HELP_XCOMMANDS_VOICE'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: VOICE channel { nickname | type:target }',
		'        DEVOICE channel { nickname | type:target }',
		' ',
		'These commands perform status mode changes on a channel.',
		'Some commands require different access',
		' ',
		'If the second parameter is not given the requested action',
		'will be performed on the person issuing the command',
		'=---- End of ChanServ Help ----=',
	),
	// PROTECT/DEPROTECT/OWNER/DEOP.. UGH BLAH..
	
	'CS_HELP_BAN_ALL'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: BAN channel { nickname | hostmask }',
		'        UNBAN channel { nickname | hostmask }',
		' ',
		'These commands allow you to tell ChanServ to ban or unban',
		'the requested nickname or hostmask.',
		'=---- End of ChanServ Help ----=',
	),
	// BAN/UNBAN
	
	'CS_HELP_KICK_ALL'	=> array(
		'=---- ChanServ Help ----=',
		'Syntax: KICK channel nickname [reason]',
		'        KICKBAN channel nickname [reason]',
		' ',
		'These commands allow you to remove a user from a channel',
		'with the requested reason, if the KICK command is used',
		'the user will be able to immediatly rejoin, if the BAN',
		'command is used ChanServ will set a channel ban for that',
		'users hostmask and then remove them from the channel, ',
		'preventing them from rejoining.',
		'=---- End of ChanServ Help ----=',
	),
	// KICK/KICKBAN
	
	'CS_INVALID_SYNTAX'		=>	'Invalid syntax: /msg ChanServ HELP for more information',
	'CS_INVALID_SYNTAX_RE'	=>	'Invalid syntax: /msg ChanServ HELP {help} for more information',
	
	'CS_ACCESS_DENIED'		=> 	'Access Denied',
	'CS_UNREGISTERED'		=>	'You need to be registered and identified to register a channel',
	'CS_REGISTERED_CHAN'	=>	'{chan} is already registered',
	'CS_FORBIDDEN_CHAN'		=>	'{chan} is forbidden from being registered',
	'CS_CHAN_REGISTERED'	=>	'{chan} has been registered under your nickname',
	'CS_MAX_CHANS_REG'		=>	'You cannot register more than {num} channel(s)',
	'CS_NEED_CHAN_OP'		=>	'You need to be an op in {chan} to register it',	
	'CS_CHAN_DROP_CODE'		=>	'To avoid this command being accidently used, this command has to be confirmed. Please confirm it by using /msg Chanserv DROP {chan} {code}',
	'CS_CHAN_INVALID_CODE'	=>	'The confirmation code you have entered is incorrect',
	'CS_CHAN_DROPPED'		=>	'{chan} has been dropped',
	'CS_UNREGISTERED_CHAN'	=>	'{chan} isn\'t a registered channel',
	'CS_UNREGISTERED_NICK'	=>	'{nick} isn\'t a registered nickname',
	// standard messages
	'CS_LEVELS_ALREADY_SET'	=>	'{target} already has {flag} set on {chan}',
	'CS_LEVELS_NOT_SET'		=>	'{target} does not have {flag} set on {chan}',
	'CS_LEVELS_SET'			=>	'{flag} set on {target} on {chan}',
	'CS_LEVELS_LIST_TOP'	=>	'    Flags         Nickname/Hostmask',
	'CS_LEVELS_LIST'		=>	'    {flags} {target} {reason}{expired}',
	'CS_LEVELS_BAD_FLAG'	=>	'You cannot set {flag} on yourself',
	'CS_LEVELS_UNKNOWN'		=>	'{flag} isn\'t a valid level flag',
	// levels
	'CS_FLAGS_NEEDS_PARAM'	=>	'{flag} requires a parameter to be set',
	'CS_FLAGS_ALREADY_SET'	=>	'{flag} is already set on {chan}',
	'CS_FLAGS_LIST'			=>	'The current flags set on {chan} are {flags}.',
	'CS_FLAGS_LIST2'		=>	'See /cs info {chan} for more information.',
	'CS_FLAGS_NOT_SET'		=>	'{flag} is not set on {chan}',
	'CS_FLAGS_SET'			=>	'{flag} set on {chan}',
	'CS_FLAGS_INVALID_T'	=>	'The value for {flag} must contain a wildcard (*)',
	'CS_FLAGS_INVALID_E'	=>	'The value for {flag} must be a valid email address',
	'CS_FLAGS_INVALID_M'	=>	'The value for {flag} contains modes you are not allowed to lock',
	'CS_FLAGS_UNKNOWN'		=>	'{flag} isn\'t a valid flag',
	// flags
	'CS_INFO_1'				=>	'Information on {chan}:',
	'CS_INFO_2'				=>	'    Founder(s): {nicks}',
	'CS_INFO_3'				=>	'   Description: {desc}',
	'CS_INFO_4'				=>	'         Topic: {topic}',
	'CS_INFO_5'				=>	'         Email: {email}',
	'CS_INFO_6'				=>	'           Url: {url}',
	'CS_INFO_7'				=>	'    Registered: {time}',
	'CS_INFO_8'				=>	'     Last used: {time}',
	'CS_INFO_9'				=>	'     Mode lock: {mode_lock}',
	'CS_INFO_10'			=>	' Entry Message: {entrymsg}',
	'CS_INFO_11'			=>	'       Options: {options}',
	'CS_INFO_12'			=>	'    Expires on: {time}',	
	'CS_INFO_SUSPENDED_1'	=>	'{chan} is currently suspended',
	'CS_INFO_SUSPENDED_2'	=>	'        Reason: {reason}',
	// info
	'CS_SUSPEND_1'			=>	'{chan} is currently suspended',
	'CS_SUSPEND_2'			=>	'{chan} is already suspended',
	'CS_SUSPEND_3'			=>	'{chan} suspended with the reason: {reason}',
	'CS_SUSPEND_4'			=>	'{chan} isn\'t suspended',
	'CS_SUSPEND_5'			=>	'{chan} unsuspended',	
	// suspend
	'CS_LIST_TOP'			=>	'Listing all registered channels',
	'CS_LIST_TOP2'			=>	'    Channel           Description',
	'CS_LIST_ROW'			=>	'    {chan} ({info})',
	'CS_LIST_BOTTOM'		=>	'End of list - {num}/{total} channel(s) shown',
	// list
);

// EOF;