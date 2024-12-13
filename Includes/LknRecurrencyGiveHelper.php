<?php

namespace Lkn\RecurrencyGive\Includes;

class LknRecurrencyGiveHelper
{
    public static function get_texts()
    {
        return [
            'total_donations' => __('Total donations', 'lkn-recurrency-give'),
            'date_label' => __('Dates', 'lkn-recurrency-give'),
            'num_donations' => __('Number of donations:', 'lkn-recurrency-give'),
            'no_data' => __('No data for the selected month.', 'lkn-recurrency-give'),
            'no_data_day' => __('No data for the selected day.', 'lkn-recurrency-give'),
            'error_message' => __('An error occurred. Please try again.', 'lkn-recurrency-give'),
            'donation_id' => __('Donation ID:', 'lkn-recurrency-give'),
            'user_id' => __('User ID:', 'lkn-recurrency-give'),
            'value' => __('Value:', 'lkn-recurrency-give'),
            'currency' => __('Currency:', 'lkn-recurrency-give'),
            'name' => __('Name:', 'lkn-recurrency-give'),
            'email' => __('Email:', 'lkn-recurrency-give'),
            'payment_mode' => __('Payment Mode:', 'lkn-recurrency-give'),
            'completion_date' => __('Completion Date:', 'lkn-recurrency-give'),
            'expiration' => __('Expiration:', 'lkn-recurrency-give'),
        ];
    }
}
