<?php

require_once __DIR__ . '/GoogleAdsClient.php';

class GoogleAdsSync
{
    private mysqli $conn;
    private GoogleAdsClient $client;

    public function __construct(mysqli $conn, ?GoogleAdsClient $client = null)
    {
        $this->conn = $conn;
        $this->client = $client ?: new GoogleAdsClient();
    }

    public function ensureTable(): void
    {
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS google_ads_campaign_stats (
                id INT NOT NULL AUTO_INCREMENT,
                campaign_id BIGINT NOT NULL,
                campaign_name VARCHAR(255) NOT NULL,
                report_date DATE NOT NULL,
                impressions BIGINT NOT NULL DEFAULT 0,
                clicks BIGINT NOT NULL DEFAULT 0,
                ctr DECIMAL(12,6) NOT NULL DEFAULT 0,
                average_cpc DECIMAL(12,2) NOT NULL DEFAULT 0,
                cost DECIMAL(12,2) NOT NULL DEFAULT 0,
                conversions DECIMAL(12,2) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_campaign_date (campaign_id, report_date),
                KEY idx_campaign_name (campaign_name),
                KEY idx_report_date (report_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function syncTodayAndYesterday(): array
    {
        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Kolkata'));
        return $this->syncRange($today->modify('-1 day')->format('Y-m-d'), $today->format('Y-m-d'));
    }

    public function syncRange(string $fromDate, string $toDate): array
    {
        $this->ensureTable();
        $rows = $this->client->fetchDailyCampaignStats($fromDate, $toDate);
        $saved = 0;

        $stmt = $this->conn->prepare("
            INSERT INTO google_ads_campaign_stats
                (campaign_id, campaign_name, report_date, impressions, clicks, ctr, average_cpc, cost, conversions)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                campaign_name = VALUES(campaign_name),
                impressions = VALUES(impressions),
                clicks = VALUES(clicks),
                ctr = VALUES(ctr),
                average_cpc = VALUES(average_cpc),
                cost = VALUES(cost),
                conversions = VALUES(conversions),
                updated_at = NOW()
        ");

        if (!$stmt) {
            throw new RuntimeException('Could not prepare Google Ads stats upsert.');
        }

        foreach ($rows as $row) {
            $campaign = $row['campaign'] ?? [];
            $segments = $row['segments'] ?? [];
            $metrics = $row['metrics'] ?? [];

            $campaignId = (int)($campaign['id'] ?? 0);
            $campaignName = (string)($campaign['name'] ?? '');
            $reportDate = (string)($segments['date'] ?? '');
            if ($campaignId <= 0 || $campaignName === '' || $reportDate === '') {
                continue;
            }

            $impressions = (int)($metrics['impressions'] ?? 0);
            $clicks = (int)($metrics['clicks'] ?? 0);
            $ctr = (float)($metrics['ctr'] ?? 0);
            $avgCpc = ((float)($metrics['averageCpc'] ?? $metrics['average_cpc'] ?? 0)) / 1000000;
            $cost = ((float)($metrics['costMicros'] ?? $metrics['cost_micros'] ?? 0)) / 1000000;
            $conversions = (float)($metrics['conversions'] ?? 0);

            $stmt->bind_param(
                'issiidddd',
                $campaignId,
                $campaignName,
                $reportDate,
                $impressions,
                $clicks,
                $ctr,
                $avgCpc,
                $cost,
                $conversions
            );
            $stmt->execute();
            $saved++;
        }
        $stmt->close();

        return ['fetched' => count($rows), 'saved' => $saved, 'from' => $fromDate, 'to' => $toDate];
    }
}
