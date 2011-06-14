<?php

/*
* Acora IRC Services
* lang/en/nickserv.php: NickServ language file (en)
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
	'NS_HELP_PREFIX' 	=> array(
		'=---- {nickserv} Help ----=',
		' ',
		'{nickserv} is a nickname registration system,',
		'access to {chanserv} requires a registered {nickserv} account.',
		'For more information on a specific command, type',
		'/msg {nickserv} HELP command.',
		' ',
	),
	// PREFIX

	'NS_HELP_REGISTER_1' 	=> '     REGISTER  - Register a nickname',
	'NS_HELP_CONFIRM_1'		=> '     CONFIRM   - Confirm a {nickserv} passcode',
	'NS_HELP_IDENTIFY_1' 	=> '     IDENTIFY  - Identify yourself with your password',
	'NS_HELP_LOGOUT_1' 		=> '     LOGOUT    - Reverses the effect of the IDENTIFY command',
	'NS_HELP_GHOST_1' 		=> '     GHOST     - Disconnects a "ghost" IRC session using your nickname',
	'NS_HELP_RECOVER_1' 	=> '     RECOVER   - Kill another user who has taken your nickname',
	'NS_HELP_RELEASE_1' 	=> '     RELEASE   - Regain custody of your nickname after RECOVER',
	'NS_HELP_DROP_1' 		=> '     DROP      - Cancel the registration of a nickname',
	'NS_HELP_SUSPEND_1' 	=> '     SUSPEND   - Suspend a given nickname',
	'NS_HELP_UNSUSPEND_1' 	=> '     UNSUSPEND - Unsuspend a given nickname',
	'NS_HELP_LIST_1' 		=> '     LIST      - Allows you to search for registered nicknames',
	'NS_HELP_INFO_1' 		=> '     INFO      - Displays information about a given nickname',
	'NS_HELP_PASSWORD_1'	=> '     PASSWORD  - Set a new nickname password',
	'NS_HELP_SAPASS_1'		=> '     SAPASS    - Set another users password',
	'NS_HELP_FLAGS_1' 		=> '     FLAGS     - Changes the flags on your nickname',
	'NS_HELP_SAFLAGS_1' 	=> '     SAFLAGS   - Changes the flags on a another user',
	// BASIC HELP
		
	'NS_HELP_SUFFIX'		=> array(
		' ',
		'NOTICE: This service is intended to provide a way for',
		'IRC users to ensure their identity is not compromised.',
		'It is NOT intended to facilitate "stealing" of',
		'nicknames or other malicious actions. Abuse of {nickserv}',
		'will result in, at minimum, loss of the abused',
		'nickname(s).',
		' ',
		'=---- End of {nickserv} Help ----=',
	),
	// SUFFIX
	
	'NS_HELP_FLAGS_ALL'		=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: FLAGS flags [params]',
		' ',
		'The FLAGS command allows for setting nickname specific settings.',
		'Some flags flags require a parameter to be set, for instance +u',
		'(url), when setting multiple settings at once that both require',
		'parameters, you can split them with ||, for example: "FLAGS +ue',
		'http://www.mywebsite.com || myemail@address.com". Lowercase flags will',
		'require a parameter, while uppercase flags wont. Note +e cannot be unset',
		'and can only be changed.',
		' ',
		'Nickname flags:',
		'    +u - Associates a url with the nickname.',
		'    +e - Associates an email address with the nickname.',
		'    +S - Enables nickname security features, which means you must identify',
		'         within a certain amount of time (specified in the config) or your',
		'         nickname will be changed.',
		'    +P - When enabled services will send messages to you instead of noticing.',
		'         you.',
		' ',
		'More than one flag can be set at once, for example "-P+S". With multiple',
		'parameters.',
		'=---- End of {nickserv} Help ----=',
	),
	// NS FLAGS
	
	'NS_HELP_SAFLAGS_ALL'		=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: SAFLAGS nickname flags [params]',
		' ',
		'The FLAGS command allows for setting nickname specific settings on another',
		'user. Some flags flags require a parameter to be set, for instance +u',
		'(url), when setting multiple settings at once that both require',
		'parameters, you can split them with ||, for example: "FLAGS +ue',
		'http://www.mywebsite.com || myemail@address.com". Lowercase flags will',
		'require a parameter, while uppercase flags wont. Note +e cannot be unset',
		'and can only be changed.',
		' ',
		'Nickname flags:',
		'    +u - Associates a url with the nickname.',
		'    +e - Associates an email address with the nickname.',
		'    +S - Enables nickname security features, which means you must identify',
		'         within a certain amount of time (specified in the config) or your',
		'         nickname will be changed.',
		'    +P - When enabled services will send messages to you instead of noticing.',
		'         you.',
		' ',
		'More than one flag can be set at once, for example "-P+S". With multiple',
		'parameters.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of {nickserv} Help ----=',
	),
	// NS SAFLAGS
	
	'NS_HELP_PASSWORD_ALL'		=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: PASSWORD new-password confirm-password',
		' ',
		'Changes the password used to identify you as the nickname\'s',
		'owner.',
		'=---- End of {nickserv} Help ----=',
	),
	// PASSWORD
	
	'NS_HELP_SAPASS_ALL'		=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: SAPASS nickname new-password confirm-password',
		' ',
		'Changes the password of the specified user used to identify',
		'them as the nickname\'s owner.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of {nickserv} Help ----=',
	),
	// SAPASS
	
	'NS_HELP_REGISTER_ALL' 	=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: REGISTER password email',
		' ',
		'Registers your nickname in the {nickserv} database, once your',
		'nickname is registered, you can identify with the IDENTIFY',
		'command and configure your nickname\'s settings as you like them.',
		'',
		'The email is required and will be used to send a passcode to',
		'confirm your account if the network admins have enabled it',
		'=---- End of {nickserv} Help ----=',
	),
	// REGISTER
	
	'NS_HELP_CONFIRM_ALL' 	=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: CONFIRM passcode',
		' ',
		'This is the second step of nickname registration process.',
		'You must perform this command in order to get your nickname',
		'registered with {nickserv}. The passcode is sent to your e-mail',
		'address in the first step of the registration process.',
		'For more information about the first stage of the registration',
		'process, type: /msg {nickserv} HELP REGISTER',
		'=---- End of {nickserv} Help ----=',
	),
	// CONFIRM
	
	'NS_HELP_IDENTIFY_ALL' 	=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: IDENTIFY password',
		' ',
		'Identifies you as the real owner of the nickname.',
		'Many commands require you to authenticate yourself',
		'with this command before you use them.',
		'=---- End of {nickserv} Help ----=',
	),
	// IDENTIFY
	
	'NS_HELP_LOGOUT_ALL' 	=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: LOGOUT',
		' ',
		'Reverses the effect of the IDENTIFY',
		'command, i.e. make you not recognized as the real owner of the nickname',
		'anymore. Note, however, that you won\'t be asked to reidentify',
		'yourself.',
		'=---- End of {nickserv} Help ----=',
	),
	// LOGOUT
	
	'NS_HELP_GHOST_ALL' 	=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: GHOST nickname password',
		' ',
		'Terminates a "ghost" IRC session using your nickname. A',
		'"ghost" session is one which is not actually connected,',
		'but which the IRC server believes is still online for one',
		'reason or another. Typically, this happens if your',
		'computer crashes or your internet or modem connection',
		'goes down while you\'re on IRC.',
		'=---- End of {nickserv} Help ----=',
	),
	// GHOST
	
	'NS_HELP_RECOVER_ALL' 	=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: RECOVER nickname password',
		' ',
		'Allows you to recover your nickname if someone else has',
		'taken it.',
		' ',
		'When you give this command, {nickserv} will bring a fake',
		'user online with the same nickname as the user you\'re',
		'trying to recover your nickname from. This fake user will',
		'remain online for 1 minute to ensure that the other',
		'user does not immediately reconnect; after that time,',
		'you can reclaim your nickname. Alternatively, use the RELEASE',
		'command (/msg {nickserv} HELP RELEASE) to get the nickname',
		'back sooner.',
		'=---- End of {nickserv} Help ----=',
	),
	// RECOVER
	
	'NS_HELP_RELEASE_ALL' 	=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: RELEASE nickname password',
		' ',
		'Instructs {nickserv} to remove any hold on your nickname',
		'caused by automatic kill protection or use of the RECOVER',
		'command. This holds lasts for 1 minute; this command gets',
		'rid of them sooner.',
		'=---- End of {nickserv} Help ----=',
	),
	// RELEASE
	
	'NS_HELP_DROP_ALL' 	=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: DROP nickname password',
		' ',
		'Using this command makes {nickserv} drop the nickname, a',
		'password is required even if you are identified to that',
		'nickname. Once a nickname has been dropped it is free',
		'to re register by anyone.',
		' ',
		'You will also lose any channels registered and all',
		'channel access under that nickname.',
		'=---- End of {nickserv} Help ----=',
	),
	// DROP
	
	'NS_HELP_SUSPEND_ALL'	=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: SUSPEND nickname reason',
		' ',
		'Disallows anyone from using the given nickname, can',
		'be cancelled by using the UNSUSPEND command to preserve',
		'all previous settings and data.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of {nickserv} Help ----=',
	),
	// SUSPEND
	
	'NS_HELP_UNSUSPEND_ALL'	=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: UNSUSPEND nickname',
		' ',
		'Releases a suspended nickname. All data and settings',
		'are preserved from before the suspension.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of {nickserv} Help ----=',
	),
	// UNSUSPEND
	
	'NS_HELP_LIST_ALL' 		=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: LIST pattern limit [SUSEPENDED]',
		' ',
		'Searches for all nicknames matching the given pattern',
		'then lists all the found nicknames with their last ',
		'used hostname. The third parameter limit can be used',
		'to grab a certain amount of results, for instance',
		'starting at result 0, and returning the next 30 results',
		'you\'d do 0-30 in the limit parameter. Starting at',
		'result 30 and returning 10, would be 30-10. The basic',
		'format for limit is offset-max.',
		' ',
		'An optional fourth parameter can be set to SUSPENDED',
		'to search through suspended channels.',
		' ',
		'Command limited to IRC Operators.',
		'=---- End of {nickserv} Help ----=',
	),
	// LIST
	
	'NS_HELP_INFO_ALL' 		=> array(
		'=---- {nickserv} Help ----=',
		'Syntax: INFO nickname',
		' ',
		'Displays information about the given nickname, such as',
		'time of registration, last seen address and time, and',
		'nickname options.',
		' ',
		'Services staff are shown all information including',
		'hidden information.',
		'=---- End of {nickserv} Help ----=',
	),
	// INFO
	
	'NS_REGISTERED_NICK'	 => 	array(
		'This nickname is registered. Please choose a different nickname,',
		'or identify via /msg {nickserv} identify password',
	),
	
	'NS_INVALID_SYNTAX'		=>	'Invalid syntax: /msg {nickserv} HELP for more information',
	'NS_INVALID_SYNTAX_RE'	=>	'Invalid syntax: /msg {nickserv} HELP {help} for more information',
	
	'NS_ACCESS_DENIED'		=> 	'Access Denied',
	'NS_UNREGISTERED'		=> 	'Your nickname isn\'t registered',
	'NS_ISNT_REGISTERED'	=>	'{nick} isn\'t registered',
	'NS_NICK_DROPPED'		=>	'{nick} has been dropped',
	'NS_NOT_IN_USE'			=>	'{nick} isn\'t in use',
	'NS_CANT_GHOST_SELF'	=>	'You can\'t ghost yourself',
	'NS_CANT_RECOVER_SELF'	=>	'You can\'t recover yourself',
	'NS_NO_HOLD'			=>	'Services doesn\'t have a hold on {nick}',
	'NS_ALREADY_IDENTIFIED'	=>	'You are already identified',
	'NS_ALREADY_REGISTERED'	=>	'This nickname is already registered',	
	'NS_INVALID_PASSWORD'	=>	'The password you have entered is incorrect',
	'NS_INVALID_PASSCODE'	=>	'Invalid passcode has been entered, please check the e-mail again, and retry',
	'NS_INVALID_EMAIL'		=>	'The email address you have entered is invalid',
	'NS_SECURED_NICK'		=>	'You have {seconds} seconds to identify to your nickname before it is changed',
	'NS_EMAIL_IN_USE'		=>	'The email address you have entered is already being used',	
	'NS_AWAITING_VALIDATION'=>	'This nickname is still awaiting validation',
	'NS_VALIDATED'			=>	'Your account is now validated, you may identify',
	'NS_IDENTIFIED'			=>	'Password accepted, you are now identified',
	'NS_NOT_IDENTIFIED'		=>	'This command requires you to be identified',
	'NS_LOGGED_OUT'			=>	'You are now logged out',	
	'NS_SUSPENDED_NICK'		=>	'This nickname is suspended from use',	
	'NS_NICK_REQUESTED'		=>	'A confirmation code has been sent to {email}',
	'NS_NICK_REGISTERED'	=>	'Your nickname has been sucessfully registered, you may now identify',
	'NS_NICK_RELEASED'		=>	'Services hold on {nick} has been released',
	'NS_NICK_RECOVERED'		=>	'{nick} has been recovered',
	'NS_NICK_CHANGE'		=>	'Your nickname is now being changed to {nick}',
	// standard messages
	'NS_SUSPEND_1'			=>	'{nick} is currently suspended',
	'NS_SUSPEND_2'			=>	'{nick} is already suspended',
	'NS_SUSPEND_3'			=>	'{nick} suspended with the reason: {reason}',
	'NS_SUSPEND_4'			=>	'{nick} isn\'t suspended',
	'NS_SUSPEND_5'			=>	'{nick} unsuspended',
	// suspend messages
	'NS_LIST_TOP'			=>	'Entry  Nickname          Hostname',
	'NS_LIST_DLM'			=>	'-----  ----------------  --------',
	'NS_LIST_ROW'			=>	'{num}  {nick}{info}',
	'NS_LIST_BOTTOM'		=>	'End of list - {num}/{total} nickname(s) shown',
	// list
	'NS_INFO_1'				=>	'Information on {nick}:',
	'NS_INFO_2'				=>	'    Registered: {time}',
	'NS_INFO_3'				=>	'     Last seen: {time}',
	'NS_INFO_4'				=>	' Last hostmask: {host}',
	'NS_INFO_5'				=>	'         Email: {email}',
	'NS_INFO_6'				=>	'           URL: {url}',
	'NS_INFO_7'				=>	'       Options: {options}',
	'NS_INFO_8'				=>	'    Expires on: {time}',
	'NS_INFO_SUSPENDED_1'	=>	'{nick} is currently suspended',
	'NS_INFO_SUSPENDED_2'	=>	'        Reason: {reason}',
	// info messages
	'NS_NEW_PASSWORD'		=>	'Your password has been changed to {pass}',
	'NS_NEW_PASSWORD_U'		=>	'Password for {nick} has been changed to {pass}',
	'NS_PASSWORD_DIFF'		=>	'The passwords you have entered do not match',
	'NS_PASSWORD_NICK'		=>	'You cannot use your nickname as your password',
	'NS_PASSWORD_NICK_U'	=>	'You cannot use the nickname as the password',
	// password
	'NS_FLAGS_NEEDS_PARAM'	=>	'{flag} requires a parameter to be set',
	'NS_FLAGS_ALREADY_SET'	=>	'{flag} is already set on {target}',
	'NS_FLAGS_NOT_SET'		=>	'{target} does not have {flag} set',
	'NS_FLAGS_SET'			=>	'{flag} set on {target}',
	'NS_FLAGS_INVALID_E'	=>	'The value for {flag} must be a valid email address',
	'NS_FLAGS_CANT_UNSET'	=>	'You cannot unset {flag}',
	'NS_FLAGS_UNKNOWN'		=>	'{flag} isn\'t a valid flag',
	// flags
);

// EOF;