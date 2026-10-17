<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;

class ReportService
{
    /**
     * Get aggregate financial metrics and GST breakdown for a date range
     */
    public static function getFinancialSummary(string $startDate, string $endDate): array
    {
        $db = db();
        $sql = "SELECT 
                    COUNT(*) AS total_orders,
                    COALESCE(SUM(subtotal_paise), 0) AS gross_subtotal_paise,
                    COALESCE(SUM(discount_paise), 0) AS total_discount_paise,
                    COALESCE(SUM(tax_paise), 0) AS total_tax_paise,
                    COALESCE(SUM(total_paise), 0) AS grand_total_paise
                FROM orders 
                WHERE status = 'completed' 
                  AND payment_status = 'paid'
                  AND DATE(created_at) BETWEEN ? AND ?";

        $row = $db->fetch($sql, [$startDate, $endDate]) ?: [];

        $totalOrders = (int)($row['total_orders'] ?? 0);
        $grossSubtotal = (int)($row['gross_subtotal_paise'] ?? 0);
        $totalDiscount = (int)($row['total_discount_paise'] ?? 0);
        $totalTax = (int)($row['total_tax_paise'] ?? 0);
        $grandTotal = (int)($row['grand_total_paise'] ?? 0);

        // Taxable turnover = Gross subtotal minus discounts
        $taxableTurnover = max(0, $grossSubtotal - $totalDiscount);

        // 5% Restaurant GST Split (2.5% CGST + 2.5% SGST)
        $cgstPaise = (int)floor($totalTax / 2);
        $sgstPaise = $totalTax - $cgstPaise;

        // Average Order Value
        $aovPaise = $totalOrders > 0 ? (int)round($grandTotal / $totalOrders) : 0;

        // Payment tenders breakdown
        $paymentSql = "SELECT 
                           payment_method, 
                           COUNT(*) AS count, 
                           COALESCE(SUM(total_paise), 0) AS total_paise
                       FROM orders 
                       WHERE status = 'completed' 
                         AND payment_status = 'paid'
                         AND DATE(created_at) BETWEEN ? AND ?
                       GROUP BY payment_method";
        $paymentRows = $db->fetchAll($paymentSql, [$startDate, $endDate]);

        $payments = [
            'counter_cash' => ['name' => 'Cash at Counter', 'count' => 0, 'total_paise' => 0, 'formatted' => '₹0.00', 'percent' => 0],
            'counter_upi' => ['name' => 'Counter UPI Scan', 'count' => 0, 'total_paise' => 0, 'formatted' => '₹0.00', 'percent' => 0],
            'online_razorpay' => ['name' => 'Online Gateway', 'count' => 0, 'total_paise' => 0, 'formatted' => '₹0.00', 'percent' => 0],
        ];

        foreach ($paymentRows as $pr) {
            $m = (string)$pr['payment_method'];
            $amt = (int)$pr['total_paise'];
            $pct = $grandTotal > 0 ? round(($amt / $grandTotal) * 100, 1) : 0;

            $payments[$m] = [
                'name' => match($m) {
                    'counter_upi' => 'Counter UPI Scan',
                    'online_razorpay' => 'Online Gateway',
                    default => 'Cash at Counter',
                },
                'count' => (int)$pr['count'],
                'total_paise' => $amt,
                'formatted' => '₹' . number_format($amt / 100, 2),
                'percent' => $pct,
            ];
        }

        return [
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'orders_count' => $totalOrders,
            'gross_subtotal_paise' => $grossSubtotal,
            'gross_subtotal_formatted' => '₹' . number_format($grossSubtotal / 100, 2),
            'total_discount_paise' => $totalDiscount,
            'total_discount_formatted' => '₹' . number_format($totalDiscount / 100, 2),
            'taxable_turnover_paise' => $taxableTurnover,
            'taxable_turnover_formatted' => '₹' . number_format($taxableTurnover / 100, 2),
            'total_tax_paise' => $totalTax,
            'total_tax_formatted' => '₹' . number_format($totalTax / 100, 2),
            'cgst_paise' => $cgstPaise,
            'cgst_formatted' => '₹' . number_format($cgstPaise / 100, 2),
            'sgst_paise' => $sgstPaise,
            'sgst_formatted' => '₹' . number_format($sgstPaise / 100, 2),
            'grand_total_paise' => $grandTotal,
            'grand_total_formatted' => '₹' . number_format($grandTotal / 100, 2),
            'aov_paise' => $aovPaise,
            'aov_formatted' => '₹' . number_format($aovPaise / 100, 2),
            'payment_tenders' => $payments,
        ];
    }

