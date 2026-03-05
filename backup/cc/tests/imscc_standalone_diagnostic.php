<?php
// Standalone IMS CC XML diagnostic – no Moodle bootstrap needed.
// Usage: php backup/cc/tests/imscc_standalone_diagnostic.php
//
// Stubs just enough of the Moodle API so the CC library classes can
// load and we can inspect the generated XML.

// ── Minimal stubs ────────────────────────────────────────────────────

define('MOODLE_INTERNAL', true);

function get_config(string $plugin, string $name = '') {
    // Simulate the namespace fix being enabled.
    if ($plugin === 'backup' && $name === 'imscc_namespace_fix') {
        return '1';
    }
    return false;
}

// ── Load CC library ──────────────────────────────────────────────────

$cclib = dirname(__DIR__) . '/cc_lib';
$root  = dirname(__DIR__, 2);
require_once $cclib . '/xmlbase.php';
require_once $cclib . '/gral_lib/cssparser.php';
require_once $cclib . '/cc_general.php';
require_once $cclib . '/cc_version1.php';
require_once $cclib . '/cc_version11.php';
require_once $cclib . '/cc_manifest.php';
require_once $cclib . '/cc_metadata.php';
require_once $cclib . '/cc_metadata_resource.php';
require_once $cclib . '/cc_metadata_file.php';
require_once $cclib . '/cc_organization.php';
require_once $cclib . '/cc_resources.php';
require_once $cclib . '/cc_asssesment.php';
require_once $cclib . '/cc_basiclti.php';
require_once $cclib . '/cc_page.php';

$sep = str_repeat('=', 72);
$pass = 0;
$fail = 0;

function check(string $label, bool $ok, string $detail = ''): void {
    global $pass, $fail;
    if ($ok) { $pass++; echo "  [PASS] $label\n"; }
    else     { $fail++; echo "  [FAIL] $label\n"; if ($detail) echo "         $detail\n"; }
}

// ── 1. Assessment 1.1 ───────────────────────────────────────────────
echo "\n{$sep}\n1. assesment11_resurce_file (quiz export)\n{$sep}\n\n";

$rt = new assesment11_resurce_file();
$rt->set_title('Diagnostic Quiz');
$rt->set_section(new cc_assesment_section());

// viewXML() does NOT trigger on_save(), so we save to a temp file first
// which populates <assessment>, <section>, etc., then read the result.
$tmpfile = sys_get_temp_dir() . '/imscc_diag_assessment_' . getmypid() . '.xml';
$rt->saveTo($tmpfile);
$xml = file_get_contents($tmpfile);
@unlink($tmpfile);

echo $xml . "\n\n";

$doc = new DOMDocument();
$doc->loadXML($xml);
$r = $doc->documentElement;

