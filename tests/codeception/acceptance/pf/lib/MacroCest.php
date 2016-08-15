<?php 
/* $pfre: MacroCest.php,v 1.1 2016/08/14 22:16:48 soner Exp $ */

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

require_once ('Rule.php');

class MacroCest extends Rule
{
	protected $type= 'Macro';
	protected $ruleNumber= 3;
	protected $ruleNumberGenerated= 9;
	protected $sender= 'macro';

	protected $origRule= 'test = "{ ssh, 2222 }" # Test';
	protected $expectedDispOrigRule= 'test
ssh
2222
Test e u d x';

	protected $modifiedRule= 'test1 = "{ ssh, 2222, 1111 }" # Test1';
	protected $expectedDispModifiedRule= 'test1
ssh
2222
1111
Test1 e u d x';

	protected function modifyRule(AcceptanceTester $I)
	{
		$I->fillField('identifier', 'test1');
		$this->clickApplySeeResult($I, 'test1 = "{ ssh, 2222 }" # Test');

		$I->fillField('addValue', '1111');
		$this->clickApplySeeResult($I, 'test1 = "{ ssh, 2222, 1111 }" # Test');

		$I->fillField('comment', 'Test1');
		$this->clickApplySeeResult($I, $this->modifiedRule);
	}

	protected function revertModifications(AcceptanceTester $I)
	{
		$I->fillField('identifier', 'test');
		$this->clickApplySeeResult($I, 'test = "{ ssh, 2222, 1111 }" # Test1');
		
		$this->clickDeleteLink($I, 'delValue', '1111');
		$this->seeResult($I, 'test = "{ ssh, 2222 }" # Test1');

		$I->fillField('comment', 'Test');
		$this->clickApplySeeResult($I, $this->revertedRule);
	}

	protected function modifyRuleQuick(AcceptanceTester $I)
	{
		$I->fillField('identifier', 'test1');
		$I->fillField('addValue', '1111');
		$I->fillField('comment', 'Test1');

		$I->click('Apply');
	}
}
?>