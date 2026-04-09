<?php
/**
 * RosterApiController — iPad roster endpoint
 *
 * GET /api/roster?year=YYYY&month=M
 *   Returns the authenticated user's roster entries for the requested month.
 *   Defaults to current month if params omitted.
 */
class RosterApiController {

    public function index(): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        $year  = (int) ($_GET['year']  ?? date('Y'));
        $month = (int) ($_GET['month'] ?? date('n'));

        if ($month < 1 || $month > 12) {
            jsonResponse(['error' => 'Invalid month'], 400);
        }

        $entries = \RosterModel::getUserMonth($user['user_id'], $year, $month);

        $dutyTypes = \RosterModel::dutyTypes();

        jsonResponse([
            'success' => true,
            'year'    => $year,
            'month'   => $month,
            'days_in_month' => cal_days_in_month(CAL_GREGORIAN, $month, $year),
            'roster'  => array_map(function ($entry) use ($dutyTypes) {
                $dt = $dutyTypes[$entry['duty_type']] ?? ['label' => ucfirst($entry['duty_type']), 'color' => '#6b7280', 'code' => '?'];
                return [
                    'id'         => (int) $entry['id'],
                    'date'       => $entry['roster_date'],
                    'duty_type'  => $entry['duty_type'],
                    'duty_label' => $dt['label'],
                    'duty_color' => $dt['color'],
                    'duty_code'  => $entry['duty_code'] ?? $dt['code'],
                    'notes'      => $entry['notes'] ?? null,
                ];
            }, $entries),
        ]);
    }
}