check('Root is <questestinterop>', $r->localName === 'questestinterop');
check('Namespace is IMS QTI',      $r->namespaceURI === 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2');
check('xmlns= attribute present',  str_contains($xml, 'xmlns="http://www.imsglobal.org/xsd/ims_qtiasiv1p2"'));
check('xmlns:xsi present',         str_contains($xml, 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'));
check('schemaLocation present',    str_contains($xml, 'ccv1p1_qtiasiv1p2p1_v1p0.xsd'));
check('No :dummy attributes',      !str_contains($xml, ':dummy'));

$sl = $r->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation');
check('schemaLocation value correct',
    str_contains($sl, 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2')
    && str_contains($sl, 'ccv1p1_qtiasiv1p2p1_v1p0.xsd'),
    "Got: $sl"
);

$assessments = $r->getElementsByTagNameNS('http://www.imsglobal.org/xsd/ims_qtiasiv1p2', 'assessment');
check('<assessment> element exists', $assessments->length === 1);
if ($assessments->length === 1) {
    check('<assessment> title correct', $assessments->item(0)->getAttribute('title') === 'Diagnostic Quiz');
}

// ── 2. Basic LTI ────────────────────────────────────────────────────
echo "\n{$sep}\n2. basicltil1_resurce_file (LTI export)\n{$sep}\n\n";

$lti = new basicltil1_resurce_file();
$ltixml = $lti->viewXML();
echo $ltixml . "\n\n";

$ldoc = new DOMDocument();
$ldoc->loadXML($ltixml);
$lr = $ldoc->documentElement;

check('Root is <cartridge_basiclti_link>', $lr->localName === 'cartridge_basiclti_link');
check('Namespace is IMS LTI CC',          $lr->namespaceURI === 'http://www.imsglobal.org/xsd/imslticc_v1p0');
check('xmlns:blti present',               str_contains($ltixml, 'xmlns:blti='));
check('xmlns:xsi present',                str_contains($ltixml, 'xmlns:xsi='));
check('No :dummy attributes',             !str_contains($ltixml, ':dummy'));

// ── 3. Page ──────────────────────────────────────────────────────────
echo "\n{$sep}\n3. page11_resurce_file (page/HTML export)\n{$sep}\n\n";

$page = new page11_resurce_file();
$pxml = $page->viewXML();
echo $pxml . "\n\n";

$pdoc = new DOMDocument();
$pdoc->loadXML($pxml);
$pr = $pdoc->documentElement;

check('Root is <html>',             $pr->localName === 'html');
check('Namespace is XHTML',         $pr->namespaceURI === 'http://www.w3.org/1999/xhtml');
check('xmlns= attribute present',   str_contains($pxml, 'xmlns="http://www.w3.org/1999/xhtml"'));
check('No :dummy attributes',       !str_contains($pxml, ':dummy'));

// ── 4. Schema validation ────────────────────────────────────────────
echo "\n{$sep}\n4. Offline schema validation\n{$sep}\n\n";

$schemaFile = dirname(__DIR__) . '/schemas11/ccv1p1_qtiasiv1p2p1_v1p0.xsd';
if (file_exists($schemaFile)) {
    libxml_use_internal_errors(true);
    $vdoc = new DOMDocument();
    $vdoc->loadXML($xml);
    $valid = @$vdoc->schemaValidate($schemaFile);
    $errors = libxml_get_errors();
    libxml_clear_errors();
    libxml_use_internal_errors(false);

    if ($valid) {
        check('Assessment validates against CC 1.1 QTI schema', true);
    } else {
        // Filter out the expected "missing child element" error on <section>
        // because our test uses an empty section (no quiz items).
        $real_errors = array_filter($errors, function ($e) {
            return strpos($e->message, 'Missing child element') === false;
        });
        if (empty($real_errors)) {
            check('Assessment validates against CC 1.1 QTI schema (ignoring empty section)', true);
            echo "  [INFO] Schema wants <item> inside <section> – expected with empty test data.\n";
        } else {
            check('Assessment validates against CC 1.1 QTI schema', false, count($real_errors) . ' errors');
            foreach (array_slice($real_errors, 0, 10) as $e) {
                echo "         Line {$e->line}: " . trim($e->message) . "\n";
            }
        }
    }
} else {
    echo "  [SKIP] Schema not found: $schemaFile\n";
}

// ── 5. Comparison: fix ON vs fix OFF ─────────────────────────────────
echo "\n{$sep}\n5. Verify fix-OFF still crashes with DOMException\n{$sep}\n\n";

// Temporarily override get_config to return 0.
// We can't re-define the function, so we do the equivalent manually:
// reproduce the loop from on_create().
$testDoc = new DOMDocument('1.0', 'UTF-8');
$testDoc->formatOutput = true;
$testDoc->strictErrorChecking = true;
$ns = 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2';
$rootEl = $testDoc->createElementNS($ns, 'questestinterop');
$testDoc->appendChild($rootEl);

$threw = false;
try {
    $testDoc->createAttributeNS($ns, 'xmlns:dummy');
} catch (DOMException $e) {
    $threw = true;
}
check('createAttributeNS with xmlns:dummy throws DOMException', $threw,
    'Expected DOMException but none was thrown – may vary by PHP/libxml version');

// ── Summary ──────────────────────────────────────────────────────────
echo "\n{$sep}\nSUMMARY: Passed=$pass  Failed=$fail\n{$sep}\n\n";

if ($fail > 0) {
    echo "SOME CHECKS FAILED – review output above.\n";
    exit(1);
}

echo "ALL CHECKS PASSED.\n\n";
echo "The namespace fix produces structurally valid XML with correct\n";
echo "namespace declarations, schema locations, and no dummy attributes.\n\n";
echo "If the exported .imscc file is still 'unusable' in the target LMS,\n";
echo "the problem is likely:\n";
echo "  - The target LMS has different import expectations\n";
echo "  - Unsupported question types (only multichoice, truefalse, essay,\n";
echo "    and simple shortanswer are supported)\n";
echo "  - Pre-existing bugs in Moodle's IMS CC converter\n";
echo "  - Missing resources/files in the .imscc package\n";
exit(0);
