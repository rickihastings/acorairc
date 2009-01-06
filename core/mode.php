<?php

/*
* Acora IRC Services
* core/mode.php: Mode parsing class.
* 
* Copyright (c) 2008 Acora (http://gamergrid.net/acorairc)
* Coded by N0valyfe and Henry of GamerGrid: irc.gamergrid.net #acora
*
* This project is licensed under the GNU Public License
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*/

class mode
{
	
	public function __construct() {}
	// __construct, makes everyone happy.
	
	/*
	* check_modes
	*
	* @params
	* $modes - checks if the modes being sent are actually valid.
	*/
	static public function check_modes( $modes )
	{
		$first_part = explode( ' ', $modes );
		$split_modes = str_split( $first_part[0] );
		
		foreach ( $split_modes as $index => $mode )
		{
			if ( $mode == '+' || $mode == '-' ) continue;
			
			if ( strpos( ircd::$modes, $mode ) === false )
			{
				$first_part[0] = str_replace( $mode, '', $first_part[0] );
				unset( $first_part[$index] );
			}
		}
		// loop through them checking if they are valid modes, if they arn't, remove it.
		
		if ( strlen( $first_part[0] ) == 1 )
			if ( $first_part[0] == '+' || $first_part[0] == '-' ) $first_part[0] = '';
		// if it is only 1 char long, and is equal to +/-, empty it.
		
		if ( $first_part[0] != '' && $first_part[0][0] != '+' && $first_part[0][0] != '-' )
			$first_part[0] = '+'.$first_part[0];
		// if it isn't empty and the first character isnt +/-, add a +o to it.
		
		$modes = implode( ' ', $first_part );
		
		return trim( $modes );
	}
	
