#!/usr/local/bin/php
<?php
/*
 * Copyright (C) 2004-2016 Soner Tari
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
 * Proxy to run all shell commands.
 * 
 * This way we have only one entry in doas.conf.
 * 
 * @todo Continually check for security issues.
 */

/// @todo Is there a better way?
$ROOT= dirname(dirname(dirname(__FILE__)));
$SRC_ROOT= dirname(dirname(__FILE__));

require_once($SRC_ROOT . '/lib/defs.php');
require_once($SRC_ROOT . '/lib/setup.php');

// chdir is for PCRE, libraries
chdir(dirname(__FILE__));

require_once($MODEL_PATH . '/pf.php');

require_once('lib.php');

/// This is a command line tool, should never be requested on the web interface.
if (filter_has_var(INPUT_SERVER, 'SERVER_ADDR')) {
	/// @attention pfrec_syslog() is in the Model, use after including model
	pfrec_syslog(LOG_ERR, __FILE__, __FUNCTION__, __LINE__, 'Requested on the wui, exiting...');
	header('Location: /index.php');
	exit;
}

$ArgV= array_slice($argv, 1);

if ($ArgV[0] === '-t') {
	$ArgV= array_slice($ArgV, 1);

	$TEST_ROOT= dirname(dirname(dirname(__FILE__)));
	$TEST_DIR= '/tests/phpunit/root';
	$TEST_DIR_PATH= $TEST_ROOT . $TEST_DIR;
	$TEST_DIR_SRC= $TEST_DIR . '/var/www/htdocs/pfre';

	$INSTALL_USER= posix_getpwuid(posix_getuid())['name'];
}

// Controller runs using the session locale of View
$Locale= $ArgV[0];

$Model= new Pf();
$Command= $ArgV[1];

/// @attention Do not set locale until after model file is included and model is created,
/// otherwise strings recorded into logs are also translated, such as the strings on Commands array of models.
/// Strings cannot be untranslated.
if (!array_key_exists($Locale, $LOCALES)) {
	$Locale= $DefaultLocale;
}

putenv('LC_ALL='.$Locale);
putenv('LANG='.$Locale);

$Domain= 'pfre';
bindtextdomain($Domain, $VIEW_PATH.'/locale');
bind_textdomain_codeset($Domain, $LOCALES[$Locale]['Codeset']);
textdomain($Domain);

$retval= 1;

if (method_exists($Model, $Command)) {
	$ArgV= array_slice($ArgV, 2);

	if (array_key_exists($Command, $Model->Commands)) {
		$run= FALSE;

		ComputeArgCounts($Model->Commands, $ArgV, $Command, $ActualArgC, $ExpectedArgC, $AcceptableArgC, $ArgCheckC);

		// Extra args are OK for now, will drop later
		if ($ActualArgC >= $AcceptableArgC) {
			if ($ArgCheckC === 0) {
				$run= TRUE;
			}
			else {
				// Check only the relevant args
				$run= ValidateArgs($Model->Commands, $Command, $ArgV, $ArgCheckC);
			}
		}
		else {
			$ErrorStr= "[$AcceptableArgC]: $ActualArgC";
			Error(_('Not enough args')." $ErrorStr");
			pfrec_syslog(LOG_ERR, __FILE__, __FUNCTION__, __LINE__, "Not enough args $ErrorStr");
		}

		if ($run) {
			if ($ActualArgC > $ExpectedArgC) {
				$ErrorStr= "[$ExpectedArgC]: $ActualArgC: ".implode(', ', array_slice($ArgV, $ExpectedArgC));

				// Drop extra arguments before passing to the function
				$ArgV= array_slice($ArgV, 0, $ExpectedArgC);

				Error(_('Too many args, truncating')." $ErrorStr");
				pfrec_syslog(LOG_WARNING, __FILE__, __FUNCTION__, __LINE__, "Too many args, truncating $ErrorStr");
			}

			if (call_user_func_array(array($Model, $Command), $ArgV)) {
				$retval= 0;
			}
		}
		else {
			Error(_('Not running command').": $Command");
			pfrec_syslog(LOG_WARNING, __FILE__, __FUNCTION__, __LINE__, "Not running command: $Command");
		}
	}
	else {
		Error(_('Unsupported command').": $Command");
		pfrec_syslog(LOG_ERR, __FILE__, __FUNCTION__, __LINE__, "Unsupported command: $Command");
	}
}
else {
	
	$ErrorStr= "Pf->$Command()";
	Error(_('Method does not exist').": $ErrorStr");
	pfrec_syslog(LOG_WARNING, __FILE__, __FUNCTION__, __LINE__, "Method does not exist: $ErrorStr");
}

/// @attention Always return errors, success or fail.
/// @attention We need to include $retval in the array too, because phpseclib exec() does not provide access to retval.
// Return an encoded array, so that the caller can easily separate output, error, and retval
$msg= array($Output, $Error, $retval);
$encoded= json_encode($msg);

if ($encoded !== NULL) {
	echo $encoded;
} else {
	pfrec_syslog(LOG_ERR, __FILE__, __FUNCTION__, __LINE__, 'Failed encoding output and error: ' . print_r($msg, TRUE));
}

exit($retval);
?>
