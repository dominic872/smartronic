<?php

require_once __DIR__ . '/GoogleAdsEnv.php';

class GoogleAdsClient
{
    private GoogleAdsEnv $env;
    private string $apiVersion;

    public function __construct(?GoogleAdsEnv $env = null)
    {
        $this->env = $env ?: new GoogleAdsEnv();
        $this->apiVersion = $this->env->get('GOOGLE_ADS_API_VERSION', 'v22') ?: 'v22';
    }

    public function env(): GoogleAdsEnv
    {
        return $this->env;
    }

    public function getCustomerId(): string
    {
        return preg_replace('/\D+/', '', $this->env->get('GOOGLE_ADS_CUSTOMER_ID'));
    }

    public function getLoginCustomerId(): string
    {
        return preg_replace('/\D+/', '', $this->env->get('GOOGLE_ADS_LOGIN_CUSTOMER_ID'));
    }

    public function getOAuthRedirectUri(): string
    {
        $configured = trim($this->env->get('GOOGLE_ADS_REDIRECT_URI'));
        if ($configured !== '') {
            return $configured;
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'smartronic.online';
        if (stripos($host, 'smartronic.online') !== false) {
            return 'https://smartronic.online/admin_v2/smart/google_ads_connect.php';
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $host . '/admin_v2/smart/google_ads_connect.php';
    }

    public function buildAuthUrl(string $state): string
    {
        $params = [
            'client_id' => $this->env->get('GOOGLE_ADS_CLIENT_ID'),
            'redirect_uri' => $this->getOAuthRedirectUri(),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/adwords',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public function exchangeCodeForRefreshToken(string $code): array
    {
        $payload = [
            'client_id' => $this->env->get('GOOGLE_ADS_CLIENT_ID'),
            'client_secret' => $this->env->get('GOOGLE_ADS_CLIENT_SECRET'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getOAuthRedirectUri(),
        ];

        try {
            $result = $this->curlJson('https://oauth2.googleapis.com/token', $payload, []);
        } catch (Throwable $e) {
            throw new RuntimeException('OAuth code exchange failed: ' . $e->getMessage());
        }
        if (!empty($result['refresh_token'])) {
            $this->env->set('GOOGLE_ADS_REFRESH_TOKEN', (string)$result['refresh_token']);
        }
        return $result;
    }

    public function getAccessToken(): string
    {
        $missing = $this->env->required([
            'GOOGLE_ADS_CLIENT_ID',
            'GOOGLE_ADS_CLIENT_SECRET',
            'GOOGLE_ADS_REFRESH_TOKEN',
        ]);
        if ($missing) {
            throw new RuntimeException('Missing Google Ads OAuth config: ' . implode(', ', $missing));
        }

        $payload = [
            'client_id' => $this->env->get('GOOGLE_ADS_CLIENT_ID'),
            'client_secret' => $this->env->get('GOOGLE_ADS_CLIENT_SECRET'),
            'refresh_token' => $this->env->get('GOOGLE_ADS_REFRESH_TOKEN'),
            'grant_type' => 'refresh_token',
        ];
        try {
            $result = $this->curlJson('https://oauth2.googleapis.com/token', $payload, []);
        } catch (Throwable $e) {
            throw new RuntimeException('Saved refresh token failed: ' . $e->getMessage());
        }
        if (empty($result['access_token'])) {
            throw new RuntimeException('Google OAuth did not return an access token.');
        }
        return (string)$result['access_token'];
    }

    public function searchStream(string $query): array
    {
        $missing = $this->env->required([
            'GOOGLE_ADS_DEVELOPER_TOKEN',
            'GOOGLE_ADS_CUSTOMER_ID',
        ]);
        if ($missing) {
            throw new RuntimeException('Missing Google Ads API config: ' . implode(', ', $missing));
        }

        $customerId = $this->getCustomerId();
        $url = "https://googleads.googleapis.com/{$this->apiVersion}/customers/{$customerId}/googleAds:searchStream";
        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
            'developer-token: ' . $this->env->get('GOOGLE_ADS_DEVELOPER_TOKEN'),
            'Content-Type: application/json',
        ];
        $loginCustomerId = $this->getLoginCustomerId();
        if ($loginCustomerId !== '') {
            $headers[] = 'login-customer-id: ' . $loginCustomerId;
        }
        $response = $this->curlJson($url, ['query' => $query], $headers, true);
        $rows = [];
        foreach ($response as $chunk) {
            if (!empty($chunk['results']) && is_array($chunk['results'])) {
                foreach ($chunk['results'] as $result) {
                    $rows[] = $result;
                }
            }
        }
        return $rows;
    }

    public function listCampaigns(): array
    {
        return $this->searchStream(
            "SELECT campaign.id, campaign.name, campaign.status
             FROM campaign
             WHERE campaign.status != 'REMOVED'
             ORDER BY campaign.name"
        );
    }

    public function listAccessibleCustomers(): array
    {
        $missing = $this->env->required([
            'GOOGLE_ADS_DEVELOPER_TOKEN',
            'GOOGLE_ADS_REFRESH_TOKEN',
        ]);
        if ($missing) {
            throw new RuntimeException('Missing Google Ads config: ' . implode(', ', $missing));
        }

        $url = "https://googleads.googleapis.com/{$this->apiVersion}/customers:listAccessibleCustomers";
        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
            'developer-token: ' . $this->env->get('GOOGLE_ADS_DEVELOPER_TOKEN'),
            'Content-Type: application/json',
        ];
        return $this->curlJson($url, [], $headers);
    }

    public function fetchDailyCampaignStats(string $fromDate, string $toDate): array
    {
        $query = "
            SELECT
              campaign.id,
              campaign.name,
              segments.date,
              metrics.impressions,
              metrics.clicks,
              metrics.ctr,
              metrics.average_cpc,
              metrics.cost_micros,
              metrics.conversions
            FROM campaign
            WHERE segments.date BETWEEN '{$fromDate}' AND '{$toDate}'
              AND campaign.status != 'REMOVED'
            ORDER BY segments.date DESC, campaign.name
        ";
        return $this->searchStream($query);
    }

    private function curlJson(string $url, array $payload, array $headers = [], bool $expectArray = false): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers ?: ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
        ]);

        if (!$headers) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false || $errno) {
            throw new RuntimeException('Google API cURL error: ' . $error);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Google API returned invalid JSON. HTTP ' . $status);
        }

