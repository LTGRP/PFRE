<?php 
/* $pfre: Option.php,v 1.21 2016/08/11 18:29:20 soner Exp $ */

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

namespace View;

class Option extends Rule
{
	function display($ruleNumber, $count)
	{
		$this->dispHead($ruleNumber);
		$this->dispOption();
		$this->dispTail($ruleNumber, $count);
	}
	
	function dispOption()
	{
		?>
		<td title="Option" colspan="12">
			<?php
			$value= $this->rule[$this->rule['type']];
			if (in_array($this->rule['type'], array('loginterface', 'optimization', 'ruleset-optimization', 'block-policy', 'state-policy', 'debug', 'fingerprints', 'hostid'))) {
				echo $this->rule['type'] . ": $value";
			} elseif ($this->rule['type'] == 'skip') {
				if (!is_array($value)) {
					echo "skip on $value";
				} else {
					echo 'skip on ' . implode(', ', $value);
				}
			} elseif ($this->rule['type'] == 'reassemble') {
				echo $this->rule['type'] . ": $value";
				if (isset($this->rule['no-df'])) {
					echo ' no-df';
				}
			}
			?>
		</td>
		<?php
	}

	function input()
	{
		$this->inputKey('block-policy');
		$this->inputKey('optimization');
		$this->inputKey('ruleset-optimization');
		$this->inputKey('state-policy');
		$this->inputKey('fingerprints');
		$this->inputKey('hostid');
		$this->inputKey('loginterface');
		$this->inputKey('debug');
		$this->inputDel('skip', 'delSkip');
		$this->inputAdd('skip', 'addSkip');
		$this->inputKey('reassemble');
		$this->inputBool('no-df');

		$this->inputKey('comment');
		$this->inputDelEmpty();
	}

	function edit($ruleNumber, $modified, $testResult, $generateResult, $action)
	{
		$this->editIndex= 0;
		$this->ruleNumber= $ruleNumber;

		$this->editHead($modified);

		if (filter_has_var(INPUT_POST, 'state') && filter_has_var(INPUT_POST, 'type')) {
			$this->rule['type']= filter_input(INPUT_POST, 'type');
		}

		if (!isset($this->rule['type'])) {
			$this->editSelectOption();
		}

		$this->editBlockPolicy();
		$this->editOptimization();
		$this->editRulesetOptimization();
		$this->editStatePolicy();
		$this->editFingerprints();
		$this->editHostid();
		$this->editLogInterface();
		$this->editDebug();
		$this->editSkip();
		$this->editReassemble();

		if (isset($this->rule['type'])) {
			$this->editComment();
		}
		$this->editTail($modified, $testResult, $generateResult, $action);
	}

	function editSelectOption()
	{
		?>
		<tr class="oddline">
			<td class="title">
				<?php echo _TITLE('Select Option Type').':' ?>
			</td>
			<td>
				<select id="type" name="type">
					<option value="block-policy" <?php echo ($this->rule['type'] == 'block-policy' ? 'selected' : ''); ?>>block-policy</option>
					<option value="optimization" <?php echo ($this->rule['type'] == 'optimization' ? 'selected' : ''); ?>>optimization</option>
					<option value="ruleset-optimization" <?php echo ($this->rule['type'] == 'ruleset-optimization' ? 'selected' : ''); ?>>ruleset-optimization</option>
					<option value="state-policy" <?php echo ($this->rule['type'] == 'state-policy' ? 'selected' : ''); ?>>state-policy</option>
					<option value="fingerprints" <?php echo ($this->rule['type'] == 'fingerprints' ? 'selected' : ''); ?>>fingerprints</option>
					<option value="hostid" <?php echo ($this->rule['type'] == 'hostid' ? 'selected' : ''); ?>>hostid</option>
					<option value="loginterface" <?php echo ($this->rule['type'] == 'loginterface' ? 'selected' : ''); ?>>loginterface</option>
					<option value="debug" <?php echo ($this->rule['type'] == 'debug' ? 'selected' : ''); ?>>debug</option>
					<option value="skip" <?php echo ($this->rule['type'] == 'skip' ? 'selected' : ''); ?>>skip</option>
					<option value="reassemble" <?php echo ($this->rule['type'] == 'reassemble' ? 'selected' : ''); ?>>reassemble</option>
				</select>
			</td>
		</tr>
		<?php
	}

