<?php
// =============================================
// api/search_experts.php
// GET /api/search_experts.php
// Returns paginated, filtered expert list
// =============================================

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

class ExpertSearch
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Search experts with filters + pagination
     * Params (GET):
     *   keyword, category_id, min_price, max_price,
     *   min_rating, verified_only, available_only,
     *   sort (rating|price_low|price_high|sessions|newest),
     *   page (default 1)
     */
    public function search(array $f = []): array
    {
        $sql = "SELECT
                    u.id        AS user_id,
                    u.full_name,
                    u.profile_photo,
                    ep.id       AS profile_id,
                    ep.headline,
                    ep.expertise_areas,
                    ep.experience_years,
                    ep.current_role,
                    ep.company,
                    ep.rate_per_session,
                    ep.currency,
                    ep.session_duration_minutes,
                    ep.max_response_hours,
                    ep.average_rating,
                    ep.total_reviews,
                    ep.total_sessions,
                    ep.is_verified,
                    ep.is_available,
                    GROUP_CONCAT(ec.name) AS category_names
                FROM expert_profiles ep
                INNER JOIN users u              ON ep.user_id = u.id
                LEFT  JOIN expert_categories xc ON ep.id = xc.expert_profile_id
                LEFT  JOIN expertise_categories ec ON xc.category_id = ec.id
                WHERE ep.is_available = 1
                  AND u.is_active     = 1";

        $params = [];

        if (!empty($f['keyword'])) {
            $kw = '%' . $f['keyword'] . '%';
            $sql .= " AND (ep.headline LIKE ? OR u.full_name LIKE ? OR ep.expertise_areas LIKE ?)";
            $params = array_merge($params, [$kw, $kw, $kw]);
        }

        if (!empty($f['category_id'])) {
            $sql .= " AND xc.category_id = ?";
            $params[] = (int) $f['category_id'];
        }

        if (isset($f['min_price']) && $f['min_price'] !== '') {
            $sql .= " AND ep.rate_per_session >= ?";
            $params[] = (float) $f['min_price'];
        }

        if (isset($f['max_price']) && $f['max_price'] !== '') {
            $sql .= " AND ep.rate_per_session <= ?";
            $params[] = (float) $f['max_price'];
        }

        if (!empty($f['min_rating'])) {
            $sql .= " AND ep.average_rating >= ?";
            $params[] = (float) $f['min_rating'];
        }

        if (!empty($f['verified_only'])) {
            $sql .= " AND ep.is_verified = 1";
        }

        $sql .= " GROUP BY u.id, u.full_name, u.profile_photo,
                            ep.id, ep.headline, ep.expertise_areas,
                            ep.experience_years, ep.current_role, ep.company,
                            ep.rate_per_session, ep.currency,
                            ep.session_duration_minutes, ep.max_response_hours,
                            ep.average_rating, ep.total_reviews, ep.total_sessions,
                            ep.is_verified, ep.is_available";

        $sortMap = [
            'rating'     => 'ep.average_rating DESC',
            'price_low'  => 'ep.rate_per_session ASC',
            'price_high' => 'ep.rate_per_session DESC',
            'sessions'   => 'ep.total_sessions DESC',
            'newest'     => 'ep.id DESC',
        ];
        $sort = $sortMap[$f['sort'] ?? 'rating'] ?? $sortMap['rating'];
        $sql .= " ORDER BY ep.is_verified DESC, {$sort}";

        // Pagination
        $page    = max(1, (int) ($f['page'] ?? 1));
        $perPage = 12;
        $offset  = ($page - 1) * $perPage;
        $sql    .= " LIMIT {$perPage} OFFSET {$offset}";

        $experts = $this->db->fetchAll($sql, $params);

        // Count total (without pagination)
        $countSql = "SELECT COUNT(DISTINCT ep.id) AS total
                     FROM expert_profiles ep
                     INNER JOIN users u ON ep.user_id = u.id
                     LEFT  JOIN expert_categories xc ON ep.id = xc.expert_profile_id
                     WHERE ep.is_available = 1 AND u.is_active = 1";

        $total = (int) ($this->db->fetchOne($countSql, [])['total'] ?? 0);

        return [
            'experts'       => $experts,
            'total'         => $total,
            'page'          => $page,
            'per_page'      => $perPage,
            'total_pages'   => (int) ceil($total / $perPage),
        ];
    }
}

// Handle request
try {
    $search = new ExpertSearch();
    $result = $search->search($_GET);
    echo json_encode(['success' => true] + $result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
