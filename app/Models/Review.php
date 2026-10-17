<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Review extends Model
{
    protected static string $table = 'reviews';

    public static function findByOrderId(int $orderId): ?array
    {
        return self::first('order_id = ?', [$orderId]);
    }

    public static function findByUserAndOrder(int $userId, int $orderId): ?array
    {
        return self::first('user_id = ? AND order_id = ?', [$userId, $orderId]);
    }

    public static function getApproved(int $limit = 10): array
    {
        return self::where('status = ?', ['approved'], 'id DESC', $limit);
    }

    public static function getPending(): array
    {
        return self::where('status = ?', ['pending'], 'id ASC');
    }

    public static function countPending(): int
    {
        $db = db();
        $row = $db->fetch("SELECT COUNT(*) AS total FROM reviews WHERE status = 'pending'");
        return (int)($row['total'] ?? 0);
    }

    /**
     * Get approved reviews joined with user info and primary ordered dish for public showcase
     */
    public static function getApprovedWithDetails(int $limit = 10): array
    {
        $db = db();
        $sql = "SELECT r.*, 
                       u.name AS user_name, 
                       u.phone AS user_phone,
                       o.order_number,
                       o.created_at AS order_date,
                       (SELECT oi.item_name FROM order_items oi WHERE oi.order_id = r.order_id ORDER BY oi.id ASC LIMIT 1) AS primary_dish
                FROM reviews r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN orders o ON r.order_id = o.id
                WHERE r.status = 'approved'
                ORDER BY r.rating DESC, r.id DESC
                LIMIT ?";
        return $db->fetchAll($sql, [$limit]);
    }

    /**
     * Get reviews with user and order details for admin moderation queue
     */
    public static function getAllWithDetails(int $limit = 100, ?string $status = null): array
    {
        $db = db();
        $params = [];
        $where = "1=1";
        if ($status !== null && $status !== '') {
            $where .= " AND r.status = ?";
            $params[] = $status;
        }
        $params[] = $limit;

        $sql = "SELECT r.*, 
                       u.name AS user_name, 
                       u.phone AS user_phone,
                       u.email AS user_email,
                       o.order_number,
                       o.total_paise,
                       o.created_at AS order_date,
                       (SELECT GROUP_CONCAT(oi.item_name, ', ') FROM order_items oi WHERE oi.order_id = r.order_id) AS dishes_ordered
                FROM reviews r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN orders o ON r.order_id = o.id
                WHERE {$where}
                ORDER BY CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END, r.id DESC
                LIMIT ?";
        return $db->fetchAll($sql, $params);
    }

    /**
     * Get high-level review statistics (average rating, count, distribution)
     */
    public static function getSummaryStats(): array
    {
        $db = db();
        $row = $db->fetch("SELECT COUNT(*) AS total, AVG(rating) AS avg_rating FROM reviews WHERE status = 'approved'");
        $total = (int)($row['total'] ?? 0);
        $avg = round((float)($row['avg_rating'] ?? 5.0), 1);

        $distRows = $db->fetchAll("SELECT rating, COUNT(*) AS count FROM reviews WHERE status = 'approved' GROUP BY rating");
        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($distRows as $dr) {
            $r = (int)$dr['rating'];
            if (isset($distribution[$r])) {
                $distribution[$r] = (int)$dr['count'];
            }
        }

        return [
            'total_approved' => $total,
            'average_rating' => $total > 0 ? $avg : 5.0,
            'distribution' => $distribution,
        ];
    }
}
