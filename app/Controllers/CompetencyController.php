<?php
/**
 * CompetencyController — personal competency snapshot.
 *
 * Aggregates the per-attribute ratings stored on the `appraisals.ratings`
 * JSON column across all *accepted* appraisals about the current user.
 * No new tables — pure read aggregation over the existing appraisal store
 * the iPad app already writes to.
 *
 * The five attributes mirror Section Three of the Field Stations Staff
 * Appraisal Form (Form No. 150, Rev 01) and the
 * `AppraisalController::COMPETENCY_ATTRIBUTES` constant.
 */
class CompetencyController {

    private const ATTRIBUTES = [
        'communication'         => 'Communication skills',
        'professionalism'       => 'Professionalism',
        'leadership'            => 'Leadership skills',
        'team_spirit'           => 'Team spirit',
        'resource_management'   => 'Resource management / Efficiency',
    ];

    public function index(): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $userId   = (int)currentUser()['id'];

        // Pull every accepted appraisal about me that has a ratings JSON.
        // Filtering on JSON in SQLite means we just fetch all and parse in
        // PHP — the dataset is bounded by an individual's appraisal count.
        $rows = Database::fetchAll(
            "SELECT a.id, a.period_from, a.period_to, a.rotation_ref, a.rating_overall,
                    a.ratings, a.reviewed_at, a.appraiser_id,
                    ua.name AS appraiser_name
               FROM appraisals a
               JOIN users ua ON a.appraiser_id = ua.id
              WHERE a.tenant_id = ? AND a.subject_id = ? AND a.status = 'accepted'
              ORDER BY a.period_to DESC, a.id DESC",
            [$tenantId, $userId]
        );

        // attribute → [scores...]
        $bucket = array_fill_keys(array_keys(self::ATTRIBUTES), []);
        $sourceRecords = [];
        foreach ($rows as $r) {
            $decoded = $r['ratings'] ? json_decode((string)$r['ratings'], true) : null;
            if (!is_array($decoded)) $decoded = [];
            foreach ($bucket as $key => $_) {
                if (isset($decoded[$key]) && is_numeric($decoded[$key])) {
                    $score = max(1, min(5, (int)$decoded[$key]));
                    $bucket[$key][] = $score;
                }
            }
            $sourceRecords[] = $r + ['_decoded' => $decoded];
        }

        // Compute summary stats per attribute.
        $summary = [];
        foreach (self::ATTRIBUTES as $key => $label) {
            $scores = $bucket[$key];
            $count  = count($scores);
            $avg    = $count > 0 ? array_sum($scores) / $count : null;
            $latest = $count > 0 ? end($scores) : null;
            $summary[$key] = [
                'label'  => $label,
                'count'  => $count,
                'avg'    => $avg,
                'latest' => $latest,
            ];
        }

        $totalAccepted = count($rows);
        $overallScores = array_filter(array_map(fn($r) => (int)$r['rating_overall'], $rows));
        $overallAvg = $overallScores ? array_sum($overallScores) / count($overallScores) : null;

        $pageTitle    = 'Competency Records';
        $pageSubtitle = 'Aggregated competency profile from accepted peer appraisals';

        ob_start();
        require VIEWS_PATH . '/competency/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }
}
