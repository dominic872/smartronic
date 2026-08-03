<?php

if (!function_exists('smartronicCampaignIdMap')) {
    function smartronicCampaignIdMap(): array
    {
        return [
            '22154028165' => 'GL',
            '23084756848' => 'PM',
            '22848100317' => 'DG',
            '23085059220' => 'SO25',
            '23220779450' => '13K',
            '23546405439' => 'QK',
            '23564758451' => 'HSR 10KM',
            '23781799333' => 'HSR 20KM',
            '23643892728' => 'GA_GMaps',
            '23815458885' => '26MAY-2',
            '23820711533' => '26MAY-1',
            '23862610003' => 'GL-Chennai',
            '23922125031' => '26MAY-2-Chennai',
        ];
    }
}

if (!function_exists('smartronicCampaignLabelFromId')) {
    function smartronicCampaignLabelFromId($campaignId, string $fallback = ''): string
    {
        $campaignId = preg_replace('/\D+/', '', (string)$campaignId);
        $map = smartronicCampaignIdMap();
        if ($campaignId !== '' && isset($map[$campaignId])) {
            return $map[$campaignId];
        }
        return trim($fallback) !== '' ? trim($fallback) : ($campaignId !== '' ? $campaignId : 'Unknown');
    }
}
