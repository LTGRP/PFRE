<?php
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

/** @file
 * Contains the RuleSet class.
 */

/**
 * @namespace Model
 * 
 * Model of MVC: Generally, the Model is responsible for executing commands from the View.
 * 
 * In PFRE, the Model:
 * \li Loads rules from strings given to it or from files in the file system
 * \li Generates rules or rulesets given by the View
 * \li Validates the values in rules: Untaints all externally supplied data
 * \li Tests rules using pfctl, and returns the test results
 * \li Installs, saves, or deletes rule files
 * \li Authenticates users
 * \li Configures PFRE settings
 */
namespace Model;

/** 
 * Loads, validates, parses, and generates a list of rules.
 */
class RuleSet
{
	public $rules= array();
	
	/** 
	 * Loads the rule definitions given in an array.
	 * 
	 * The rule definitions in $rulesArray contains the type and NVP elements of the rule.
	 * For each item in $rulesArray, we create an empty rule object of type mentioned in the rule definition and
	 * call the load method of the rule with the NVP elements.
	 * 
	 * Rule number is determined by the order of the rule in the given $rulesArray.
	 * 
	 * If loading produces errors, we can force loading by setting $force to TRUE. The user is allowed to do this on the
	 * WUI, so that rule sets with errors can be loaded and fixed using PFRE.
	 * 
	 * Note that loading does not stop even if $force is set to FALSE, we partially load the current rule with error and
	 * continue loading the next rule in the array.
	 * 
	 * Also, this is where we set the nesting string, which is used in reporting errors in nested anchors in inline rules.
	 *
	 * @param array $rulesArray List of rule definitions in an array.
	 * @param bool $force Used to override validation or other types of errors, hence forces loading of rules.
	 */
	function load($rulesArray, $force= FALSE)
	{
		global $Nesting;
		$nestingStr= $Nesting > 0 ? "Nesting $Nesting, " : '';

		$this->deleteRules();
	
		$retval= TRUE;
		$ruleNumber= 0;
		foreach ($rulesArray as $ruleDef) {
			$cat= __NAMESPACE__ . '\\' . $ruleDef['cat'];
			$ruleObj= new $cat('');
			if (!$ruleObj->load($ruleDef['rule'], $ruleNumber, $force)) {
				if (!$force) {
					Error($nestingStr . _('Rule') . " $ruleNumber: " . _('Error loading, rule loaded partially'));
					pfrec_syslog(LOG_NOTICE, __FILE__, __FUNCTION__, __LINE__, $nestingStr . "Rule $ruleNumber: Error loading, rule loaded partially");
				} else {
					Error($nestingStr . _('Rule') . " $ruleNumber: " . _('Error loading, rule load forced'));
					pfrec_syslog(LOG_NOTICE, __FILE__, __FUNCTION__, __LINE__, $nestingStr . "Rule $ruleNumber: Error loading, rule load forced");
				}
				$retval= FALSE;
			}
			$this->rules[]= $ruleObj;
			$ruleNumber++;
		}
		return $retval;
	}
	
	/** 
	 * Deletes all the rules in the ruleset.
	 */
	function deleteRules()
	{
		$this->rules= array();
	}
	
