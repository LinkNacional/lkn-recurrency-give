<?php

namespace Lkn\RecurrencyGive\Includes;

class LknRecurrencyGiveHelper
{
    public static function get_texts()
    {
        return [
            'total_donations' => __('Total donations', 'lkn-recurrency-give'),
            'date_label' => __('Dates', 'lkn-recurrency-give'),
            'day_label' => __('Day', 'lkn-recurrency-give'),
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
            'creation_date' => __('Creation Date:', 'lkn-recurrency-give'),
            'expiration' => __('Expiration:', 'lkn-recurrency-give'),
            'next_month' => __('Expected amount for next month', 'lkn-recurrency-give'),
            'recurrency' => __('Recurrency', 'lkn-recurrency-give'),
            'month_label' => __('Select month:', 'lkn-recurrency-give'),
            'year_label' => __('Select year:', 'lkn-recurrency-give'),
            'currency_label' => __('Select currency:', 'lkn-recurrency-give'),
            'payment_mode_label' => __('Select payment mode:', 'lkn-recurrency-give'),
            'january' => __('January', 'lkn-recurrency-give'),
            'february' => __('February', 'lkn-recurrency-give'),
            'march' => __('March', 'lkn-recurrency-give'),
            'april' => __('April', 'lkn-recurrency-give'),
            'may' => __('May', 'lkn-recurrency-give'),
            'june' => __('June', 'lkn-recurrency-give'),
            'july' => __('July', 'lkn-recurrency-give'),
            'august' => __('August', 'lkn-recurrency-give'),
            'september' => __('September', 'lkn-recurrency-give'),
            'october' => __('October', 'lkn-recurrency-give'),
            'november' => __('November', 'lkn-recurrency-give'),
            'december' => __('December', 'lkn-recurrency-give'),
            'currency_brl' => __('BRL', 'lkn-recurrency-give'),
            'payment_mode_test' => __('Test', 'lkn-recurrency-give'),
            'payment_mode_production' => __('Production', 'lkn-recurrency-give'),
            'reviewButtonTitle' => __('Review', 'lkn-recurrency-give'),
            'reviewButtonText' => __('Review', 'lkn-recurrency-give'),
            'reviewIconAlt' => __('Review icon', 'lkn-recurrency-give')
        ];
    }

    public static function get_texts_update()
    {
        return [
            'updating' => __('Updating...', 'lkn-recurrency-give'),
            'updateButton' => __('Update Data', 'lkn-recurrency-give'),
            'successMessage' => __('Update completed successfully!', 'lkn-recurrency-give'),
            'noUpdateMessage' => __('No updates were needed.', 'lkn-recurrency-give'),
            'errorMessage' => __('An error occurred while updating. Please try again.', 'lkn-recurrency-give'),
            'clearing' => __('Clearing...', 'lkn-recurrency-give'),
            'clearButton' => __('Clear Recurrences', 'lkn-recurrency-give'),
            'confirmClear' => __('Are you sure you want to clear all recurrences without donations associated to them?', 'lkn-recurrency-give'),
        ];
    }
}
