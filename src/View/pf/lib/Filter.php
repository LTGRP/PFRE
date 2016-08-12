<?php
/* $pfre: Filter.php,v 1.19 2016/08/11 18:29:20 soner Exp $ */

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

class Filter extends FilterBase
{
	function dispInterface()
	{
		?>
		<td title="Interface">
			<?php
			if (isset($this->rule['interface'])) {
				$this->printValue($this->rule['interface']);
			} elseif (isset($this->rule['rdomain'])) {
				echo 'rdomain: ' . $this->rule['rdomain'];
			}
			?>
		</td>
		<?php
	}

	function input()
	{
		$this->inputAction();

		$this->inputFilterHead();

		$this->inputLog();
		$this->inputBool('quick');

		$this->inputFilterOpts();

		$this->inputKey('comment');
		$this->inputDelEmpty();
	}

	function inputAction()
	{
		if (filter_has_var(INPUT_POST, 'state')) {
			$this->inputKey('action');
			if (filter_input(INPUT_POST, 'action') === 'block') {
				$this->inputKey('blockoption');
				$this->inputKey('block-ttl');
				$this->inputKey('block-icmpcode');
				$this->inputKey('block-icmp6code');
			} else {
				unset($this->rule['blockoption']);
				unset($this->rule['block-ttl']);
				unset($this->rule['block-icmpcode']);
				unset($this->rule['block-icmp6code']);
			}
		}
	}

	function inputInterface()
	{
		$this->inputDel('interface', 'delInterface');
		$this->inputAdd('interface', 'addInterface');
		$this->inputKey('rdomain');
	}

	function inputPoolType()
	{
		$this->inputBool('bitmask');
		$this->inputBool('least-states');
		$this->inputBool('random');
		$this->inputBool('round-robin');
		$this->inputBool('source-hash');
		$this->inputKeyIfHasVar('source-hash-key', 'source-hash');
		$this->inputBool('sticky-address');
	}

	function edit($ruleNumber, $modified, $testResult, $generateResult, $action)
	{
		$this->editIndex= 0;
		$this->ruleNumber= $ruleNumber;

		$this->editHead($modified);

		$this->editAction();

		$this->editFilterHead();

		$this->editLog();
		$this->editCheckbox('quick', 'Quick');

		$this->editFilterOpts();

		$this->editComment();
		$this->editTail($modified, $testResult, $generateResult, $action);
	}

	function editAction()
	{
		?>
		<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
			<td class="title">
				<?php echo _TITLE('Action').':' ?>
			</td>
			<td>
				<select id="action" name="action">
					<option label="pass" <?php echo $this->rule['action'] == 'pass' ? 'selected' : ''; ?>>pass</option>
					<option label="match" <?php echo $this->rule['action'] == 'match' ? 'selected' : ''; ?>>match</option>
					<option label="block" <?php echo $this->rule['action'] == 'block' ? 'selected' : ''; ?>>block</option>
				</select>
				<?php
				$this->editHelp($this->rule['action']);
				?>
			</td>
		</tr>
		<?php
		if ($this->rule['action'] == 'block') {
			$this->editBlockOption();
		}
	}

	function editBlockOption()
	{
		?>
		<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
			<td class="title">
				<?php echo _TITLE('Block Option').':' ?>
			</td>
			<td>
				<select id="blockoption" name="blockoption">
					<option value=""></option>
					<option value="drop" <?php echo ($this->rule['blockoption'] == 'drop' ? 'selected' : ''); ?>>drop</option>
					<option value="return" <?php echo ($this->rule['blockoption'] == 'return' ? 'selected' : ''); ?>>return</option>
					<option value="return-rst" <?php echo ($this->rule['blockoption'] == 'return-rst' ? 'selected' : ''); ?>>return-rst</option>
					<option value="return-icmp" <?php echo ($this->rule['blockoption'] == 'return-icmp' ? 'selected' : ''); ?>>return-icmp</option>
					<option value="return-icmp6" <?php echo ($this->rule['blockoption'] == 'return-icmp6' ? 'selected' : ''); ?>>return-icmp6</option>
				</select>
				<input type="text" name="block-ttl" id="block-ttl" value="<?php echo $this->rule['block-ttl']; ?>" size="20" placeholder="number" <?php echo $this->rule['blockoption'] == 'return-rst' ? '' : 'disabled' ?> />
				<label for="block-ttl">ttl</label>
				<input type="text" name="block-icmpcode" id="block-icmpcode" value="<?php echo $this->rule['block-icmpcode']; ?>" size="20" placeholder="number or abbrev." <?php echo $this->rule['blockoption'] == 'return-icmp' ? '' : 'disabled' ?> />
				<label for="block-icmpcode">icmpcode</label>
				<input type="text" name="block-icmp6code" id="block-icmp6code" value="<?php echo $this->rule['block-icmp6code']; ?>" size="20" placeholder="number or abbrev." <?php echo (isset($this->rule['block-icmpcode']) && $this->rule['blockoption'] == 'return-icmp') || $this->rule['blockoption'] == 'return-icmp6' ? '' : 'disabled' ?> />
				<label for="block-icmp6code">icmp6code</label>
				<?php $this->editHelp('block') ?>
			</td>
		</tr>
		<?php
	}

