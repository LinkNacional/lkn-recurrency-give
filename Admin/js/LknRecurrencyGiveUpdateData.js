(function ($) {
  'use strict'

  $(document).ready(function () {
    // Set the background to white
    $('#wpwrap').css('background', '#fff')

    // Function to clear the message div
    function clearMessage() {
      $('#lkn-message').empty() // Clears any previous message
    }

    // Common function to make an AJAX request
    function makeRequest(url, button, responseDiv, successMessage, errorMessage) {
      $.ajax({
        url,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        data: JSON.stringify({}),
        success: function (response) {
          if (response.status) {
            responseDiv.text(response.message || successMessage)
          } else {
            responseDiv.text(response.message || errorMessage)
          }
        },
        error: function () {
          responseDiv.text(errorMessage)
        },
        complete: function () {
          // Re-enable buttons and reset text
          $('#update-cielo-btn').prop('disabled', false).text($('#update-cielo-btn').data('original-text'))
          $('#clear-recurrences-btn').prop('disabled', false).text($('#clear-recurrences-btn').data('original-text'))
        }
      })
    }

    // Update Cielo data
    $('#update-cielo-btn').on('click', function (e) {
      e.preventDefault()

      const button = $(this)
      const responseDiv = $('#lkn-message')

      // Messages
      const updatingText = lknRecurrencyTexts.updating || 'Updating...'
      const updateButtonText = lknRecurrencyTexts.updateButton || 'Update Data'
      const successMessage = lknRecurrencyTexts.successMessage || 'Update completed successfully!'
      const noUpdateMessage = lknRecurrencyTexts.noUpdateMessage || 'No updates were needed.'
      const errorMessage = lknRecurrencyTexts.errorMessage || 'An error occurred while updating. Please try again.'

      // Clear previous message
      clearMessage()

      // Disable both buttons and show loading text
      button.prop('disabled', true).data('original-text', button.text()).text(updatingText)
      $('#clear-recurrences-btn').prop('disabled', true)

      // Make AJAX request
      makeRequest('/wp-json/lkn-recurrency/v1/update/', button, responseDiv, successMessage, errorMessage)
    })

    // Clear recurrences
    $('#clear-recurrences-btn').on('click', function (e) {
      e.preventDefault()

      const button = $(this)
      const responseDiv = $('#lkn-message')

      // Messages
      const clearingText = lknRecurrencyTexts.clearing || 'Clearing...'
      const clearButtonText = lknRecurrencyTexts.clearButton || 'Clear Recurrences'
      const successMessage = lknRecurrencyTexts.successMessage || 'Recurrences cleared successfully! This action will remove all recurrences without associated donations.'
      const errorMessage = lknRecurrencyTexts.errorMessage || 'An error occurred while clearing. Please try again.'

      // Confirmation before clearing
      if (confirm(lknRecurrencyTexts.confirmClear || 'Are you sure you want to clear all recurrences without donations associated to them?')) {
        // Clear previous message
        clearMessage()

        // Disable both buttons and show loading text
        button.prop('disabled', true).data('original-text', button.text()).text(clearingText)
        $('#update-cielo-btn').prop('disabled', true)

        // Make AJAX request
        makeRequest('/wp-json/lkn-recurrency/v1/clear/', button, responseDiv, successMessage, errorMessage)
      }
    })
  })
})(jQuery)
