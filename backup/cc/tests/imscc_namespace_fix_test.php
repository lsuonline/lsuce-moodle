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
 * Unit tests for the IMS CC namespace fix.
 *
 * LSU added - Tests the fix for DOMException "Namespace Error" when exporting
 * courses with quizzes to IMS Common Cartridge format. The fix skips the
 * reserved 'xmlns' prefix in general_cc_file::on_create() to avoid
 * createAttributeNS() with an invalid qualified name.
 *
 * @package    core_backup
 * @category   test
 * @copyright  2026 LSU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     general_cc_file
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/cc/cc_includes.php');

/**
 * Unit tests for IMS CC namespace fix.
 *
 * LSU added - See file docblock for details.
 *
 * @package    core_backup
 * @category   test
 * @copyright  2026 LSU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class imscc_namespace_fix_test extends \advanced_testcase {

    /**
     * Test that assesment11_resurce_file can be created when the fix is enabled.
     */
    public function test_namespace_fix_enabled_creates_assessment_without_error(): void {
        $this->resetAfterTest(true);

        set_config('imscc_namespace_fix', 1, 'backup');

        $rt = new assesment11_resurce_file();
        $this->assertInstanceOf(assesment11_resurce_file::class, $rt);
    }

    /**
     * Test that assesment11_resurce_file can be created when config is not set (defaults to fix).
     */
    public function test_namespace_fix_defaults_to_enabled_when_config_not_set(): void {
        $this->resetAfterTest(true);

        unset_config('imscc_namespace_fix', 'backup');

        $rt = new assesment11_resurce_file();
        $this->assertInstanceOf(assesment11_resurce_file::class, $rt);
    }

    /**
     * Test that assesment11_resurce_file throws DOMException when fix is disabled.
     */
    public function test_namespace_fix_disabled_throws_domexception(): void {
        $this->resetAfterTest(true);

        set_config('imscc_namespace_fix', 0, 'backup');

        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('Namespace Error');

        new assesment11_resurce_file();
    }

    /**
     * Test that assessment XML has correct default namespace on root element.
     */
    public function test_assessment_xml_has_default_namespace(): void {
        $this->resetAfterTest(true);
        set_config('imscc_namespace_fix', 1, 'backup');

        $rt = new assesment11_resurce_file();
        $rt->set_title('Test Quiz');
        $rt->set_section(new cc_assesment_section());

        $xml = $rt->viewXML();
        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $this->assertSame('questestinterop', $doc->documentElement->localName);
        $this->assertSame(
            'http://www.imsglobal.org/xsd/ims_qtiasiv1p2',
            $doc->documentElement->namespaceURI
        );
        $this->assertStringContainsString(
            'xmlns="http://www.imsglobal.org/xsd/ims_qtiasiv1p2"',
            $xml
        );
    }

    /**
     * Test that assessment XML has xsi namespace and schemaLocation.
     */
    public function test_assessment_xml_has_xsi_schema_location(): void {
        $this->resetAfterTest(true);
        set_config('imscc_namespace_fix', 1, 'backup');

        $rt = new assesment11_resurce_file();
        $rt->set_title('Test Quiz');
        $rt->set_section(new cc_assesment_section());

        $xml = $rt->viewXML();

        $this->assertStringContainsString(
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
            $xml
        );

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $schemaLoc = $doc->documentElement->getAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'schemaLocation'
        );
        $this->assertStringContainsString(
            'http://www.imsglobal.org/xsd/ims_qtiasiv1p2',
            $schemaLoc
        );
        $this->assertStringContainsString(
            'ccv1p1_qtiasiv1p2p1_v1p0.xsd',
            $schemaLoc
        );
    }

    /**
     * Test that assessment XML does not contain leftover dummy attributes.
     */
    public function test_assessment_xml_has_no_dummy_attributes(): void {
        $this->resetAfterTest(true);
        set_config('imscc_namespace_fix', 1, 'backup');

        $rt = new assesment11_resurce_file();
        $xml = $rt->viewXML();

        $this->assertStringNotContainsString(':dummy', $xml);
    }

    /**
     * Test that assessment attributes are unqualified (no namespace prefix).
     *
     * The XML default namespace does NOT apply to attributes. Attributes like
     * ident and title must be plain (e.g. ident="...") not prefixed
     * (e.g. default:ident="...").
     */
    public function test_assessment_attributes_are_unqualified(): void {
        $this->resetAfterTest(true);
        set_config('imscc_namespace_fix', 1, 'backup');

        $rt = new assesment11_resurce_file();
        $rt->set_title('Test Quiz');
        $rt->set_section(new cc_assesment_section());

        $tmpfile = make_temp_directory('imscc_test') . '/attr_test.xml';
        $rt->saveTo($tmpfile);
        $xml = file_get_contents($tmpfile);
        @unlink($tmpfile);

        $this->assertStringNotContainsString('default:', $xml);
        $this->assertStringNotContainsString('default0:', $xml);
        $this->assertStringNotContainsString('default1:', $xml);
        $this->assertStringNotContainsString('default2:', $xml);

        $this->assertMatchesRegularExpression('/ident="[^"]*"/', $xml);
        $this->assertMatchesRegularExpression('/title="[^"]*"/', $xml);
    }

    /**
     * Test that assessment child elements are in the correct namespace
     * and have correct unqualified attributes after save.
     */
    public function test_assessment_child_elements_in_correct_namespace(): void {
        $this->resetAfterTest(true);
        set_config('imscc_namespace_fix', 1, 'backup');

        $rt = new assesment11_resurce_file();
        $rt->set_title('Test Quiz');
        $rt->set_section(new cc_assesment_section());

        $tmpfile = make_temp_directory('imscc_test') . '/child_test.xml';
        $rt->saveTo($tmpfile);
        $xml = file_get_contents($tmpfile);
        @unlink($tmpfile);

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $ns = 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2';
        $assessments = $doc->documentElement->getElementsByTagNameNS($ns, 'assessment');
        $this->assertSame(1, $assessments->length, '<assessment> element must exist');
        $this->assertSame('Test Quiz', $assessments->item(0)->getAttribute('title'));
    }

    /**
     * Test that basicltil1_resurce_file creates valid XML with namespace fix.
     */
    public function test_basiclti_xml_has_correct_namespaces(): void {
        $this->resetAfterTest(true);
        set_config('imscc_namespace_fix', 1, 'backup');

        $lti = new basicltil1_resurce_file();
        $xml = $lti->viewXML();

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $this->assertSame('cartridge_basiclti_link', $doc->documentElement->localName);
        $this->assertSame(
            'http://www.imsglobal.org/xsd/imslticc_v1p0',
            $doc->documentElement->namespaceURI
        );

        $this->assertStringContainsString('xmlns:blti=', $xml);
        $this->assertStringContainsString('xmlns:xsi=', $xml);
        $this->assertStringNotContainsString(':dummy', $xml);
    }

    /**
     * Test that page11_resurce_file creates valid XHTML output.
     */
    public function test_page_xml_has_correct_xhtml_namespace(): void {
        $this->resetAfterTest(true);
        set_config('imscc_namespace_fix', 1, 'backup');

        $page = new page11_resurce_file();
        $xml = $page->viewXML();

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $this->assertSame('html', $doc->documentElement->localName);
        $this->assertSame(
            'http://www.w3.org/1999/xhtml',
            $doc->documentElement->namespaceURI
        );
        $this->assertStringNotContainsString(':dummy', $xml);
    }

    /**
     * Test that the assessment XML can be saved to a temp file and reloaded.
     */
    public function test_assessment_xml_round_trip(): void {
        $this->resetAfterTest(true);
        set_config('imscc_namespace_fix', 1, 'backup');

        $rt = new assesment11_resurce_file();
        $rt->set_title('Round Trip Quiz');
        $rt->set_section(new cc_assesment_section());

        $tmpfile = make_temp_directory('imscc_test') . '/assessment_test.xml';
        $rt->saveTo($tmpfile);

        $this->assertFileExists($tmpfile);

        $reloaded = new DOMDocument();
        $reloaded->load($tmpfile);
        $root = $reloaded->documentElement;

        $this->assertSame('questestinterop', $root->localName);
        $this->assertSame(
            'http://www.imsglobal.org/xsd/ims_qtiasiv1p2',
            $root->namespaceURI
        );

        $assessments = $root->getElementsByTagNameNS(
            'http://www.imsglobal.org/xsd/ims_qtiasiv1p2',
            'assessment'
        );
        $this->assertSame(1, $assessments->length);

        @unlink($tmpfile);
    }
}
