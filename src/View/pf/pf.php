<?php
/* $pfre: pf.php,v 1.1 2016/08/12 18:28:27 soner Exp $ */

/*
 * Copyright (c) 2016 Soner Tari.  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. All advertising materials mentioning features or use of this
 *    software must display the following acknowledgement: This
 *    product includes software developed by Soner Tari
 *    and its contributors.
 * 4. Neither the name of Soner Tari nor the names of
 *    its contributors may be used to endorse or promote products
 *    derived from this software without specific prior written
 *    permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
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
		PrintHelpWindow('Rules loaded: ' . $View->RuleSet->filename);
	} else {
		PrintHelpWindow("<br>Failed loading: $filepath", NULL, 'ERROR');
	}
}
?>