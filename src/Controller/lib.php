<?php
/*
 * Copyright (C) 2004-2018 Soner Tari
 *
 * This file is part of PFRE.
 *
 * PFRE is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PFRE is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PFRE.  If not, see <http://www.gnu.org/licenses/>.
 */

/** @file
 * Defs and library functions for Controller.
 */

/**
 * Functions and info strings used in shell arg control.
 *
 * @param string func Function to check type
 * @param string desc Info string to use when check failed
 */
$ArgTypes= array(
	FILEPATH	=>	array(
		'func'	=> 'IsFilePath',
		'desc'	=> _('Filepath wrong'),
		),
	NAME		=>	array(
		'func'	=> 'IsName',
		'desc'	=> _('Name wrong'),
		),
	NUM			=>	array(
		'func'	=> 'IsNumber',
		'desc'	=> _('Number wrong'),
		),
	SHA1STR	=>	array(
		'func'	=> 'IsSha1Str',
		'desc'	=> _('Not sha1 encrypted string'),
		),
	BOOL	=>	array(
		'func'	=> 'IsBool',
		'desc'	=> _('Not boolean'),
		),
	SAVEFILEPATH	=>	array(
		'func'	=> 'IsFilePath',
		'desc'	=> _('Filepath wrong'),
		),
	JSON	=>	array(
		'func'	=> 'IsJson',
		'desc'	=> _('Not JSON encoded string'),
		),
);

function IsNumber($str)
{
	return preg_match('/' . RE_NUM . '/', $str);
}

function IsName($str)
{
	return preg_match('/' . RE_NAME . '/', $str);
}

function IsJson($str)
{
	return json_decode($str) !== NULL;
}

function IsSha1Str($str)
{
	return preg_match('/' . RE_SHA1 . '/', $str);
}

function IsEmpty($str)
{
	return empty($str);
}

function IsBool($str)
{
	return preg_match('/' . RE_BOOL . '/', $str);
}

/**
 * Computes and fills arg count variables.
 *
 * @param array $commands Available commands for the current model
 * @param array $argv Argument vector
 * @param string $cmd Method name, key to $commands
 * @param int $actual Given arg count
 * @param int $expected Expected arg count
 * @param int $acceptable Acceptable arg count
 * @param int $check Arg count used while validating
 */
function ComputeArgCounts($commands, $argv, $cmd, &$actual, &$expected, &$acceptable, &$check)
{
	$actual= count($argv);
	$expected= count($commands[$cmd]['argv']);

	$acceptable= $expected;
	for ($argpos= 0; $argpos < $expected; $argpos++) {
		$argtype= $commands[$cmd]['argv'][$argpos];
		if ($argtype & NONE) {
			$acceptable--;
		}
	}
	
	/// @attention There may be extra or missing args, hence min() here
	$check= min($actual, $expected);
}

/**
 * Checks types of the arguments passed.
 *
 * The arguments are checked against the types listed in $commands.
 *
 * @param array $commands Available commands for the current model
 * @param string $command Method name, key to $commands
 * @param array $argv Argument vector
 * @param int $check Arg count used while validating
 * @return bool Validation result
 *
 * @todo There are 2 types of argument checks in this project, which one to choose?
 */
function ValidateArgs($commands, $command, $argv, $check)
{
	global $ArgTypes;

	$helpmsg= $commands[$command]['desc'];
	$logmsg= $commands[$command]['desc'];
	
	$valid= FALSE;
	// Check each argument in order
	for ($argpos= 0; $argpos < $check; $argpos++) {
		$arg= $argv[$argpos];
		$argtype= $commands[$command]['argv'][$argpos];

		// Multiple types may match for an arg, hence the foreach loop
		foreach ($ArgTypes as $type => $conf) {
			// Acceptable types are bitwise ORed, hence the AND here
			if ($argtype & $type) {
				$validatefunc= $conf['func'];
				if ($validatefunc($arg)) {
					$valid= TRUE;

					if ($type & FILEPATH) {
						// Further check if file really exists
						exec("[ -e $arg ]", $output, $retval);
						if ($retval !== 0) {
							$valid= FALSE;

							$errormsg= "$command: $arg";
							Error(_('No such file').": $errormsg");
							ctlr_syslog(LOG_NOTICE, __FILE__, __FUNCTION__, __LINE__, "No such file: $errormsg");
						}
					}

					if ($valid) {
						// One type succeded, hence do not check for other possible types for this arg
						break;
					}
				}
				else {
					$valid= FALSE;
					
					$helpmsg.= "\n"._($conf['desc']).': '.$arg;
					$logmsg.= "\n".$conf['desc'].': '.$arg;
					// Will keep checking if further types are possible for this arg
				}
			}
		}

		if (!$valid) {
			// One arg failed to check, do not run the func
			break;
		}
	}
	
	if (!$valid) {
		Error(_('Arg type check failed').": $helpmsg");
		ctlr_syslog(LOG_NOTICE, __FILE__, __FUNCTION__, __LINE__, "Arg type check failed: $logmsg");
	}
	return $valid;
}
?>