	/*
	* sort_modes
	*
	* @params
	* $modes - parses a mode string into a processable format
	* $force_modes - force checking of invalid modes, defaults to true.
	*/
	static public function sort_modes( $modes, $force_modes = true )
	{
		if ( $modes[0] != '+' && $modes[0] != '-' ) $modes .= '+'.$modes;
		
		if ( strstr( $modes, ' ' ) != false )
		{
			$params = split( ' ', $modes );
			// split the space
			
			$modes = $params[0];
			// set the new modes string
			
			unset( $params[0] );
			// unset the modes string from the array
		}
		// if the string contains a space
		
		$split_modes = str_split( $modes );
		// split the modes string
		
		$modes = array(
			'plus'		=>	'',
			'minus'		=>	'',
			'params'	=>	( is_array( $params ) ? $params : array() )
		);
		$mode_type	=	null;
		// setup some key variables
		
		$params = array();
		$param_count = 1;
		// more key variables
		
		foreach ( $split_modes as $mode )
		{
			if ( $mode == '+' ) { $mode_type = 'plus'; continue; }
			elseif ( $mode == '-' ) { $mode_type = 'minus'; continue; }
			// check if the letter is a + or a -
			// and set the appropriate mode for the next characters
			
			if ( $mode_type == null ) continue;
			// if no mode type is set, -- don't exit, seems to be causing a crash
			// just continue;
			
			if ( $force_modes )
			{
				if ( strpos( ircd::$modes, $mode ) === false ) continue;
				// we need to check if the mode that we're getting is valid, this is determined
				// by what we're passed with the ircd
				
				if ( strpos( ircd::$modes_params, $mode ) !== false )
				{
					if ( count( $params ) == 0 && $mode_type == 'minus' )
						$modes[$mode_type] .= $mode;
					else
						$params[] = ( $mode_type == 'plus' ? '+'.$mode : '-'.$mode );
				}
				else
				{
					$modes[$mode_type] .= $mode;
				}
				// check if the mode is a parmeter mode
				// if so put in a seperate array
			}
			else
			{
				$modes[$mode_type] .= $mode;
			}
		}
		// go through each letter in the mode string
		
		if ( count( $modes['params'] ) > 0 )
		{
			foreach ( $modes['params'] as $num => $param )
			{
				if ( isset( $params[$num-1] ) )
				{
					$mode = $params[$num-1];
					// get the mode related to the param
					
					if ( !is_array( $modes['params'][$param] ) )
						$modes['params'][$param] = array( 'plus' => '', 'minus' => '' );
					// if the parameter hasen't been used before prepare it
					
					if ( strpos( $mode, '+' ) !== false )
						$modes['params'][$param]['plus'] .= str_replace( '+', '', $mode );
					elseif ( strpos( $mode, '-' ) !== false )
						$modes['params'][$param]['minus'] .=  str_replace( '-', '', $mode );
					// put the mode in the correct array
				}
				// check there is a mode set with it
				
				unset( $modes['params'][$num] );
				// unset the old key in the array
			}
			// go through each parmeter
			// and get the mode which goes with it
			
			foreach ( $modes['params'] as $param => $details )
			{
				foreach ( str_split( $details['plus'] ) as $mode )
				{
					if ( $mode == '' ) continue;
					// check the mode isn't empty
					
					$plus_count = ( $details['plus'] != '' ? substr_count( $details['plus'], $mode ) : 0 );
					$minus_count = ( $details['minus'] != '' ? substr_count( $details['minus'], $mode ) : 0 );
					// count the number of duplicate modes
					
					$modes['params'][$param]['plus'] = str_replace( $mode, '', $details['plus'] );
					$modes['params'][$param]['minus'] = str_replace( $mode, '', $details['minus'] );
					// remove all the duplicates
					
					if ( $plus_count > $minus_count )
						$modes['params'][$param]['plus'] .= $mode;
					elseif ( $plus_count < $minus_count )
						$modes['params'][$param]['minus'] .= $mode;
					// add in the mode in the correct string
				}
				
				if ( $modes['params'][$param]['plus'] == '' && $modes['params'][$param]['minus'] == '' )
					unset( $modes['params'][$param] );
				// if there are no modes for the param, remove it
			}
			// go through the new array to check for duplicates
		}
		// if there are any parameters
		
		unset( $params );
		// unset the old params array
		
		if ( $modes['plus'] != '' )
		{
			foreach ( str_split( $modes['plus'] ) as $mode )
			{
				if ( $mode == '' ) continue;
				// check the mode isn't empty
				
				$plus_count	= ( $modes['plus'] != '' ? substr_count( $modes['plus'], $mode ) : 0 );
				$minus_count = ( $modes['minus'] != '' ? substr_count( $modes['minus'], $mode ) : 0 );
				// count the number of duplicate modes
				
				$modes['plus'] = str_replace( $mode, '', $modes['plus'] );
				$modes['minus'] = str_replace( $mode, '', $modes['minus'] );
				// remove all the duplicates
				
				if ( $plus_count > $minus_count )
					$modes['plus'] .= $mode;
				elseif ( $plus_count < $minus_count )
					$modes['minus'] .= $mode;
				// add in the mode in the correct string
			}
		}
		// if the string of + modes is bigger then 0
		
		return $modes;
	}
	