    /**
     * Get top performing menu items by quantity and revenue
     */
    public static function getTopSellingItems(string $startDate, string $endDate, int $limit = 10): array
    {
        $db = db();
        $sql = "SELECT 
                    oi.item_name,
                    COALESCE(mc.name, 'Artisanal Collection') AS category_name,
                    SUM(oi.quantity) AS total_qty,
                    SUM(oi.total_price_paise) AS total_revenue_paise
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                LEFT JOIN menu_items mi ON oi.item_id = mi.id
                LEFT JOIN menu_categories mc ON mi.category_id = mc.id
                WHERE o.status = 'completed'
                  AND o.payment_status = 'paid'
                  AND DATE(o.created_at) BETWEEN ? AND ?
                GROUP BY oi.item_name, mc.name
                ORDER BY total_qty DESC, total_revenue_paise DESC
                LIMIT ?";

        $rows = $db->fetchAll($sql, [$startDate, $endDate, $limit]);

        return array_map(function ($r) {
            $rev = (int)$r['total_revenue_paise'];
            return [
                'item_name' => $r['item_name'],
                'category_name' => $r['category_name'],
                'total_qty' => (int)$r['total_qty'],
                'total_revenue_paise' => $rev,
                'total_revenue_formatted' => '₹' . number_format($rev / 100, 2),
            ];
        }, $rows);
    }

    /**
     * Get day-by-day sales trend for the date range
     */
    public static function getDailyTrend(string $startDate, string $endDate): array
    {
        $db = db();
        $sql = "SELECT 
                    DATE(created_at) AS sale_date,
                    COUNT(*) AS orders_count,
                    COALESCE(SUM(subtotal_paise), 0) AS subtotal_paise,
                    COALESCE(SUM(discount_paise), 0) AS discount_paise,
                    COALESCE(SUM(tax_paise), 0) AS tax_paise,
                    COALESCE(SUM(total_paise), 0) AS total_paise
                FROM orders 
                WHERE status = 'completed' 
                  AND payment_status = 'paid'
                  AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY sale_date ASC";

        $rows = $db->fetchAll($sql, [$startDate, $endDate]);

        return array_map(function ($r) {
            $tot = (int)$r['total_paise'];
            return [
                'date' => $r['sale_date'],
                'formatted_date' => date('d M Y', strtotime($r['sale_date'])),
                'orders_count' => (int)$r['orders_count'],
                'subtotal_paise' => (int)$r['subtotal_paise'],
                'discount_paise' => (int)$r['discount_paise'],
                'tax_paise' => (int)$r['tax_paise'],
                'total_paise' => $tot,
                'total_formatted' => '₹' . number_format($tot / 100, 2),
            ];
        }, $rows);
    }

    /**
     * Generate Accountant-Ready CSV Export of all orders in date range
     */
    public static function generateCsv(string $startDate, string $endDate): string
    {
        $db = db();
        $sql = "SELECT 
                    o.id,
                    o.order_number,
                    o.created_at,
                    COALESCE(t.table_number, 'Counter') AS table_number,
                    COALESCE(u.phone, 'Guest') AS patron_phone,
                    COALESCE(u.name, 'Patron') AS patron_name,
                    o.subtotal_paise,
                    o.discount_paise,
                    o.tax_paise,
                    o.total_paise,
                    o.payment_method,
                    o.payment_status
                FROM orders o
                LEFT JOIN tables t ON o.table_id = t.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.status = 'completed'
                  AND o.payment_status = 'paid'
                  AND DATE(o.created_at) BETWEEN ? AND ?
                ORDER BY o.id ASC";

        $orders = $db->fetchAll($sql, [$startDate, $endDate]);

        $fh = fopen('php://temp', 'r+');

        // CSV Header
        fputcsv($fh, [
            'Order Number',
            'Date & Time',
            'Table',
            'Customer Name',
            'Customer Phone',
            'Gross Subtotal (₹)',
            'Discount (₹)',
            'Taxable Turnover (₹)',
            'CGST 2.5% (₹)',
            'SGST 2.5% (₹)',
            'Total 5% GST (₹)',
            'Grand Total (₹)',
            'Payment Mode',
            'Payment Status'
        ], ',', '"', "\\");

        foreach ($orders as $ord) {
            $gross = (int)$ord['subtotal_paise'];
            $disc = (int)$ord['discount_paise'];
            $taxable = max(0, $gross - $disc);
            $tax = (int)$ord['tax_paise'];
            $cgst = (int)floor($tax / 2);
            $sgst = $tax - $cgst;
            $grand = (int)$ord['total_paise'];

            fputcsv($fh, [
                $ord['order_number'],
                $ord['created_at'],
                $ord['table_number'],
                $ord['patron_name'],
                $ord['patron_phone'],
                number_format($gross / 100, 2, '.', ''),
                number_format($disc / 100, 2, '.', ''),
                number_format($taxable / 100, 2, '.', ''),
                number_format($cgst / 100, 2, '.', ''),
                number_format($sgst / 100, 2, '.', ''),
                number_format($tax / 100, 2, '.', ''),
                number_format($grand / 100, 2, '.', ''),
                ucfirst(str_replace('_', ' ', (string)$ord['payment_method'])),
                ucfirst((string)$ord['payment_status']),
            ], ',', '"', "\\");
        }

        rewind($fh);
        $csvContent = stream_get_contents($fh);
        fclose($fh);

        return (string)$csvContent;
    }
}
