<?php

namespace App\Enums;

enum AuditAction: string
{
    case AccountAdjust = 'account.adjust';
    case RewardCreate = 'reward.create';
    case RewardUpdate = 'reward.update';
    case RewardDelete = 'reward.delete';
    case CampaignCreate = 'campaign.create';
    case CampaignUpdate = 'campaign.update';
    case CampaignDelete = 'campaign.delete';
    case CampaignPrizeCreate = 'campaign.prize.create';

    /**
     * Human-readable label for the audit action.
     */
    public function label(): string
    {
        return match ($this) {
            self::AccountAdjust => 'Account adjusted',
            self::RewardCreate => 'Reward created',
            self::RewardUpdate => 'Reward updated',
            self::RewardDelete => 'Reward deleted',
            self::CampaignCreate => 'Campaign created',
            self::CampaignUpdate => 'Campaign updated',
            self::CampaignDelete => 'Campaign deleted',
            self::CampaignPrizeCreate => 'Campaign prize created',
        };
    }
}
