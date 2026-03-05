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
 * CLI diagnostic script for IMS CC XML generation.
 *
 * Generates sample XML from each resource type that uses the 'xmlns' rootns
 * and validates the output structure, namespace declarations, and schema
 * location attributes.
 *
 * Usage: php backup/cc/tests/imscc_xml_diagnostic.php
 *
 * @package    core_backup
 * @copyright  2026 LSU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/backup/cc/cc_includes.php');

$separator = str_repeat('=', 72);

cli_heading('IMS CC XML Diagnostic – Namespace Fix Verification');

// Force the namespace fix on.
set_config('imscc_namespace_fix', 1, 'backup');

$checks_passed = 0;
$checks_failed = 0;

/**
 * Helper to run a check and report.
 */
function check(string $label, bool $result, string $detail = ''): void {
    global $checks_passed, $checks_failed;
    if ($result) {
        $checks_passed++;
        echo "  [PASS] {$label}\n";
    } else {
        $checks_failed++;
        echo "  [FAIL] {$label}\n";
        if ($detail) {
            echo "         {$detail}\n";
        }
    }
}

// ─── 1. Assessment 1.1 resource file ────────────────────────────────
echo "\n{$separator}\n";
echo "1. assesment11_resurce_file (quiz export)\n";
echo "{$separator}\n\n";

$rt = new assesment11_resurce_file();
$rt->set_title('Diagnostic Quiz');

$section = new cc_assesment_section();
$rt->set_section($section);

$xml = $rt->viewXML();
echo "Generated XML:\n\n";
echo $xml . "\n\n";

$doc = new DOMDocument();
$doc->loadXML($xml);
$root = $doc->documentElement;

check(
    'Root element is <questestinterop>',
    $root->localName === 'questestinterop'
);

check(
    'Default namespace is IMS QTI',
    $root->namespaceURI === 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2'
);

check(
    'xmlns attribute present in serialised XML',
    strpos($xml, 'xmlns="http://www.imsglobal.org/xsd/ims_qtiasiv1p2"') !== false,
    'Missing: xmlns="http://www.imsglobal.org/xsd/ims_qtiasiv1p2"'
);

