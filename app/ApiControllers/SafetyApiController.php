<?php
/**
 * SafetyApiController — Safety Reporting API (iPad / CrewAssist)
 *
 * All endpoints require bearer-token auth (enforced by ApiAuthMiddleware).
 * All responses are JSON. Tenant isolation is enforced throughout.
 *
 * Routes (see config/routes.php):
 *   GET    /api/safety/types               — list available report types for this user
 *   GET    /api/safety/my-reports          — reporter's submitted/closed reports
 *   GET    /api/safety/drafts              — reporter's drafts
 *   GET    /api/safety/report/{id}         — single report detail + thread + attachments
 *   POST   /api/safety/report              — save draft OR submit ({..., is_draft: bool})
 *   PUT    /api/safety/report/{id}         — update draft (must own it + still be draft)
 *   DELETE /api/safety/report/{id}/draft   — delete a draft
 *   POST   /api/safety/report/{id}/reply   — reporter adds public thread reply
 *   GET    /api/safety/publications        — published safety publications list
 *   GET    /api/safety/publication/{id}    — single publication
 */
class SafetyApiController {

    // ─── GET /api/safety/types ─────────────────────────────────────────────────

    /**
     * Returns array of report types available to the current user's role
     * filtered by the tenant's enabled types.
     *
     * Response: { types: [ { slug, name } ] }
     */
    public function types(): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        $settings     = SafetyReportModel::getSettings($tenantId);
        $enabledTypes = $settings['enabled_types'] ?? array_keys(SafetyReportModel::TYPES);

        $userRoles = UserModel::getRoles((int) $user['id']);
        $roleSlugs = array_column($userRoles, 'slug');

        $result = [];
        foreach ($enabledTypes as $slug) {
            if (!isset(SafetyReportModel::TYPES[$slug])) continue;
            $allowed = SafetyReportModel::TYPE_ROLES[$slug] ?? ['all'];
            if (in_array('all', $allowed, true) || array_intersect($allowed, $roleSlugs)) {
                $result[] = [
                    'slug' => $slug,
                    'name' => SafetyReportModel::TYPES[$slug],
                ];
            }
        }