        if ($status >= 400 || isset($decoded['error'])) {
            if (isset($decoded['error']) && is_array($decoded['error'])) {
                $message = $decoded['error']['message'] ?? ('Google API HTTP ' . $status);
                if (!empty($decoded['error']['status'])) {
                    $message .= ' [' . $decoded['error']['status'] . ']';
                }
                if (!empty($decoded['error']['details']) && is_array($decoded['error']['details'])) {
                    $parts = [];
                    foreach ($decoded['error']['details'] as $detail) {
                        if (!empty($detail['errors']) && is_array($detail['errors'])) {
                            foreach ($detail['errors'] as $apiError) {
                                $code = '';
                                if (!empty($apiError['errorCode']) && is_array($apiError['errorCode'])) {
                                    $code = implode('.', array_filter(array_map('strval', $apiError['errorCode'])));
                                }
                                $msg = (string)($apiError['message'] ?? '');
                                $parts[] = trim($code . ($code && $msg ? ': ' : '') . $msg);
                            }
                        }
                    }
                    $parts = array_values(array_filter($parts));
                    if ($parts) {
                        $message .= ' - ' . implode(' | ', $parts);
                    }
                }
            } else {
                $message = (string)($decoded['error'] ?? ('Google API HTTP ' . $status));
            }
            if (!empty($decoded['error_description'])) {
                $message .= ': ' . $decoded['error_description'];
            }
            if (!empty($decoded['error_uri'])) {
                $message .= ' (' . $decoded['error_uri'] . ')';
            }
            if ($message === 'Google API HTTP ' . $status || trim($message) === '') {
                $message = 'Google API HTTP ' . $status . ': ' . $this->summarizeErrorBody($decoded, $body);
            }
            throw new RuntimeException($message);
        }

        if ($expectArray && $this->isList($decoded)) {
            return $decoded;
        }
        return $decoded;
    }

    private function isList(array $value): bool
    {
        $i = 0;
        foreach ($value as $key => $_) {
            if ($key !== $i++) return false;
        }
        return true;
    }

    private function summarizeErrorBody(array $decoded, string $body): string
    {
        $parts = [];
        $walker = function ($value, string $path = '') use (&$walker, &$parts): void {
            if (count($parts) >= 8) return;
            if (is_array($value)) {
                foreach ($value as $key => $child) {
                    $walker($child, $path === '' ? (string)$key : $path . '.' . $key);
                }
                return;
            }
            if (is_scalar($value) && $value !== '') {
                $key = strtolower($path);
                if (strpos($key, 'token') !== false || strpos($key, 'secret') !== false || strpos($key, 'authorization') !== false) {
                    return;
                }
                $text = trim((string)$value);
                if ($text !== '') {
                    $parts[] = $path . '=' . $text;
                }
            }
        };
        $walker($decoded);
        if ($parts) {
            return substr(implode('; ', $parts), 0, 900);
        }
        return substr(trim(strip_tags($body)), 0, 900);
    }
}
