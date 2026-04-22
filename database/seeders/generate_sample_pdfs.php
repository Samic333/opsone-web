<?php
/**
 * Mock sample PDFs for local document-flow testing.
 *
 * Produces four realistic-looking, single-page PDFs without any external deps.
 * These are used to exercise:
 *   - /files upload + download
 *   - /my-files crew viewing + acknowledgement
 *   - role targeting (pilots, cabin, engineers, all crew)
 *   - fleet / category folder placement (e.g. Dash-8 Q400)
 *   - requires_ack enforcement
 *
 * Output: storage/samples/*.pdf  (safe to commit/distribute as demo content)
 *
 * Usage: php database/seeders/generate_sample_pdfs.php
 */

function mock_pdf(string $title, string $subtitle, array $body): string {
    // Build a minimal, valid PDF with one page, single font, hand-crafted
    // cross-ref table. Good enough for preview + download testing.
    $lines = array_merge(
        [
            ['size' => 22, 'y' => 760, 'text' => $title, 'bold' => true],
            ['size' => 12, 'y' => 735, 'text' => $subtitle, 'bold' => false],
            ['size' => 9,  'y' => 720, 'text' => 'OpsOne Demo Airline — for testing only', 'bold' => false],
        ],
        []
    );

    $y = 690;
    foreach ($body as $para) {
        // crude word-wrap at ~95 chars
        $chunks = str_split($para, 95);
        foreach ($chunks as $c) {
            $lines[] = ['size' => 11, 'y' => $y, 'text' => $c, 'bold' => false];
            $y -= 16;
        }
        $y -= 8;
    }

    // Page content stream
    $stream = "BT\n";
    foreach ($lines as $l) {
        $font = $l['bold'] ? '/F2' : '/F1';
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $l['text']);
        $stream .= "$font {$l['size']} Tf\n";
        $stream .= "72 {$l['y']} Td\n";
        $stream .= "({$escaped}) Tj\n";
        $stream .= "-72 -{$l['y']} Td\n";
    }
    $stream .= "ET\n";

    $streamLen = strlen($stream);

    $objs = [];
    $objs[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objs[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objs[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] "
             . "/Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>";
    $objs[4] = "<< /Length $streamLen >>\nstream\n$stream\nendstream";
    $objs[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objs[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

    $out = "%PDF-1.4\n%âãÏÓ\n";
    $offsets = [0];
    foreach ($objs as $num => $body) {
        $offsets[$num] = strlen($out);
        $out .= "$num 0 obj\n$body\nendobj\n";
    }
    $xrefStart = strlen($out);
    $total = count($objs) + 1;
    $out .= "xref\n0 $total\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objs); $i++) {
        $out .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $out .= "trailer\n<< /Size $total /Root 1 0 R >>\nstartxref\n$xrefStart\n%%EOF";
    return $out;
}

$outDir = dirname(__DIR__, 2) . '/storage/samples';
if (!is_dir($outDir)) mkdir($outDir, 0755, true);

$files = [
    'operations_manual_v1.pdf' => [
        'Operations Manual — Part A (Demo)',
        'Version 1.4  ·  Effective 2026-04-01  ·  Pilots & Cabin Crew',
        [
            'This is a demonstration copy of Part A of the Operations Manual. It exists solely to verify the document library upload, distribution, and acknowledgement flow.',
            '1. General. The commander is responsible for safe operation of the aircraft in accordance with the Company Operations Manual and applicable regulations.',
            '2. Flight Crew Composition. For scheduled passenger operations a minimum of two qualified pilots shall be assigned to each flight.',
            '3. Fatigue Management. Pilots shall self-report fatigue using the Safety Report module. Such reports are reviewed by the Safety Manager within 24 hours.',
            '4. Acknowledgement. By acknowledging this document you confirm that you have read and understood the revised sections 3 and 4.',
            'END OF DEMO CONTENT — sample document generated for OpsOne local testing.',
        ],
    ],
    'training_notice_2026_q2.pdf' => [
        'Training Notice — 2026 Q2 Recurrents',
        'Issued: 2026-04-15  ·  Training Department',
        [
            'Recurrent training for pilots and cabin crew will take place between 05 May 2026 and 20 May 2026 at HQ Training Centre.',
            'Affected crew: Q400 pilots with recurrent due before 30 July 2026; all cabin crew with SEP expiring before 01 August 2026.',
            'Schedules will be published via the roster workbench no later than 22 April 2026. Please confirm attendance within 72 hours of receipt.',
            'Questions: training@opsone-demo.com. This notice requires acknowledgement.',
        ],
    ],
    'fleet_q400_notice.pdf' => [
        'Fleet Notice — Dash-8 Q400',
        'Issued: 2026-04-10  ·  Engineering & Chief Pilot',
        [
            'This notice applies ONLY to the Dash-8 Q400 fleet (registrations ET-QAA through ET-QAF).',
            'Effective 15 Apr 2026 the minimum reserve fuel for domestic sectors is increased from 30 to 45 minutes due to seasonal ATC holding patterns at ADD.',
            'Flight plans and briefings will be updated automatically. Commanders should verify reserve on the release.',
            'This is a fleet-specific document and should only be visible to Q400-type-rated crew and engineers.',
        ],
    ],
    'ack_required_ops_notice.pdf' => [
        'Operational Notice — Mandatory Acknowledgement',
        'Issued: 2026-04-20  ·  Flight Operations',
        [
            'CHANGES TO DUTY REPORTING PROCEDURE',
            'Starting 01 May 2026, all pilots, cabin crew, and engineers must check in via the CrewAssist iPad app or web portal within 30 minutes of their scheduled report time.',
            'Exceptions (late arrivals, substitutions) must be raised through the Duty Exceptions queue; your manager or scheduler will action the request.',
            'This notice MUST be acknowledged before 30 April 2026. Unacknowledged status will be flagged on your Compliance card.',
        ],
    ],
];

foreach ($files as $name => [$title, $subtitle, $body]) {
    $path = "$outDir/$name";
    file_put_contents($path, mock_pdf($title, $subtitle, $body));
    $size = number_format(filesize($path));
    echo "✓ $name — $size bytes\n";
}

echo "\nAll demo PDFs written to: $outDir\n";
echo "You can upload them via /files/upload to exercise the document flow.\n";