	function editInterface()
	{
		?>
		<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
			<td class="title">
				<?php echo _TITLE('Interface').':' ?>
			</td>
			<td>
				<?php
				$this->editDeleteValueLinks($this->rule['interface'], 'delInterface');
				$this->editAddValueBox('addInterface', NULL, 'if or macro', 10, isset($this->rule['rdomain']));
				$this->editHelp('interface');
				?>
				<input type="text" name="rdomain" id="rdomain" value="<?php echo $this->rule['rdomain']; ?>" size="10" placeholder="number" <?php echo isset($this->rule['interface']) ? 'disabled' : '' ?> />
				<label for="rdomain">routing domain</label>
				<?php $this->editHelp('rdomain') ?>
			</td>
		</tr>
		<?php
	}

	function editPoolType()
	{
		?>
		<tr class="<?php echo ($this->editIndex++ % 2 ? 'evenline' : 'oddline'); ?>">
			<td class="title">
				<?php echo _TITLE('Redirect Options').':' ?>
			</td>
			<td>
				<input type="checkbox" id="bitmask" name="bitmask" <?php echo ($this->rule['least-states'] || $this->rule['random'] || $this->rule['round-robin'] || $this->rule['source-hash'] ? 'disabled' : ''); ?> value="bitmask" <?php echo ($this->rule['bitmask'] ? 'checked' : ''); ?> />
				<label for="bitmask">bitmask</label>
				<br>
				<input type="checkbox" id="least-states" name="least-states" <?php echo ($this->rule['bitmask'] || $this->rule['random'] || $this->rule['round-robin'] || $this->rule['source-hash'] ? 'disabled' : ''); ?> value="least-states" <?php echo ($this->rule['least-states'] ? 'checked' : ''); ?> />
				<label for="least-states">least-states</label>
				<br>
				<input type="checkbox" id="random" name="random" <?php echo ($this->rule['bitmask'] || $this->rule['least-states'] || $this->rule['round-robin'] || $this->rule['source-hash'] ? 'disabled' : ''); ?> value="random" <?php echo ($this->rule['random'] ? 'checked' : ''); ?> />
				<label for="random">random</label>
				<br>
				<input type="checkbox" id="round-robin" name="round-robin" <?php echo ($this->rule['bitmask'] || $this->rule['least-states'] || $this->rule['random'] || $this->rule['source-hash'] ? 'disabled' : ''); ?> value="round-robin" <?php echo ($this->rule['round-robin'] ? 'checked' : ''); ?> />
				<label for="round-robin">round-robin</label>
				<br>
				<input type="checkbox" id="source-hash" name="source-hash" <?php echo ($this->rule['bitmask'] || $this->rule['least-states'] || $this->rule['random'] || $this->rule['round-robin'] ? 'disabled' : ''); ?> value="source-hash" <?php echo ($this->rule['source-hash'] ? 'checked' : ''); ?> />
				<label for="source-hash">source-hash</label>
				<input type="text" id="source-hash-key" name="source-hash-key" <?php echo ($this->rule['source-hash'] ? '' : 'disabled'); ?> value="<?php echo $this->rule['source-hash-key']; ?>" size="32" />
				<label for="source-hash-key">key</label>
				<br>
				<input type="checkbox" id="sticky-address" name="sticky-address" <?php echo ($this->rule['bitmask'] || $this->rule['least-states'] || $this->rule['random'] || $this->rule['round-robin'] || $this->rule['source-hash'] ? '' : 'disabled'); ?> value="sticky-address" <?php echo ($this->rule['sticky-address'] ? 'checked' : ''); ?> />
				<label for="sticky-address">sticky-address</label>
				<?php $this->editHelp('rdr-method') ?>
			</td>
		</tr>
		<?php
	}
}
?>