        jsonResponse(['types' => $result]);
    }

    // ─── GET /api/safety/my-reports ────────────────────────────────────────────

    /**
     * Reporter's submitted and non-draft reports.
     *
     * Response: { reports: [ ReportItem ] }
     */
    public function myReports(): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        $rows = SafetyReportModel::forUser($tenantId, (int) $user['id']);
        jsonResponse(['reports' => array_map([self::class, 'formatReport'], $rows)]);
    }

    // ─── GET /api/safety/drafts ────────────────────────────────────────────────

    /**
     * Reporter's draft reports.
     *
     * Response: { drafts: [ ReportItem ] }
     */
    public function drafts(): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        $rows = SafetyReportModel::draftsForUser($tenantId, (int) $user['id']);
        jsonResponse(['drafts' => array_map([self::class, 'formatReport'], $rows)]);
    }

    // ─── GET /api/safety/report/{id} ──────────────────────────────────────────

    /**
     * Full report detail: report fields + reporter-visible thread + attachments.
     * Internal notes are NEVER returned to reporter.
     *
     * Response: { report: ReportDetail }
     */
    public function report(int $id): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        $report = SafetyReportModel::find($tenantId, $id);

        if (!$report) {
            jsonResponse(['error' => 'Report not found'], 404);
            return;
        }

        // Reporter can only see their own report; safety team can see all
        if ((int) $report['reporter_id'] !== (int) $user['id']) {
            jsonResponse(['error' => 'Access denied'], 403);
            return;
        }

        $threads     = SafetyReportModel::getThreads($id, false); // public only
        $attachments = SafetyReportModel::getAttachments($id);

        $payload = self::formatReport($report);
        $payload['thread']      = array_map([self::class, 'formatThread'], $threads);
        $payload['attachments'] = array_map([self::class, 'formatAttachment'], $attachments);

        jsonResponse(['report' => $payload]);
    }

    // ─── POST /api/safety/report ───────────────────────────────────────────────

    /**
     * Create a new report — either as draft or fully submitted.
     *
     * Request body (JSON or form-encoded):
     *   { report_type, title, description, is_draft, ... }
     *
     * Response: { success: true, id: int, reference_no: string, is_draft: bool }
     */
    public function createReport(): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        $body = $this->parseBody();
        $type = trim($body['report_type'] ?? 'general_hazard');

        if (!$this->userCanUseType($type, $tenantId, (int) $user['id'])) {
            jsonResponse(['error' => 'You do not have access to that report type'], 403);
            return;
        }

        $data            = $this->collectData($body, $user);
        $isDraft         = !empty($body['is_draft']);

        if ($isDraft) {
            $id = SafetyReportModel::saveDraft($tenantId, $data);
            AuditService::logApi('safety.draft_saved', 'safety_reports', $id);
        } else {
            $title       = trim($body['title']       ?? '');
            $description = trim($body['description'] ?? '');
            if (!$title || !$description) {
                jsonResponse(['error' => 'title and description are required'], 422);
                return;
            }
            $id = SafetyReportModel::submit($tenantId, $data);
            AuditService::logApi('safety.report_submitted', 'safety_reports', $id);

            // Notify safety managers
            $report = SafetyReportModel::find($tenantId, $id);
            NotificationService::notifyTenant(
                $tenantId,
                'safety_manager',
                'New Safety Report: ' . ($report['reference_no'] ?? ''),
                ($report['reference_no'] ?? '') . ' — ' . $title,
                '/safety/team/report/' . $id
            );
        }

        $report = SafetyReportModel::find($tenantId, $id);
        jsonResponse([
            'success'      => true,
            'id'           => $id,
            'reference_no' => $report['reference_no'] ?? '',
            'is_draft'     => $isDraft,
        ]);
    }

    // ─── PUT /api/safety/report/{id} ──────────────────────────────────────────

    /**
     * Update an existing draft.
     * Returns 403 if not owned, 422 if report is no longer a draft.
     *
     * Response: { success: true }
     */
    public function updateReport(int $id): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        $body = $this->parseBody();
        $data = $this->collectData($body, $user);

        $ok = SafetyReportModel::updateDraft($tenantId, $id, (int) $user['id'], $data);

        if (!$ok) {
            jsonResponse(['error' => 'Draft not found, already submitted, or not owned by you'], 422);
            return;
        }

        AuditService::logApi('safety.draft_updated', 'safety_reports', $id);
        jsonResponse(['success' => true]);
    }

    // ─── DELETE /api/safety/report/{id}/draft ─────────────────────────────────

    /**
     * Delete a draft. Only the owner may delete their draft.
     *
     * Response: { success: true }
     */
    public function deleteDraft(int $id): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        $report = SafetyReportModel::find($tenantId, $id);

        if (!$report) {
            jsonResponse(['error' => 'Draft not found'], 404);
            return;
        }

        if (!$report['is_draft']) {
            jsonResponse(['error' => 'Only draft reports may be deleted'], 422);
            return;
        }

        if ((int) $report['reporter_id'] !== (int) $user['id']) {
            jsonResponse(['error' => 'Access denied'], 403);
            return;
        }

        Database::execute(
            "DELETE FROM safety_reports WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        AuditService::logApi('safety.draft_deleted', 'safety_reports', $id);
        jsonResponse(['success' => true]);
    }

    // ─── POST /api/safety/report/{id}/reply ───────────────────────────────────

    /**
     * Reporter adds a public thread reply.
     *
     * Request body: { body: string }
     * Response: { success: true, thread_id: int }
     */
    public function addReply(int $id): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        $report = SafetyReportModel::find($tenantId, $id);

        if (!$report) {
            jsonResponse(['error' => 'Report not found'], 404);
            return;
        }

        if ((int) $report['reporter_id'] !== (int) $user['id']) {
            jsonResponse(['error' => 'Access denied'], 403);
            return;
        }

        $body = $this->parseBody();
        $text = trim($body['body'] ?? '');

        if (!$text) {
            jsonResponse(['error' => 'body is required'], 422);
            return;
        }

        $threadId = SafetyReportModel::addThread($id, (int) $user['id'], $text, false);
        AuditService::logApi('safety.reply_added', 'safety_reports', $id);

        // Notify assigned user or safety managers
        if ($report['assigned_to']) {
            NotificationService::notifyUser(
                (int) $report['assigned_to'],
                'New Reply: ' . $report['reference_no'],
                'The reporter has added a reply to ' . $report['reference_no'],
                '/safety/team/report/' . $id
            );
        } else {
            NotificationService::notifyTenant(
                $tenantId,
                'safety_manager',
                'New Reply: ' . $report['reference_no'],
                'The reporter has added a reply to ' . $report['reference_no'],
                '/safety/team/report/' . $id
            );
        }

        jsonResponse(['success' => true, 'thread_id' => $threadId]);
    }

    // ─── GET /api/safety/publications ─────────────────────────────────────────

    /**
     * List published safety publications for the user's tenant.
     *
     * Response: { publications: [ PublicationItem ] }
     */
    public function publications(): void {
        $tenantId = apiTenantId();

        $pubs = SafetyReportModel::getPublications($tenantId, 'published');
        jsonResponse([
            'publications' => array_map([self::class, 'formatPublication'], $pubs),
        ]);
    }

    // ─── GET /api/safety/publication/{id} ─────────────────────────────────────

    /**
     * Single publication detail.
     *
     * Response: { publication: PublicationDetail }
     */
    public function publication(int $id): void {
        $tenantId = apiTenantId();

        $pub = SafetyReportModel::getPublication($tenantId, $id);

        if (!$pub || $pub['status'] !== 'published') {
            jsonResponse(['error' => 'Publication not found'], 404);
            return;
        }

        jsonResponse(['publication' => self::formatPublication($pub)]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Parse JSON or form body into an associative array.
     */
    private function parseBody(): array {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return $_POST;
    }

    /**
     * Collect and sanitise fields for SafetyReportModel write methods.
     */
    private function collectData(array $body, array $user): array {
        return [
            'report_type'           => trim($body['report_type']           ?? 'general_hazard'),
            'reporter_id'           => (int) $user['id'],
            'is_anonymous'          => !empty($body['is_anonymous']),
            'event_date'            => trim($body['event_date']            ?? '') ?: null,
            'event_utc_time'        => trim($body['event_utc_time']        ?? '') ?: null,
            'event_local_time'      => trim($body['event_local_time']      ?? '') ?: null,
            'location_name'         => trim($body['location_name']         ?? '') ?: null,
            'icao_code'             => strtoupper(trim($body['icao_code']  ?? '')) ?: null,
            'occurrence_type'       => in_array(($body['occurrence_type'] ?? ''), ['occurrence','hazard'])
                                        ? $body['occurrence_type'] : 'occurrence',
            'event_type'            => trim($body['event_type']            ?? '') ?: null,
            'initial_risk_score'    => isset($body['initial_risk_score']) && $body['initial_risk_score'] !== ''
                                        ? max(1, min(5, (int) $body['initial_risk_score'])) : null,
            'aircraft_registration' => strtoupper(trim($body['aircraft_registration'] ?? '')) ?: null,
            'call_sign'             => strtoupper(trim($body['call_sign']  ?? '')) ?: null,
            'title'                 => trim($body['title']                 ?? ''),
            'description'           => trim($body['description']           ?? ''),
            'severity'              => trim($body['severity']              ?? 'unassigned'),
            'extra_fields'          => $body['extra_fields']               ?? null,
            'reporter_position'     => trim($body['reporter_position']     ?? '') ?: null,
            'template_version'      => max(1, (int) ($body['template_version'] ?? 1)),
        ];
    }

    /**
     * Return true if the user's roles allow using the given type and
     * the type is enabled for the tenant.
     */
    private function userCanUseType(string $type, int $tenantId, int $userId): bool {
        if (!isset(SafetyReportModel::TYPES[$type])) return false;

        $settings     = SafetyReportModel::getSettings($tenantId);
        $enabledTypes = $settings['enabled_types'] ?? array_keys(SafetyReportModel::TYPES);
        if (!in_array($type, $enabledTypes, true)) return false;

        $allowed   = SafetyReportModel::TYPE_ROLES[$type] ?? ['all'];
        if (in_array('all', $allowed, true)) return true;

        $userRoles = UserModel::getRoles($userId);
        $slugs     = array_column($userRoles, 'slug');
        return (bool) array_intersect($allowed, $slugs);
    }

    // ─── Formatters ───────────────────────────────────────────────────────────

    private static function formatReport(array $r): array {
        return [
            'id'                    => (int) $r['id'],
            'reference_no'          => $r['reference_no'],
            'report_type'           => $r['report_type'],
            'status'                => $r['status'],
            'is_draft'              => (bool) ($r['is_draft'] ?? false),
            'is_anonymous'          => (bool) $r['is_anonymous'],
            'title'                 => $r['title'],
            'description'           => $r['description'],
            'event_date'            => $r['event_date'] ?? null,
            'event_utc_time'        => $r['event_utc_time'] ?? null,
            'event_local_time'      => $r['event_local_time'] ?? null,
            'location_name'         => $r['location_name'] ?? null,
            'icao_code'             => $r['icao_code'] ?? null,
            'occurrence_type'       => $r['occurrence_type'] ?? 'occurrence',
            'event_type'            => $r['event_type'] ?? null,
            'initial_risk_score'    => isset($r['initial_risk_score']) ? (int) $r['initial_risk_score'] : null,
            'aircraft_registration' => $r['aircraft_registration'] ?? null,
            'call_sign'             => $r['call_sign'] ?? null,
            'severity'              => $r['severity'],
            'reporter_position'     => $r['reporter_position'] ?? null,
            'submitted_at'          => $r['submitted_at'] ?? null,
            'closed_at'             => $r['closed_at'] ?? null,
            'created_at'            => $r['created_at'],
            'updated_at'            => $r['updated_at'],
        ];
    }

    private static function formatThread(array $t): array {
        return [
            'id'          => (int) $t['id'],
            'author_name' => $t['author_name'] ?? null,
            'body'        => $t['body'],
            'is_internal' => (bool) ($t['is_internal'] ?? false),
            'parent_id'   => isset($t['parent_id']) ? (int) $t['parent_id'] : null,
            'created_at'  => $t['created_at'],
        ];
    }

    private static function formatAttachment(array $a): array {
        return [
            'id'           => (int) $a['id'],
            'file_name'    => $a['file_name'],
            'file_type'    => $a['file_type'],
            'file_size'    => (int) $a['file_size'],
            'uploader_name'=> $a['uploader_name'] ?? null,
            'created_at'   => $a['created_at'],
        ];
    }

    private static function formatPublication(array $p): array {
        return [
            'id'          => (int) $p['id'],
            'title'       => $p['title'],
            'summary'     => $p['summary'] ?? null,
            'content'     => $p['content'],
            'author_name' => $p['author_name'] ?? null,
            'published_at'=> $p['published_at'] ?? null,
            'created_at'  => $p['created_at'],
        ];
    }

    /**
     * GET /api/safety/airstrip-feed?base=ICAO
     *
     * Returns approved airstrip / runway-condition reports flagged
     * `visible_to_base = 1` for the supplied base ICAO (or the caller's
     * assigned base when no `base` query is given).  Used by the iPad
     * Airstrip Reports screen to surface what other crew have reported at
     * the next base before flying in.
     */
    public function airstripFeed(): void {
        $tenantId = apiTenantId();
        $base     = trim((string)($_GET['base'] ?? ''));
        if ($base === '') {
            $userId = (int) apiUser()['user_id'];
            $u = Database::fetch(
                "SELECT b.code FROM users u
                   LEFT JOIN bases b ON b.id = u.base_id
                  WHERE u.id = ? LIMIT 1",
                [$userId]
            );
            $base = (string)($u['code'] ?? '');
        }
        if ($base === '') {
            jsonResponse(['reports' => [], 'note' => 'No base specified or assigned to caller']);
        }

        $rows = Database::fetchAll(
            "SELECT r.id, r.title, r.description, r.severity, r.icao_code,
                    r.event_date, r.aircraft_registration, r.submitted_at,
                    u.name AS reporter_name
               FROM safety_reports r
               LEFT JOIN users u ON u.id = r.reporter_id
              WHERE r.tenant_id = ?
                AND r.visible_to_base = 1
                AND r.report_type IN ('airstrip','operational')
                AND r.icao_code = ?
                AND r.is_draft = 0
                AND r.is_anonymous = 0
              ORDER BY COALESCE(r.event_date, r.submitted_at) DESC, r.id DESC
              LIMIT 50",
            [$tenantId, strtoupper($base)]
        );

        $out = array_map(fn($r) => [
            'id'                    => (int)$r['id'],
            'title'                 => (string)$r['title'],
            'description'           => (string)($r['description'] ?? ''),
            'severity'              => (string)($r['severity'] ?? 'unassigned'),
            'icao_code'             => (string)($r['icao_code'] ?? ''),
            'event_date'            => $r['event_date'] ?? null,
            'aircraft_registration' => $r['aircraft_registration'] ?? null,
            'submitted_at'          => $r['submitted_at'] ?? null,
            'reporter_name'         => $r['reporter_name'] ?? null,
        ], $rows);

        jsonResponse(['reports' => $out, 'base' => $base]);
    }
}