	/*
	* append_modes
	*
	* @params
	* $chan - channel to append modes to.
	* $mode_array - should be a valid result from sort_modes()
	*/
	static public function append_modes( $chan, $mode_array )
	{
		if ( !isset( core::$chans[$chan] ) || !is_array( $mode_array ) )
		{
			return false;
		}
		
		if ( $mode_array['plus'] != '' )
		{
			foreach ( str_split( $mode_array['plus'] ) as $mode )
			{
				if ( strpos( core::$chans[$chan]['modes'], $mode ) === false )
					core::$chans[$chan]['modes'] .= $mode;
			}
		}
		// if we have any plus modes, add them to the channel string
		
		if ( $mode_array['minus'] != '' )
		{
			foreach ( str_split( $mode_array['minus'] ) as $mode )
			{
				$parts = explode( ' ', core::$chans[$chan]['modes'] );
				
				if ( strpos( $parts[0], $mode ) !== false )
				{
					$n_str = '';
					// some values here
					
					foreach ( str_split( $parts[0] ) as $rm )
						if ( strpos( ircd::$modes_params, $rm ) !== false ) $n_str .= $rm;
					// generate our string, without non-paramtized modes
					
					$strpos = strpos( $n_str, $mode );
					// find the location of the paramter.
					unset( $parts[($strpos + 1)] );
					// remove the parameter
					
					$parts[0] = str_replace( $mode, '', $parts[0] );
					core::$chans[$chan]['modes'] = implode( ' ', $parts );
					core::$chans[$chan]['modes'] = trim( core::$chans[$chan]['modes'] );
					// remove the param
				}
				// minus it
			}
		}
		// check if we have any minus modes, if so, take them from the plus modes
		
		foreach ( $mode_array['params'] as $param => $modes )
		{
			if ( $mode_array['params'][$param]['plus'] != '' )
			{
				foreach ( str_split( $mode_array['params'][$param]['plus'] ) as $pm )
				{
					if ( in_array( $pm, ircd::$status_modes ) || strpos( ircd::$restrict_modes, $pm ) !== false ) continue;
					// ignore status modes etc, mode::handle_params() deals with these
					
					$parts = explode( ' ', core::$chans[$chan]['modes'] );
					// parts
					
					if ( strpos( $parts[0], $pm ) === false )
					{
						$parts[0] .= $pm;
						$parts[] = $param;
						// append the mode to the parts
						
						core::$chans[$chan]['modes'] = implode( ' ', $parts );
						core::$chans[$chan]['modes'] = trim( core::$chans[$chan]['modes'] );
						// make the changes.
					}
					// plus it
				}
			}
			// plus modes
			
			if ( $mode_array['params'][$param]['minus'] != '' )
			{
				foreach ( str_split( $mode_array['params'][$param]['minus'] ) as $mm )
				{
					if ( in_array( $mm, ircd::$status_modes ) || strpos( ircd::$restrict_modes, $mm ) !== false ) continue;
					// ignore status modes etc, mode::handle_params() deals with these
					
					$parts = explode( ' ', core::$chans[$chan]['modes'] );
					// parts
					
					if ( strpos( $parts[0], $mm ) !== false )
					{
						$n_str = '';
						// some values here
						
						foreach ( str_split( $parts[0] ) as $rm )
							if ( strpos( ircd::$modes_params, $rm ) !== false ) $n_str .= $rm;
						// generate our string, without non-paramtized modes
						
						$strpos = strpos( $n_str, $mm );
						// find the location of the paramter.
						unset( $parts[($strpos + 1)] );
						// remove the parameter
						
						$parts[0] = str_replace( $mm, '', $parts[0] );
						
						core::$chans[$chan]['modes'] = implode( ' ', $parts );
						core::$chans[$chan]['modes'] = trim( core::$chans[$chan]['modes'] );
						// remove the param
					}
					// minus it
				}
			}
			// minus modes
		}
		// do we have any modes with parameters?
	}
	