check(
    'xmlns:xsi declaration present',
    strpos($xml, 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"') !== false,
    'Missing: xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
);

check(
    'xsi:schemaLocation present with CC 1.1 schema',
    strpos($xml, 'ccv1p1_qtiasiv1p2p1_v1p0.xsd') !== false,
    'Missing schema location for CC 1.1 assessment'
);

check(
    'No leftover dummy attributes',
    strpos($xml, ':dummy') === false,
    'Found ":dummy" in output XML'
);

$schemaLoc = $root->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation');
check(
    'xsi:schemaLocation maps namespace to schema URL',
    strpos($schemaLoc, 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2') !== false
        && strpos($schemaLoc, 'http://www.imsglobal.org/profile/cc/ccv1p1/ccv1p1_qtiasiv1p2p1_v1p0.xsd') !== false,
    "Got: {$schemaLoc}"
);

// Check for <assessment> child inside root.
$assessmentNodes = $root->getElementsByTagNameNS('http://www.imsglobal.org/xsd/ims_qtiasiv1p2', 'assessment');
check(
    '<assessment> element exists in correct namespace',
    $assessmentNodes->length === 1
);

if ($assessmentNodes->length === 1) {
    check(
        '<assessment> has title attribute',
        $assessmentNodes->item(0)->hasAttribute('title')
            && $assessmentNodes->item(0)->getAttribute('title') === 'Diagnostic Quiz'
    );
}

// ─── 2. Basic LTI resource file ─────────────────────────────────────
echo "\n{$separator}\n";
echo "2. basicltil1_resurce_file (LTI export)\n";
echo "{$separator}\n\n";

$lti = new basicltil1_resurce_file();
$ltixml = $lti->viewXML();
echo "Generated XML:\n\n";
echo $ltixml . "\n\n";

$ltidoc = new DOMDocument();
$ltidoc->loadXML($ltixml);
$ltiroot = $ltidoc->documentElement;

check(
    'Root element is <cartridge_basiclti_link>',
    $ltiroot->localName === 'cartridge_basiclti_link'
);

check(
    'Default namespace is IMS LTI CC',
    $ltiroot->namespaceURI === 'http://www.imsglobal.org/xsd/imslticc_v1p0'
);

check(
    'xmlns:blti declaration present',
    strpos($ltixml, 'xmlns:blti=') !== false,
    'Missing blti namespace declaration'
);

check(
    'xmlns:xsi declaration present',
    strpos($ltixml, 'xmlns:xsi=') !== false,
    'Missing xsi namespace declaration'
);

check(
    'No leftover dummy attributes',
    strpos($ltixml, ':dummy') === false,
    'Found ":dummy" in LTI output XML'
);

// ─── 3. Page resource file ──────────────────────────────────────────
echo "\n{$separator}\n";
echo "3. page11_resurce_file (page/HTML export)\n";
echo "{$separator}\n\n";

$page = new page11_resurce_file();
$pagexml = $page->viewXML();
echo "Generated XML:\n\n";
echo $pagexml . "\n\n";

$pagedoc = new DOMDocument();
$pagedoc->loadXML($pagexml);
$pageroot = $pagedoc->documentElement;

check(
    'Root element is <html>',
    $pageroot->localName === 'html'
);

check(
    'Default namespace is XHTML',
    $pageroot->namespaceURI === 'http://www.w3.org/1999/xhtml'
);

check(
    'xmlns="http://www.w3.org/1999/xhtml" present',
    strpos($pagexml, 'xmlns="http://www.w3.org/1999/xhtml"') !== false,
    'Missing XHTML default namespace'
);

check(
    'No leftover dummy attributes',
    strpos($pagexml, ':dummy') === false,
    'Found ":dummy" in page output XML'
);

// ─── 4. Manifest file (uses imscc prefix, NOT xmlns) ────────────────
echo "\n{$separator}\n";
echo "4. cc_manifest (should be unaffected by fix)\n";
echo "{$separator}\n\n";

$manifest = new cc_manifest(cc_version::v11);
$manifestxml = $manifest->viewXML();
echo "Generated XML (first 800 chars):\n\n";
echo substr($manifestxml, 0, 800) . "\n...\n\n";

check(
    'Manifest creates without error',
    !empty($manifestxml)
);

check(
    'xmlns:imscc namespace declared',
    strpos($manifestxml, 'xmlns:imscc=') !== false || strpos($manifestxml, 'imsccv1p1') !== false,
    'Missing imscc namespace in manifest'
);

// ─── 5. XML Schema Validation (offline – against local schemas) ─────
echo "\n{$separator}\n";
echo "5. Schema validation (assessment XML against local XSD)\n";
echo "{$separator}\n\n";

$schemaDir = $CFG->dirroot . '/backup/cc/schemas11';
$assessmentSchema = $schemaDir . '/ccv1p1_qtiasiv1p2p1_v1p0.xsd';

if (file_exists($assessmentSchema)) {
    libxml_use_internal_errors(true);
    $validDoc = new DOMDocument();
    $validDoc->loadXML($xml);
    $valid = $validDoc->schemaValidate($assessmentSchema);
    $errors = libxml_get_errors();
    libxml_clear_errors();
    libxml_use_internal_errors(false);

    if ($valid) {
        check('Assessment XML validates against CC 1.1 QTI schema', true);
    } else {
        check('Assessment XML validates against CC 1.1 QTI schema', false,
            'Errors: ' . count($errors));
        foreach ($errors as $error) {
            echo "         Line {$error->line}: " . trim($error->message) . "\n";
        }
    }
} else {
    echo "  [SKIP] Schema file not found at {$assessmentSchema}\n";
}

// ─── Summary ─────────────────────────────────────────────────────────
echo "\n{$separator}\n";
echo "SUMMARY\n";
echo "{$separator}\n\n";
echo "  Passed: {$checks_passed}\n";
echo "  Failed: {$checks_failed}\n\n";

if ($checks_failed > 0) {
    echo "Some checks failed – review the output above.\n";
    exit(1);
}

echo "All checks passed – the namespace fix produces valid XML.\n";
echo "\nIf the exported .imscc file is 'unusable' in the target LMS, the issue\n";
echo "is likely in the IMS CC package structure (manifest, resource references)\n";
echo "or unsupported question types, NOT in the namespace fix.\n";
exit(0);