	function editBlockPolicy()
	{
		if ($this->rule['type'] == 'block-policy') {
			?>
			<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Block Policy').':' ?>
				</td>
				<td>
					<select id="block-policy" name="block-policy">
						<option value="drop" label="drop" <?php echo ($this->rule['block-policy'] == 'drop' ? 'selected' : ''); ?>>drop</option>
						<option value="return" label="return" <?php echo ($this->rule['block-policy'] == 'return' ? 'selected' : ''); ?>>return</option>
					</select>
					<?php $this->editHelp('block-policy') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editOptimization()
	{
		if ($this->rule['type'] == 'optimization') {
			?>
			<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Optimization').':' ?>
				</td>
				<td>
					<select id="optimization" name="optimization">
						<option value="normal" <?php echo ($this->rule['optimization'] == 'normal' ? 'selected' : ''); ?>>normal</option>
						<option value="high-latency" <?php echo ($this->rule['optimization'] == 'high-latency' ? 'selected' : ''); ?>>high-latency</option>
						<option value="satellite" <?php echo ($this->rule['optimization'] == 'satellite' ? 'selected' : ''); ?>>satellite</option>
						<option value="aggressive" <?php echo ($this->rule['optimization'] == 'aggressive' ? 'selected' : ''); ?>>aggressive</option>
						<option value="conservative" <?php echo ($this->rule['optimization'] == 'conservative' ? 'selected' : ''); ?>>conservative</option>
					</select>
					<?php $this->editHelp('optimization') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editRulesetOptimization()
	{
		if ($this->rule['type'] == 'ruleset-optimization') {
			?>
			<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Ruleset Optimization').':' ?>
				</td>
				<td>
					<select id="ruleset-optimization" name="ruleset-optimization">
						<option value="none" <?php echo ($this->rule['ruleset-optimization'] == 'none' ? 'selected' : ''); ?>>none</option>
						<option value="basic" <?php echo ($this->rule['ruleset-optimization'] == 'basic' ? 'selected' : ''); ?>>basic</option>
						<option value="profile" <?php echo ($this->rule['ruleset-optimization'] == 'profile' ? 'selected' : ''); ?>>profile</option>
					</select>
					<?php $this->editHelp('ruleset-optimization') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editStatePolicy()
	{
		if ($this->rule['type'] == 'state-policy') {
			?>
			<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('State Policy').':' ?>
				</td>
				<td>
					<select id="state-policy" name="state-policy">
						<option value="if-bound" <?php echo ($this->rule['state-policy'] == 'if-bound' ? 'selected' : ''); ?>>if-bound</option>
						<option value="floating" <?php echo ($this->rule['state-policy'] == 'floating' ? 'selected' : ''); ?>>floating</option>
					</select>
					<?php $this->editHelp('state-policy') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editFingerprints()
	{
		if ($this->rule['type'] == 'fingerprints') {
			?>
			<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Fingerprints File').':' ?>
				</td>
				<td>
					<input type="text" size="50" id="fingerprints" name="fingerprints" value="<?php echo $this->rule['fingerprints']; ?>" placeholder="filename"/>
					<?php $this->editHelp('fingerprints') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editHostid()
	{
		if ($this->rule['type'] == 'hostid') {
			?>
			<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Host Id').':' ?>
				</td>
				<td>
					<input type="text" size="20" id="hostid" name="hostid" value="<?php echo $this->rule['hostid']; ?>"  placeholder="number"/>
					<?php $this->editHelp('hostid') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editLogInterface()
	{
		if ($this->rule['type'] == 'loginterface') {
			?>
			<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Log Interface').':' ?>
				</td>
				<td>
					<input type="text" size="10" id="loginterface" name="loginterface" value="<?php echo $this->rule['loginterface']; ?>"  placeholder="interface"/>
					<?php $this->editHelp('loginterface') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editDebug()
	{
		if ($this->rule['type'] == 'debug') {
			?>
			<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Debug').':' ?>
				</td>
				<td>
					<select id="debug" name="debug">
						<option value="emerg" <?php echo ($this->rule['debug'] == 'emerg' ? 'selected' : ''); ?>>emerg</option>
						<option value="alert" <?php echo ($this->rule['debug'] == 'alert' ? 'selected' : ''); ?>>alert</option>
						<option value="crit" <?php echo ($this->rule['debug'] == 'crit' ? 'selected' : ''); ?>>crit</option>
						<option value="err" <?php echo ($this->rule['debug'] == 'err' ? 'selected' : ''); ?>>err</option>
						<option value="warning" <?php echo ($this->rule['debug'] == 'warning' ? 'selected' : ''); ?>>warning</option>
						<option value="notice" <?php echo ($this->rule['debug'] == 'notice' ? 'selected' : ''); ?>>notice</option>
						<option value="info" <?php echo ($this->rule['debug'] == 'info' ? 'selected' : ''); ?>>info</option>
						<option value="debug" <?php echo ($this->rule['debug'] == 'debug' ? 'selected' : ''); ?>>debug</option>
					</select>
					<?php $this->editHelp('debug') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editSkip()
	{
		if ($this->rule['type'] == 'skip') {
			?>
			<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Skip Interfaces').':' ?>
				</td>
				<td>
					<?php
					$this->editDeleteValueLinks($this->rule['skip'], 'delSkip');
					$this->editAddValueBox('addSkip', NULL, 'if or macro', 40);
					$this->editHelp('skip');
					?>
				</td>
			</tr>
			<?php
		}
	}

	function editReassemble()
	{
		if ($this->rule['type'] == 'reassemble') {
			?>
			<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Reassemble').':' ?>
				</td>
				<td>
					<select id="reassemble" name="reassemble">
						<option value="yes" <?php echo ($this->rule['reassemble'] == 'yes' ? 'selected' : ''); ?>>yes</option>
						<option value="no" <?php echo ($this->rule['reassemble'] == 'no' ? 'selected' : ''); ?>>no</option>
					</select>
					<?php $this->editHelp('reassemble') ?>
					<input type="checkbox" id="no-df" name="no-df" value="no-df" <?php echo ($this->rule['no-df'] ? 'checked' : ''); ?> />
					<label for="no-df">no-df</label>
				</td>
			</tr>
			<?php
		}
	}
}
?>