<?php
/*
 * Copyright (C) 2004-2017 Soner Tari
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

use View\RuleSet;

require_once('../lib/vars.php');

class Pf extends View
{
	public $RuleSet;

	function __construct()
	{
		if (!isset($_SESSION['pf']['ruleset'])) {
			$_SESSION['pf']['ruleset']= new RuleSet();
		}
		$this->RuleSet= &$_SESSION['pf']['ruleset'];
	}
}

$View= new Pf();

// Load the main pf configuration if the ruleset is empty
if ($View->RuleSet->filename == '') {
	$filepath= '/etc/pf.conf';
	$ruleSet= new RuleSet();
	if ($ruleSet->load($filepath, 0, TRUE)) {
		$View->RuleSet= $ruleSet;
		PrintHelpWindow(_NOTICE('Rules loaded') . ': ' . $View->RuleSet->filename);
	} else {
		PrintHelpWindow('<br>' . _NOTICE('Failed loading') . ": $filepath", NULL, 'ERROR');
	}
}
?>