	/** 
	 * Parses the given rule string.
	 * 
	 * These are the steps:
	 * \li First delete any existing rules
	 * \li Sort out inline comments and new lines
	 * \li Divide the given string into lines, which are to be parsed by rule objects
	 * \li Determine the type of rule on each line
	 * \li Insert any accumulated comments or blank lines
	 * \li Collect inline anchor rules which spans multiple lines
	 * \li Finally create a rule object of the type determined above and pass the line to be parsed to that object
	 * 
	 * We validate the ruleset after all those steps are completed.
	 *
	 * @param string $text Rule set in string format to parse.
	 * @param bool $force Used to override validation or other types of errors, hence forces loading of rules.
	 */
	function parse($text, $force= FALSE)
	{
		$this->deleteRules();
		$ruleLines= array();

		$text= preg_replace("/\n#/", "\n# ", $text);
		$text= str_replace("\\\n", '', $text);

		$ruleLines= explode("\n", $text);

		$blank= '';

		for ($index= 0; $index < count($ruleLines); $index++) {
			$str= $ruleLines[$index];
			$words= preg_split('/[\s,\t]+/', trim($str), -1);
			
			$type= $words[0];
            // Do not search in comment lines
			if ($type !== '' && $type !== '#') {
				if (preg_match('/\s+(scrub|af-to|nat-to|binat-to|divert-to|rdr-to|route-to|reply-to|dup-to|divert-packet|state-defaults)\s+/', $str, $match)) {
					$type= $match[1];
				} elseif (preg_match('/\bset\s+(timeout|limit)\s+/', $str, $match)) {
					$type= $match[1];
				}
			}

			// Add any accumulated comment or blank lines if a non-comment/blank rule is next
			if ($type !== '' && $type !== '#') {
				if (isset($comment)) {
					$this->rules[]= new Comment($comment);
					unset($comment);
				}
				if ($blank != '') {
					$this->rules[]= new Blank($blank);
					$blank= '';
				}
			}

			if ($type === 'anchor' && preg_match('/^.*{\s*$/', $str)) {
				$this->parseInlineRules($ruleLines, $str, $index, $force);
			}

			switch ($type) {
				case '':
					if (isset($comment)) {
						$this->rules[]= new Comment($comment);
						unset($comment);
					}
					$blank.= "\n";
					break;
				case '#':
					if ($blank != '') {
						$this->rules[]= new Blank($blank);
						$blank= '';
					}
					if (!isset($comment)) {
						$comment= trim(substr($str, 1));
					} else {
						$comment.= "\n" . trim(substr($str, 1));
					}
					break;
				case 'include':
					$this->rules[]= new _Include($str);
					break;
				case 'anchor':
					$this->rules[]= new Anchor($str);
					break;
				case 'pass':
				case 'block':
				case 'match':
					$this->rules[]= new Filter($str);
					break;
				case 'antispoof':
					$this->rules[]= new Antispoof($str);
					break;
				case 'af-to':
					$this->rules[]= new AfTo($str);
					break;
				case 'nat-to':
					$this->rules[]= new NatTo($str);
					break;
				case 'binat-to':
					$this->rules[]= new BinatTo($str);
					break;
				case 'divert-to':
					$this->rules[]= new DivertTo($str);
					break;
				case 'divert-packet':
					$this->rules[]= new DivertPacket($str);
					break;
				case 'rdr-to':
					$this->rules[]= new RdrTo($str);
					break;
				case 'route-to':
				case 'reply-to':
				case 'dup-to':
					$this->rules[]= new Route($str);
					break;
				case 'timeout':
					$this->rules[]= new Timeout($str);
					break;
				case 'state-defaults':
					$this->rules[]= new State($str);
					break;
				case 'set':
					$this->rules[]= new Option($str);
					break;
				case 'limit':
					$this->rules[]= new Limit($str);
					break;
				case 'scrub':
					$this->rules[]= new Scrub($str);
					break;
				case 'table':
					$this->rules[]= new Table($str);
					break;
				case 'queue':
					$this->rules[]= new Queue($str);
					break;
				case 'load':
					$this->rules[]= new LoadAnchor($str);
					break;
				default:
					$this->rules[]= new Macro($str);
					break;
			}
		}
        
        // Necessary if there is no non-comment rule at the end of ruleset
        if (isset($comment)) {
            $this->rules[]= new Comment($comment);
        }
        /// @attention Do not append accumulated blank lines to the end
		
		return $this->validate($force);
	}

	/** 
	 * Validates the ruleset.
	 * 
	 * Since encoding and decoding the rules array produces an array with the elements we need,
	 * we reload the already loaded ruleset using json encode and decode functions. The load
	 * method validates the rules in the ruleset.
	 *
	 * @param bool $force Used to override validation or other types of errors, hence forces loading of rules.
	 */
	function validate($force= FALSE)
	{
		// Reload for validation
		$rulesArray= json_decode(json_encode($this->rules), TRUE);
		if (!$this->load($rulesArray, $force)) {
			Error(_('Load Error') . ': ' . _('Ruleset contains errors'));
			pfrec_syslog(LOG_ERR, __FILE__, __FUNCTION__, __LINE__, 'Load Error: Ruleset contains errors');
			return FALSE;
		}
		return TRUE;
	}

