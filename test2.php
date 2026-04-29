<?php
require 'config/db.php';
$db = Database::getInstance();
$sql = "SELECT u.id, u.full_name, u.email, u.phone, u.country, u.user_type, u.is_active,
        IFNULL(ep.total_sessions, 0) AS total_sessions,
        IFNULL(ep.average_rating, 0) AS average_rating,
        IFNULL(ep.total_reviews, 0) AS total_reviews,
        IFNULL(ep.is_verified, 0) AS is_verified,
        IFNULL(ep.is_available, 0) AS is_available,
        ep.headline, ep.expertise_areas, ep.experience_years, ep.rate_per_session, ep.currency,
        (SELECT COUNT(*) FROM thinking_requests tr WHERE tr.expert_id = u.id AND tr.status IN ('responded','completed')) AS solved_count
 FROM users u
 LEFT JOIN expert_profiles ep ON ep.user_id = u.id
 WHERE u.user_type IN ('expert','both')
 ORDER BY total_sessions DESC, u.created_at DESC
 LIMIT 10";
print_r($db->fetchAll($sql));
