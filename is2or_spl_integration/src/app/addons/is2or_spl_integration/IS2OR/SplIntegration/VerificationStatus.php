<?php

namespace IS2OR\SplIntegration;

class VerificationStatus
{
    const PENDING    = 'pending';
    const VERIFIED   = 'verified';
    const FAILED     = 'failed';
    const OVERRIDDEN = 'overridden';

    /**
     * All valid statuses.
     */
    const ALL = [
        self::PENDING,
        self::VERIFIED,
        self::FAILED,
        self::OVERRIDDEN,
    ];

    /**
     * Statuses that allow storefront visibility.
     */
    const VISIBLE = [
        self::VERIFIED,
        self::OVERRIDDEN,
    ];
}