	/** 
	 * Collects inline anchor rules spanning multiple lines.
	 * 
	 * Inline rule starts with an opening brace at the end of the anchor line and ends with
	 * a closing brace at the beginning of the last line, possibly followed by an inline comment.
	 * 
	 * @code{.php}
	 * 	anchor "external" on egress {
	 * 		block
	 * 		anchor out {
	 * 			pass proto tcp from any to port { 25, 80, 443 }
	 * 		}
	 * 		pass in proto tcp to any port 22
	 * 	} # Inline comment
	 * @endcode
	 * 
	 * Since inline rules span multiple lines, we combine those lines and append them at the
	 * end of the anchor rule after the 'inline' keyword. This process advances the line index
	 * to the line following the last inline rule.
	 * 
	 * Nested anchor rules are allowed in inline rules. We limit the number of nesting by
	 * $MaxAnchorNesting. This limit can be overridden by the $force parameter. Otherwise,
	 * the method stops collecting and combining inline rules after $MaxAnchorNesting
	 * number of nesting is reached.
	 * 
	 * @param array $ruleLines Rule strings in array format.
	 * @param string $str Current anchor rule in string format.
	 * @param int $index Index pointing at the current line.
	 * @param bool $force Forces collection of inline rules even after nesting limit is reached.
	 */
	function parseInlineRules($ruleLines, &$str, &$index, $force= FALSE)
	{
		global $MaxAnchorNesting;

		if (preg_match('/^(.*){\s*$/', $str, $match)) {
			$str= $match[1] . ' inline ';

			$nesting= 1;
			$index++;
			while ($index < count($ruleLines)) {
				$line= $ruleLines[$index];

				// anchor-close = "}", but there may be a comment after it, hence match
				if (!preg_match('/^\s*}(.*)$/', $line, $match)) {
					$str.= "$line\n";
					/// @todo Use recursion instead?
					if (preg_match('/^.*{\s*$/', $line)) {
						// Do not allow more than $MaxAnchorNesting count of nested inline rules
						if (++$nesting > $MaxAnchorNesting) {
							Error(_('Parse Error') . ': ' . _('Reached max nesting for inline anchors') . ': <pre>' . htmlentities(print_r($line, TRUE)) . '</pre>');
							pfrec_syslog(LOG_ERR, __FILE__, __FUNCTION__, __LINE__, "Parse Error: Reached max nesting for inline anchors: $line");
							if (!$force) {
								break;
							}
						}
					}
				} else {
					if (--$nesting == 0) {
						// Discard the last anchor-close, keep the trailing text
						$str.= $match[1] . "\n";
						break;
					} else {
						// Don't discard the anchor-close of nested anchors
						$str.= "$line\n";
					}
				}
				$index++;
			}
		}
	}
	
	/** 
	 * Prints the ruleset.
	 * 
	 * We iterate over all the rule objects and ask them to return string representations of themselves.
	 * We combine these strings and print on the Display page.
	 * 
	 * The default on the Display page is to print line numbers in front of each line. 
	 *
	 * @param bool $printNumbers Print line numbers.
	 */
	function generate($printNumbers= FALSE)
	{
		$str= '';
		foreach ($this->rules as $rule) {
			$str.= $rule->generate();
		}
        
		// Do not merge this loop with the generate loop above, because there are rules which produce
		// multiline string representations, such as comments and anchors with inline rules.
		if ($printNumbers) {
			$ruleNumber= 0;
			$s= '';
			foreach (explode("\n", $str) as $line) {
				$s.= sprintf('% 4d', $ruleNumber++) . ": $line\n";
			}
			$str= $s;
		}
		return $str;
	}
}
?>
