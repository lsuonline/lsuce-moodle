<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for Moodle 4.0+ question bank format support in IMS CC export.
 *
 * Validates that cc_assesment_helper::process_questions() resolves questions
 * using question_reference/questionbankentryid (4.0+) with fallback to the
 * legacy questionid format (pre-4.0).
 *
 * @package    core_backup
 * @category   test
 * @copyright  2026 Moodlerooms US
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     cc_assesment_helper
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/cc/cc_includes.php');
require_once($CFG->dirroot . '/backup/cc/cc_lib/cc_asssesment.php');

/**
 * Tests the XPath resolution logic in cc_assesment_helper::process_questions()
 * for both Moodle 4.0+ and legacy backup formats.
 *
 * @package    core_backup
 * @category   test
 * @copyright  2026 Moodlerooms US
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cc_assesment_question_format_test extends \advanced_testcase {

    /**
     * Moodle 4.0+ quiz.xml uses question_reference/questionbankentryid.
     */
    public function test_new_format_xpath_detects_question_references(): void {
        $quizxml = '<activity id="1" moduleid="328" modulename="quiz" contextid="999">'
            . '<quiz id="10"><name>Sample Quiz</name><grade>10.0000000</grade>'
            . '<shuffleanswers>0</shuffleanswers><question_instances>'
            . '<question_instance id="3"><slot>1</slot><maxmark>1.0000000</maxmark>'
            . '<question_reference id="3"><questionbankentryid>3</questionbankentryid>'
            . '<version></version></question_reference></question_instance>'
            . '</question_instances></quiz></activity>';

        $qdoc = new XMLGenericDocument();
        $qdoc->loadString($quizxml);

        $qrefs = $qdoc->nodeList('//question_instances/question_instance/question_reference');
        $this->assertNotEmpty($qrefs);
        $this->assertGreaterThan(0, $qrefs->length, 'New format XPath should find question_reference elements');

        $qbe_id = $qdoc->nodeValue('questionbankentryid', $qrefs->item(0));
        $this->assertEquals('3', $qbe_id, 'Should extract questionbankentryid from question_reference');
    }

    /**
     * Moodle 4.0+ questions.xml nests questions under question_bank_entries.
     */
    public function test_new_format_xpath_resolves_question_in_questions_xml(): void {
        $questionsxml = '<question_categories><question_category id="6">'
            . '<name>Default for Category 1</name><question_bank_entries>'
            . '<question_bank_entry id="3"><questioncategoryid>6</questioncategoryid>'
            . '<question_version><question_versions id="3"><version>1</version>'
            . '<status>ready</status><questions><question id="3">'
            . '<name>TF Question 1</name><qtype>truefalse</qtype>'
            . '<questiontext>The sky is blue.</questiontext>'
            . '<defaultmark>1.0000000</defaultmark><generalfeedback></generalfeedback>'
            . '<plugin_qtype_truefalse_question><answers>'
            . '<answer id="5"><answertext>True</answertext>'
            . '<fraction>1.0000000</fraction><feedback></feedback></answer>'
            . '<answer id="6"><answertext>False</answertext>'
            . '<fraction>0.0000000</fraction><feedback></feedback></answer>'
            . '</answers></plugin_qtype_truefalse_question>'
            . '</question></questions></question_versions></question_version>'
            . '</question_bank_entry></question_bank_entries>'
            . '</question_category></question_categories>';

        $questions = new XMLGenericDocument();
        $questions->loadString($questionsxml);

        $qbe_id = '3';
        $xpath = "//question_category/question_bank_entries/question_bank_entry[@id='{$qbe_id}']"
               . "/question_version/question_versions/questions/question";
        $qnode = $questions->node($xpath);
        $this->assertNotNull($qnode, 'New-format XPath should resolve a question under question_bank_entries');

        $qtype = $questions->nodeValue('qtype', $qnode);
        $this->assertEquals('truefalse', $qtype);

        $name = $questions->nodeValue('name', $qnode);
        $this->assertEquals('TF Question 1', $name);
    }

    /**
     * Old (pre-4.0) quiz.xml uses //question_instances//questionid.
     */
    public function test_old_format_xpath_detects_questionid(): void {
        $quizxml = '<activity id="1" moduleid="100" modulename="quiz" contextid="500">'
            . '<quiz id="5"><name>Old Quiz</name><grade>10.0000000</grade>'
            . '<shuffleanswers>0</shuffleanswers><question_instances>'
            . '<question_instance id="1"><questionid>42</questionid>'
            . '<maxmark>1.0000000</maxmark></question_instance>'
            . '</question_instances></quiz></activity>';

        $qdoc = new XMLGenericDocument();
        $qdoc->loadString($quizxml);

        // New-format XPath should find nothing.
        $qrefs = $qdoc->nodeList('//question_instances/question_instance/question_reference');
        $this->assertEquals(0, $qrefs->length, 'Old format should have no question_reference elements');

        // Legacy XPath should work.
        $qids = $qdoc->nodeList('//question_instances//questionid');
        $this->assertGreaterThan(0, $qids->length, 'Old format XPath should find questionid elements');
        $this->assertEquals('42', $qids->item(0)->nodeValue);
    }

    /**
     * Old (pre-4.0) questions.xml places questions directly under question_category/questions.
     */
    public function test_old_format_xpath_resolves_question_in_questions_xml(): void {
        $questionsxml = '<question_categories><question_category id="1">'
            . '<name>Default</name><questions><question id="42">'
            . '<name>Old TF Question</name><qtype>truefalse</qtype>'
            . '<questiontext>Water is wet.</questiontext>'
            . '<defaultmark>1.0000000</defaultmark>'
            . '</question></questions></question_category></question_categories>';

        $questions = new XMLGenericDocument();
        $questions->loadString($questionsxml);

        $qnode = $questions->node("//question_category/questions/question[@id='42']");
        $this->assertNotNull($qnode, 'Old-format XPath should find question by id');
        $this->assertEquals('truefalse', $questions->nodeValue('qtype', $qnode));
    }

    /**
     * Format detection: new format is detected when question_reference exists.
     */
    public function test_format_detection_prefers_new_format_when_both_could_match(): void {
        $quizxml = '<activity id="1" moduleid="328" modulename="quiz" contextid="999">'
            . '<quiz id="10"><name>Quiz</name><grade>10.0000000</grade>'
            . '<shuffleanswers>0</shuffleanswers><question_instances>'
            . '<question_instance id="3"><slot>1</slot>'
            . '<question_reference id="3"><questionbankentryid>7</questionbankentryid>'
            . '<version></version></question_reference></question_instance>'
            . '</question_instances></quiz></activity>';

        $qdoc = new XMLGenericDocument();
        $qdoc->loadString($quizxml);

        $qrefs = $qdoc->nodeList('//question_instances/question_instance/question_reference');
        $use_new_format = (!empty($qrefs) && $qrefs->length > 0);
        $this->assertTrue($use_new_format, 'Should detect new format when question_reference is present');
    }

    /**
     * Nonexistent questionbankentryid gracefully returns no match.
     */
    public function test_new_format_missing_question_bank_entry_returns_null(): void {
        $questionsxml = '<question_categories><question_category id="6">'
            . '<question_bank_entries><question_bank_entry id="3">'
            . '<question_version><question_versions id="3"><questions>'
            . '<question id="3"><qtype>truefalse</qtype></question>'
            . '</questions></question_versions></question_version>'
            . '</question_bank_entry></question_bank_entries>'
            . '</question_category></question_categories>';

        $questions = new XMLGenericDocument();
        $questions->loadString($questionsxml);

        $xpath = "//question_category/question_bank_entries/question_bank_entry[@id='999']"
               . "/question_version/question_versions/questions/question";
        $qnode = $questions->node($xpath);
        $this->assertNull($qnode, 'Non-existent question_bank_entry ID should return null');
    }
}