	/*
	* handle_params
	*
	* @params
	* $chan - channel to handle params for.
	* $mode_array - should be a valid result from sort_modes()
	*/
	static public function handle_params( $chan, $mode_array )
	{
		if ( !isset( core::$chans[$chan] ) || !is_array( $mode_array ) )
		{
			return false;
		}
		
		foreach ( $mode_array['params'] as $param => $modes )
		{
			if ( isset( core::$chans[$chan]['users'][$param] ) )
			{
				if ( $mode_array['params'][$param]['plus'] != '' )
				{
					foreach ( str_split( $mode_array['params'][$param]['plus'] ) as $pm )
					{
						if ( !in_array( $pm, ircd::$status_modes ) ) continue;
						// we've found a user but be careful, this could be a key
						// so we've gotta check for the qaohv modes
						
						if ( strpos( core::$chans[$chan]['users'][$param], $pm ) === false )
							core::$chans[$chan]['users'][$param] .= $pm;
						// we add it as normally
					}
				}
				// loop through the plus modes if there are any
			
				if ( $mode_array['params'][$param]['minus'] != '' )
				{	
					foreach ( str_split( $mode_array['params'][$param]['minus'] ) as $mm )
					{
						if ( !in_array( $mm, ircd::$status_modes ) ) continue;
						// again we've found a user, but we need to check if it's a correct mode
						
						if ( strpos( core::$chans[$chan]['users'][$param], $mm ) !== false ) 
							core::$chans[$chan]['users'][$param] = str_replace( $mm, '', core::$chans[$chan]['users'][$param] );
						// the mode is correct, so we do the replacing accordingly
					}
				}
				// same with minus
			}
			// this above part is only for user params, eg.. qaohv, and k, if people are fucking about
			// but we also take care of k inside.
			else
			{
				if ( $mode_array['params'][$param]['plus'] != '' )
				{
					foreach ( str_split( $mode_array['params'][$param]['plus'] ) as $pm )
					{
						if ( strpos( ircd::$restrict_modes, $pm ) === false ) continue;
						// make sure the mode is a +bIe
						
						if ( strpos( core::$chans[$chan]['p_modes'][$param], $pm ) === false )
							core::$chans[$chan]['p_modes'][$param] .= $pm;
						// we add it as normally
					}
				}
				// loop through the plus modes
				
				if ( $mode_array['params'][$param]['minus'] != '' )
				{
					foreach ( str_split( $mode_array['params'][$param]['minus'] ) as $mm )
					{
						if ( strpos( ircd::$restrict_modes, $mm ) === false ) continue;
						// make sure the mode is a +bIe
						
						if ( strpos( core::$chans[$chan]['p_modes'][$param], $mm ) !== false ) 
							core::$chans[$chan]['p_modes'][$param] = str_replace( $mm, '', core::$chans[$chan]['p_modes'][$param] );
						// the mode is correct, so we do the replacing accordingly
						
						if ( core::$chans[$chan]['p_modes'][$param] == '' )
							unset( core::$chans[$chan]['p_modes'][$param] );
						// if the param is empty, we unset it.
					}
				}
				// loop through the minus modes
			}
			// here we handle +bIe, and any other that may occur, this is determined by ircd{}
		}
		// here we need to loop through the parameters, handling
		// things like +qaohv, also +bIe, as of 0.4.5
	}
	
