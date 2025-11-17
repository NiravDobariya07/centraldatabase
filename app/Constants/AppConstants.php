<?php

namespace App\Constants;

class AppConstants
{
    // Logging Categories
    public const LOG_CATEGORIES = [
        'ERRORS'     => 'errors',
        'EXCEPTIONS' => 'exceptions',
        'EVENTS'     => 'events',
        'REQUEST'    => 'request',
        'RESPONSE'   => 'response',
    ];

    public const EXPORT_STATUSES = [
        'ACTIVE'  => 'active',
        'PAUSED'  => 'paused',
        'STOPPED' => 'stopped',
    ];

    public const EXPORT_FREQUENCY_OPTIONS = [
        'ONE_TIME' => 'one_time',
        'DAILY'    => 'daily',
        'WEEKLY'   => 'weekly',
        'MONTHLY'  => 'monthly',
        'CUSTOM'   => 'custom',
    ];

    public const EXPORT_DAYS_OF_WEEK = [
        'MONDAY'    => 'monday',
        'TUESDAY'   => 'tuesday',
        'WEDNESDAY' => 'wednesday',
        'THURSDAY'  => 'thursday',
        'FRIDAY'    => 'friday',
        'SATURDAY'  => 'saturday',
        'SUNDAY'    => 'sunday',
    ];

    public const EXPORT_RUNING_STATUS = [
        'SCHEDULED' => 'scheduled',
        'SUCCESS'   => 'success',
        'FAILED'    => 'failed',
        'PENDING'   => 'pending',
        'PAUSED'    => 'paused',
        'STOPPED'   => 'stopped',
    ];
}