	/*
	* type_check (private)
	* 
	* @params
	* $chan - the channel to deal with.
	* $level - level:0 etc.
	* $mode - the mode(s) we have to set
	* $cnick - and who is to set these modes.
	*/
	static public function type_check( $chan, $level, $mode, $cnick )
	{
		$part = explode( ':', $level );
		$nicks = array();
		
		if ( $part[1] == '' )
			return false;
		// we need to make sure we're actually given something.
		
		if ( $mode[0] != '+' && $mode[0] != '-' )
			return false;
		// make sure we're getting +/-
		
		if ( strpos( 'qaohv', $mode[1] ) === false )
			return false;
		// we can only set on these modes, for now.
		
		if ( count( core::$chans[$chan]['users'] ) == 0 )
			return false;
		// is the channel empty?
		
		if ( $part[0] == 'level' )
		{
			if ( $part[1] == '0' )
			{
				foreach ( core::$chans[$chan]['users'] as $nick => $modes )
					if ( strpos( $modes, 'o' ) === false && strpos( $modes, 'h' ) === false && strpos( $modes, 'v' ) === false ) $nicks[] .= $nick;
				// loop through, finding users that are level 0, or more
				// commonly, don't have any status modes.
			}
			elseif ( $part[1] == 'v' || $part[1] == ircd::$prefix_modes['v'] )
			{
				foreach ( core::$chans[$chan]['users'] as $nick => $modes )
					if ( strpos( $modes, 'v' ) !== false ) $nicks[] .= $nick;
				// again we loop, finding users that have voice.	
			}
			elseif ( ( $part[1] == 'h' || $part[1] == ircd::$prefix_modes['h'] ) && ircd::$halfop  )
			{
				foreach ( core::$chans[$chan]['users'] as $nick => $modes )
					if ( strpos( $modes, 'h' ) !== false ) $nicks[] .= $nick;
				// again we loop, finding users that have halfop.	
			}
			elseif ( $part[1] == 'o' || $part[1] == ircd::$prefix_modes['o'] )
			{
				foreach ( core::$chans[$chan]['users'] as $nick => $modes )
					if ( strpos( $modes, 'o' ) !== false ) $nicks[] .= $nick;
				// again we loop, finding users that have operator.	
			}
			elseif ( ( $part[1] == 'a' || $part[1] == ircd::$prefix_modes['a'] ) && ircd::$protect )
			{
				foreach ( core::$chans[$chan]['users'] as $nick => $modes )
				{
					if ( strpos( $modes, 'a' ) !== false ) $nicks[] .= $nick;
				}
				// again we loop, finding users that have admin.	
			}
			elseif ( ( $part[1] == 'q' || $part[1] == ircd::$prefix_modes['q'] ) && ircd::$owner )
			{
				foreach ( core::$chans[$chan]['users'] as $nick => $modes )
					if ( strpos( $modes, 'q' ) !== false ) $nicks[] .= $nick;
				// again we loop, finding users that have owner.	
			}
			elseif ( $part[1] == '*' )
			{
				foreach ( core::$chans[$chan]['users'] as $nick => $modes )
					$nicks[] .= $nick;
				// and last but not least, all :)
				// note we don't need to do any checks here.
			}
			else
			{
				return false;
			}
		}
		// we basically check here the "options" for level:x etc.
		// if there is an invalid option return false too.
		elseif ( $part[0] == 'mask' )
		{
			if ( strpos( $part[1], '@' ) === false )
				$part[1] = '*@'.$part[1];
			if ( strpos( $part[1], '!' ) === false )
				$part[1] = '*!'.$part[1];
			// mask is malformed
			
			foreach ( core::$chans[$chan]['users'] as $nick => $modes )
			{
				$hostname = core::get_full_hostname( $nick );
				
				if ( services::match( $hostname, $part[1] ) ) $nicks[] .= $nick; 
				// this needs tested, although i'm purty confident i'll work.
			}
			// loop through our users, and find a matching mask >:D
		}
		// now we check mask.
		else
		{
			return false;
		}
		// something is invalid here, back out.
		
		if ( count( $nicks ) == 0 )
			return false;
		// empty array :(
		
		foreach ( $nicks as $id => $nick )
			if ( $nick == $cnick ) unset( $nicks[$id] );
		// we don't want chanserv in our list, eww.
		
		$i = 0;
		$x = count( $nicks );
		$mode_string = $mode[0];
		$nick_string = ' ';
		foreach ( $nicks as $id => $nick )
		{
			$i++;
			// plus plus
	
			$mode_string .= $mode[1];
			$nick_string .= $nick.' ';
			// add stuff to the strings.
			
			if ( $i == ircd::$max_params || $i == $x ) 
			{
				ircd::mode( $cnick, $chan, trim( $mode_string.$nick_string ) );
				// send the modes
				
				$i = 0;
				$mode_string = $mode[0];
				$nick_string = ' ';
				// reset all our strings :D
			}
			// bit of maths, well, not much, lol.
			
			unset( $nicks[$id] );
		}
		// ok, so we've got our list, instead of sending the modes one by one, here's
		// what we're gonna do, we're gonna compress it into a mode string, setting
		// 6 at a time, sound good? indeed it does.
		
		if ( count( $nicks ) > 0 )
			ircd::mode( $cnick, $chan, trim( $mode_string.$nick_string ) );
		// send the remaining modes
	}
}

// EOF;