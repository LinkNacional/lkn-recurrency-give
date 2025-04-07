(function ($) {
  'use strict'

  $(document).ready(function () {
    let chartInstance = null
    let topFiveDonorsChartIntance = null
    function getTab() {
      // Cria o botão e o adiciona à navegação
      const customButton = $('<button>', {
        text: lknRecurrencyTexts.recurrency,
        class: 'nav-tab',
        disabled: true,
        css: {
          cursor: 'not-allowed',
          backgroundColor: '#ccc',
          color: '#666',
          border: '1px solid #ddd',
          transition: 'background-color 0.3s ease, color 0.3s ease, border 0.3s ease'
        }
      })
      $('.nav-tab-wrapper').append(customButton)

      $.ajax({
        url: '/wp-json/lkn-recurrency/v1/content/',
        method: 'GET',
        contentType: 'application/json',
        success: function (data) {
          $('.givewp-grid').before(data)
          $('.lkn-wrap').css('display', 'none')

          customButton.prop('disabled', false)
          customButton.css({
            cursor: 'pointer',
            backgroundColor: '',
            color: ''
          })

          customButton.on('click', function () {
            const buttonHtml = `
              <button
                  title="${lknRecurrencyTexts.reviewButtonTitle}"
                  id="lkn-review-button"
              >
                  <p>${lknRecurrencyTexts.reviewButtonText}</p>
                  <img
                      src="${lknRecurrencyVars.urlWebsite}Includes/assets/icons/review.svg"
                      alt="${lknRecurrencyTexts.reviewIconAlt}"
                  >
              </button>
            `

            const toggleButtonHtml = $('<button>', {
              id: 'toggleMenuButton',
              css: {
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                height: '30px',
                width: '30px',
                borderRadius: '4px',
                border: '1px solid #343434',
                cursor: 'pointer'
              },
              title: 'Menu'
            }).append($('<img>', {
              src: `${lknRecurrencyVars.urlWebsite}Includes/assets/icons/menu.svg`,
              alt: 'Review icon',
              css: {
                height: '20px',
                width: '20px'
              }
            }))

            $('.givewp-filters').css('justify-content', 'flex-end').css('gap', '10px').empty()
            $('.givewp-filters').append(buttonHtml)
            $('.givewp-filters').append(toggleButtonHtml)
            createToggleMenu()

            $(this).prop('disabled', true).css({
              cursor: 'not-allowed',
              backgroundColor: '#f1f1f1',
              color: '#000'
            })

            // Remove o conteúdo da grid
            $('.givewp-grid').empty()

            // Remove a classe 'nav-tab-active' do item atualmente ativo
            $('.nav-tab-active').removeClass('nav-tab-active')
              .off('click') // Remove o evento de clique antigo
              .on('click', function () {
                location.reload()
              })

            // Adiciona a classe 'nav-tab-active' no botão clicado
            $(this).addClass('nav-tab-active')

            // Exibe o gráfico após o clique
            $('.lkn-wrap').css('display', 'flex')

            const monthSelect = $('#month-select')
            const yearSelect = $('#year-select')
            const currencySelect = $('#currency-select')
            const modeSelect = $('#mode-select')

            monthSelect.off('change', fetchDataAndRenderChart)
            yearSelect.off('change', fetchDataAndRenderChart)
            currencySelect.off('change', fetchDataAndRenderChart)
            modeSelect.off('change', fetchDataAndRenderChart)

            // Event listeners para os selects
            monthSelect.on('change', function () {
              fetchDataAndRenderChart(monthSelect, yearSelect, currencySelect, modeSelect)
            })
            yearSelect.on('change', function () {
              fetchDataAndRenderChart(monthSelect, yearSelect, currencySelect, modeSelect)
            })
            currencySelect.on('change', function () {
              fetchDataAndRenderChart(monthSelect, yearSelect, currencySelect, modeSelect)
            })
            modeSelect.on('change', function () {
              fetchDataAndRenderChart(monthSelect, yearSelect, currencySelect, modeSelect)
            })

            // Chama a função para renderizar o gráfico
            fetchDataAndRenderChart(monthSelect, yearSelect, currencySelect, modeSelect)

            // Call verifyPluginStatus on page load to set the display state
            verifyPluginStatus()
          })
        },
        error: function (error) {
          // Cria a nova div de erro, caso não exista
          const errorDiv = $('<div>')

          // Adiciona a mensagem de erro dentro de um <p> dentro da div
          const errorMessage = $('<p>').text(error.responseJSON?.message || error.statusText)
          errorDiv.append(errorMessage)

          // Insere a div de erro antes do givewp-grid
          $('.givewp-grid').before(errorDiv)

          // Estiliza o botão com um vermelho mais claro
          customButton.css({
            backgroundColor: '#df8277', // Cor de fundo do botão
            color: '#fff',
            cursor: 'pointer'
          })

          // Ativa o botão
          customButton.prop('disabled', false)

          // Ação de clique do botão
          customButton.on('click', function () {
            $('.nav-tab-active').removeClass('nav-tab-active')
              .off('click') // Remove o evento de clique antigo
              .on('click', function () {
                location.reload()
              })

            $(this).prop('disabled', true).css({
              backgroundColor: '#e74c3c',
              cursor: 'not-allowed'
            })

            $('.givewp-filters').empty()

            $('.givewp-grid').empty()

            $('.lkn-wrap').css('display', 'flex')
          })
        }
      })
    }

    function showLoading() {
      $('#lkn-recurrency-loading').fadeIn()
    }

    // Oculta o loading screen após o carregamento
    function hideLoading() {
      $('#lkn-recurrency-loading').fadeOut()
    }

    function fetchDataAndRenderChart(monthSelect, yearSelect, currencySelect, modeSelect) {
      showLoading()
      const selectedMonth = monthSelect.val()
      const selectedYear = yearSelect.val()
      const selectedCurrency = currencySelect.val()
      const selectedMode = modeSelect.val()
      $.getJSON(`${lknRecurrencyVars.apiUrlBase}&month=${selectedMonth}&year=${selectedYear}&currency=${selectedCurrency}&mode=${selectedMode}&nonce=${lknRecurrencyVars.nonce}`)
        .done(function (responseData) {
          $('#recurrencyChart').show()
          $('#top-five-donations-chart').show()

          $('.lkn-error-message').hide()

          if (!responseData.success) {
            $('#recurrencyChart').hide()
            $('#top-five-donations-chart').hide()
            $('.lkn-error-message').show().html(responseData.data.message || lknRecurrencyTexts.error_message)

            $('#lkn-table tbody').empty()
            $('#lkn-top-last-donations-list').empty()
            $('#lkn-review-button').off('click')
            $('#lkn-modal-content').empty()

            const selectedCurrency = currencySelect.val()
            const formatTotal = formatCurrency(selectedCurrency)
            const monthlyValue = $('#lkn-value')
            const nextMonthValue = $('#lkn-value-review-monthly')
            const annualValue = $('#lkn-value-review-yearly')

            const formatTotalMonthly = `<p>${formatTotal.format(0)}</p>`
            monthlyValue.html(formatTotalMonthly)

            const formatNextMonthTotal = `<p>${formatTotal.format(0)}</p>`
            nextMonthValue.html(formatNextMonthTotal)

            const formatAnnualTotal = `<p> ${formatTotal.format(0)}</p>`
            annualValue.html(formatAnnualTotal)
            return
          }

          const formatResponse = responseData

          if (formatResponse.data.donations) {
            formatResponse.data.donations.forEach((donation) => {
              // Extrai a data (YYYY-MM-DD) ignorando a hora
              const donationDate = donation.created.split(' ')[0] // Ex: "2024-12-31"

              // Calcula o mês da doação
              const donationMonth = new Date(donationDate + 'T00:00:00Z').getMonth() + 1 // Mês original

              // Substitui a data apenas se o mês for diferente do selecionado
              if (donationMonth !== parseInt(selectedMonth)) {
                // Extrai o dia do expiration
                const expirationDay = new Date(donation.expiration.split(' ')[0] + 'T00:00:00Z').getUTCDate()

                // Atualiza o campo "currentDate"
                donation.currentDate = `${selectedYear}-${String(selectedMonth).padStart(2, '0')}-${String(expirationDay).padStart(2, '0')} ${donation.created.split(' ')[1]}`
                donation.day = expirationDay
              } else {
                donation.currentDate = donation.created
              }
            })
          } else {
            $('#recurrencyChart').hide()
            $('#top-five-donations-chart').hide()
            $('.lkn-error-message').show().html(responseData.data.message || lknRecurrencyTexts.error_message)

            $('#lkn-table tbody').empty()
            $('#lkn-top-last-donations-list').empty()
            $('#lkn-review-button').off('click')
            $('#lkn-modal-content').empty()

            const selectedCurrency = currencySelect.val()
            const formatTotal = formatCurrency(selectedCurrency)
            const monthlyValue = $('#lkn-value')
            const nextMonthValue = $('#lkn-value-review-monthly')
            const annualValue = $('#lkn-value-review-yearly')

            const formatTotalMonthly = `<p>${formatTotal.format(0)}</p>`
            monthlyValue.html(formatTotalMonthly)

            const formatNextMonthTotal = `<p>${formatTotal.format(0)}</p>`
            nextMonthValue.html(formatNextMonthTotal)

            const formatAnnualTotal = `<p> ${formatTotal.format(0)}</p>`
            annualValue.html(formatAnnualTotal)
            return
          }

          const data = formatResponse.data

          let labels = []
          let groupedDonations = []

          const lastDay = new Date(selectedYear, selectedMonth, 0)

          // Formatar as datas
          const formattedLastDay = formatDate(lastDay)

          const selectedCurrency = $('#currency-select').val()
          const formatTotal = formatCurrency(selectedCurrency)
          const monthlyValue = $('#lkn-value')
          const nextMonthValue = $('#lkn-value-review-monthly')
          const annualValue = $('#lkn-value-review-yearly')

          if (data) {
            modalSetting(formatResponse)
            populateTable(formatResponse)
            renderTopFiveDonorsChart(formatResponse)
            renderLastFiveDonationsList(formatResponse)

            const formatMonthlyTotal = `<p>${formatTotal.format(data.total.toFixed(2))}</p>`
            monthlyValue.html(formatMonthlyTotal)

            const formatNextMonthTotal = `<p>${formatTotal.format(parseFloat(data.next_month_total.replace(',', '')))}</p>`
            nextMonthValue.html(formatNextMonthTotal)

            const formatAnnualTotal = `<p> ${formatTotal.format(parseFloat(data.annual_total.replace(',', '')))}</p>`
            annualValue.html(formatAnnualTotal)

            const donationSummary = data.donations.reduce(
              (accumulator, donation) => {
                // Extrai a data (YYYY-MM-DD) ignorando a hora
                const donationDate = donation.currentDate.split(' ')[0] // Ex: "2024-12-31"

                // Adiciona a data ao labelsArray, se não existir
                if (!accumulator.dateLabels.includes(donationDate)) {
                  accumulator.dateLabels.push(donationDate)
                }

                // Acumula o valor no dailyDonationTotals para o dia correspondente
                if (!accumulator.dailyDonationTotals[donationDate]) {
                  accumulator.dailyDonationTotals[donationDate] = 0
                }
                accumulator.dailyDonationTotals[donationDate] += 1

                return accumulator
              },
              { dateLabels: [], dailyDonationTotals: {} }
            )

            const { dateLabels, dailyDonationTotals } = donationSummary

            groupedDonations = Object.values(dailyDonationTotals)
            labels = dateLabels
          } else {
            $('#recurrencyChart').hide()
            $('#top-five-donations-chart').hide()
            $('.lkn-error-message').show().html(responseData.data.message || lknRecurrencyTexts.error_message)

            $('#lkn-table tbody').empty()
            $('#lkn-top-last-donations-list').empty()
            $('#lkn-review-button').off('click')
            $('#lkn-modal-content').empty()

            const selectedCurrency = currencySelect.val()
            const formatTotal = formatCurrency(selectedCurrency)
            const monthlyValue = $('#lkn-value')
            const nextMonthValue = $('#lkn-value-review-monthly')
            const annualValue = $('#lkn-value-review-yearly')

            const formatTotalMonthly = `<p>${formatTotal.format(0)}</p>`
            monthlyValue.html(formatTotalMonthly)

            const formatNextMonthTotal = `<p>${formatTotal.format(0)}</p>`
            nextMonthValue.html(formatNextMonthTotal)

            const formatAnnualTotal = `<p> ${formatTotal.format(0)}</p>`
            annualValue.html(formatAnnualTotal)
          }

          const daysOfMonth = []
          const formattedLastDayDate = new Date(formattedLastDay + 'T23:59:59')

          // Loop para gerar todas as datas do mês até o formattedLastDay
          for (let d = new Date(selectedYear, selectedMonth - 1, 1); d <= formattedLastDayDate; d.setDate(d.getDate() + 1)) {
            daysOfMonth.push(d.toISOString().split('T')[0])
          }

          // Mapear os valores de doações, associando valores a cada data ou null se não houver doação
          const numberOfDonationsPerDay = daysOfMonth.map(label => {
            const index = labels.indexOf(label)
            return index !== -1 ? groupedDonations[index] : 0
          })

          updateChart(daysOfMonth, numberOfDonationsPerDay, formatResponse)
        })
        .fail(function (error) {
          $('#recurrencyChart').hide()
          $('#top-five-donations-chart').hide()
          $('.lkn-error-message').show().text(error.message || lknRecurrencyTexts.error_message)

          $('#lkn-table tbody').empty()
          $('#lkn-top-last-donations-list').empty()
          $('#lkn-review-button').off('click')
          $('#lkn-modal-content').empty()

          const formatTotal = formatCurrency(selectedCurrency)
          const monthlyValue = $('#lkn-value')
          const nextMonthValue = $('#lkn-value-review-monthly')
          const annualValue = $('#lkn-value-review-yearly')

          const formatTotalMonthly = `<p>${formatTotal.format(0)}</p>`
          monthlyValue.html(formatTotalMonthly)

          const formatNextMonthTotal = `<p>${formatTotal.format(0)}</p>`
          nextMonthValue.html(formatNextMonthTotal)

          const formatAnnualTotal = `<p> ${formatTotal.format(0)}</p>`
          annualValue.html(formatAnnualTotal)
        })
        .always(function () {
          hideLoading() // Oculta o loading
        })
    }

    function updateChart(labels, data, responseData) {
      const currencySelect = $('#currency-select')
      const selectedCurrency = currencySelect.val()
      let dateFomat = 'yyyy-MM-dd'
      const ctx = document.getElementById('recurrencyChart').getContext('2d')

      if (selectedCurrency === 'BRL') {
        dateFomat = 'dd/MM/yyyy'
      }
      if (chartInstance) {
        chartInstance.destroy()
      }

      chartInstance = new LknChart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: lknRecurrencyTexts.total_donations,
            data,
            borderColor: 'rgba(122, 208, 58, 1)',
            backgroundColor: 'rgba(122, 208, 58, 0.2)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgba(100, 172, 45, 1)',
            pointBorderColor: 'rgba(100, 172, 45, 1)',
            pointRadius: 4,
            pointHoverRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              type: 'time',
              time: {
                parser: 'yyyy-MM-dd',
                tooltipFormat: dateFomat,
                unit: 'day',
                displayFormats: {
                  day: dateFomat
                }
              },
              title: {
                display: true,
                text: lknRecurrencyTexts.date_label
              }
            },
            y: {
              beginAtZero: true,
              suggestedMax: Math.max(...data) + 3,
              title: {
                display: true,
                text: lknRecurrencyTexts.num_donations
              }
            }
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: function (tooltipItem) {
                  return `${lknRecurrencyTexts.num_donations} ${tooltipItem.raw}`
                }
              }
            }
          },
          onClick: (event, elements) => {
            if (elements.length > 0) {
              const pointIndex = elements[0].index
              const clickedDate = labels[pointIndex]
              modalSettingPerDay(responseData, clickedDate)
            }
          },
          onHover: (event, elements) => {
            const chartCanvas = ctx.canvas
            if (elements.length > 0) {
              chartCanvas.style.cursor = 'pointer'
            } else {
              chartCanvas.style.cursor = 'default'
            }
          },
          interaction: {
            mode: 'nearest',
            intersect: true
          }
        }
      })
    }

    function modalSettingPerDay(responseData, clickedDate) {
      const modal = $('#lkn-review-modal')
      const modalContent = $('#lkn-modal-content')
      const selectedCurrency = $('#currency-select').val()
      const formatTotal = formatCurrency(selectedCurrency)

      // Renderizar os dados no modal
      let content = ''

      // Extrair o dia da data clicada
      const customDay = clickedDate.split(' ')[0].split('-')[2]

      // Filtrar as doações para o dia clicado
      const donationsByDay = responseData.data.donations.filter((donation) => parseInt(donation.day) === parseInt(customDay))
      content += `<h3>${lknRecurrencyTexts.date_label}: ${responseData.data.date}</h3>`

      if (donationsByDay.length === 0) {
        content = `<strong>${lknRecurrencyTexts.no_data_day}</strong>`
      } else {
        content += '<div class="lkn-review-data">'
        content += `<h4>${lknRecurrencyTexts.day_label}: ${customDay}</h4><ul>`

        donationsByDay.forEach((donation, index, array) => {
          content += `
                  <li>
                      <strong>${lknRecurrencyTexts.donation_id}</strong> ${donation.donation_id} <br>
                      <strong>${lknRecurrencyTexts.user_id}</strong> ${donation.customer_id} <br>
                      <strong>${lknRecurrencyTexts.value}</strong> ${formatTotal.format(donation.recurring_amount)} <br>
                      <strong>${lknRecurrencyTexts.currency}</strong> ${donation.payment_currency} <br>
                      <strong>${lknRecurrencyTexts.name}</strong> ${capitalizeName(donation.billing_first_name)} ${capitalizeName(donation.billing_last_name)} <br>
                      <strong>${lknRecurrencyTexts.email}</strong> ${donation.donor_email} <br>
                      <strong>${lknRecurrencyTexts.payment_mode}</strong> ${donation.payment_mode} <br>
                      <strong>${lknRecurrencyTexts.creation_date}</strong> ${donation.created} <br>
                      <strong>${lknRecurrencyTexts.expiration}</strong> ${donation.expiration}
                  </li>
              `
          if (index < array.length - 1) {
            content += '<hr />'
          }
        })

        content += '</ul></div>'
      }

      modalContent.html(content)
      modal.fadeIn()
    }

    function modalSetting(responseData) {
      // Abrir modal
      $('#lkn-review-button').on('click', function () {
        const modal = $('#lkn-review-modal')
        const modalContent = $('#lkn-modal-content')
        const selectedCurrency = $('#currency-select').val()
        const formatTotal = formatCurrency(selectedCurrency)
        modal.fadeIn()

        // Renderizar os dados no modal
        let content = ''

        // Agrupar as doações por dia
        const donationsByDay = {}
        if (responseData.data.donations.length > 0) {
          responseData.data.donations.forEach((donation) => {
            // Usar o campo 'day' diretamente
            const day = donation.day

            if (!donationsByDay[day]) donationsByDay[day] = []
            donationsByDay[day].push(donation)
          })

          // Adicionar o cabeçalho com a data agrupada
          content += `<h3>${lknRecurrencyTexts.date_label}: ${responseData.data.date}</h3>`

          let dayIndex = 0
          // Loop pelos dias e exibição dos dados
          for (const day in donationsByDay) {
            content += `<div class="lkn-review-data" style="${dayIndex > 0 ? 'margin-top: 20px' : ''}">`
            content += `<h4>${lknRecurrencyTexts.day_label}: ${day}</h4><ul>`
            donationsByDay[day].forEach((donation, index, array) => {
              content += `
                <li>
                  <strong>${lknRecurrencyTexts.donation_id}</strong> ${donation.donation_id} <br>
                  <strong>${lknRecurrencyTexts.user_id}</strong> ${donation.customer_id} <br>
                  <strong>${lknRecurrencyTexts.value}</strong> ${formatTotal.format(donation.recurring_amount)} <br>
                  <strong>${lknRecurrencyTexts.currency}</strong> ${donation.payment_currency} <br>
                  <strong>${lknRecurrencyTexts.name}</strong> ${capitalizeName(donation.billing_first_name)} ${capitalizeName(donation.billing_last_name)} <br>
                  <strong>${lknRecurrencyTexts.email}</strong> ${donation.donor_email} <br>
                  <strong>${lknRecurrencyTexts.payment_mode}</strong> ${donation.payment_mode} <br>
                  <strong>${lknRecurrencyTexts.creation_date}</strong> ${donation.created} <br>
                  <strong>${lknRecurrencyTexts.expiration}</strong> ${donation.expiration}
                </li>
              `
              if (index < array.length - 1) {
                content += '<hr />'
              }
            })
            content += '</ul></div>'
            dayIndex += 1
          }
        } else {
          content = `<strong>${lknRecurrencyTexts.no_data}</strong>`
        }

        modalContent.html(content)
      })

      // Fechar modal
      $('#lkn-close-review-modal, #lkn-review-modal').on('click', function (e) {
        if ($(e.target).is('#lkn-close-review-modal') || $(e.target).is('#lkn-review-modal')) {
          $('#lkn-review-modal').fadeOut()
        }
      })
    }

    function formatCurrency(currency) {
      return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency
      })
    }

    function formatDate(date) {
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      return `${year}-${month}-${day}`
    }

    function formatTableDate(dateString) {
      const date = new Date(dateString)
      const options = { month: 'long', day: 'numeric', year: 'numeric' }
      const formattedDate = date.toLocaleDateString('pt-BR', options)
      const [day, month, year] = formattedDate.split(' de ')
      return `${month.charAt(0).toUpperCase() + month.slice(1)} ${day}, ${year}`
    }

    function capitalizeName(name) {
      return name.replace(/\b\w/g, char => char.toUpperCase())
    }

    function populateTable(responseData) {
      const tableBody = document.querySelector('#lkn-table tbody')
      tableBody.innerHTML = '' // Clear existing rows

      responseData.data.donations.forEach(donation => {
        const row = document.createElement('tr')
        row.onclick = () => modalSettingPerDonation(responseData, donation.donation_id)

        row.innerHTML = `
              <td>${formatTableDate(donation.created)}</td>
              <td>${formatTableDate(donation.expiration)}</td>
              <td>${capitalizeName(donation.billing_first_name)} ${capitalizeName(donation.billing_last_name)}</td>
              <td>${parseFloat(donation.recurring_amount).toFixed(2)}</td>
              <td>${donation.customer_id}</td>
          `

        tableBody.appendChild(row)
      })
    }

    function modalSettingPerDonation(responseData, donationId) {
      const selectedCurrency = $('#currency-select').val()
      const formatTotal = formatCurrency(selectedCurrency)
      const donation = responseData.data.donations.find(d => d.donation_id === donationId)
      const day = donation.day
      let content = ''

      if (donation) {
        // Adicionar o cabeçalho com a data agrupada
        content += `<h3>${lknRecurrencyTexts.date_label}: ${responseData.data.date}</h3>`

        content += '<div class="lkn-review-data">'
        content += `<h4>${lknRecurrencyTexts.day_label}: ${day}</h4><ul>`

        content += `
              <li>
                  <strong>${lknRecurrencyTexts.donation_id}</strong> ${donation.donation_id} <br>
                  <strong>${lknRecurrencyTexts.user_id}</strong> ${donation.customer_id} <br>
                  <strong>${lknRecurrencyTexts.value}</strong> ${formatTotal.format(donation.recurring_amount)} <br>
                  <strong>${lknRecurrencyTexts.currency}</strong> ${donation.payment_currency} <br>
                  <strong>${lknRecurrencyTexts.name}</strong> ${capitalizeName(donation.billing_first_name)} ${capitalizeName(donation.billing_last_name)} <br>
                  <strong>${lknRecurrencyTexts.email}</strong> ${donation.donor_email} <br>
                  <strong>${lknRecurrencyTexts.payment_mode}</strong> ${donation.payment_mode} <br>
                  <strong>${lknRecurrencyTexts.creation_date}</strong> ${donation.created} <br>
                  <strong>${lknRecurrencyTexts.expiration}</strong> ${donation.expiration}
              </li>
      `

        content += '</ul></div>'
        // Populate modal with content
        const modalContent = $('#lkn-modal-content')
        modalContent.html(content)

        // Open modal
        const modal = $('#lkn-review-modal')
        modal.fadeIn()
      } else {
        content = `<strong>${lknRecurrencyTexts.no_data}</strong>`
      }
    }

    function renderTopFiveDonorsChart(responseData) {
      const donations = responseData.data.donations
      const selectedCurrency = $('#currency-select').val()
      const formatTotal = formatCurrency(selectedCurrency)

      // Sort donations by recurring_amount in descending order and take the top 5
      const groupedDonations = donations.reduce((acc, donation) => {
        const customerId = donation.customer_id
        if (!acc[customerId]) {
          acc[customerId] = {
            ...donation,
            recurring_amount: parseFloat(donation.recurring_amount)
          }
        } else {
          acc[customerId].recurring_amount += parseFloat(donation.recurring_amount)
        }
        return acc
      }, {})

      // Converter o objeto agrupado de volta para um array
      const aggregatedDonations = Object.values(groupedDonations)

      // Ordenar as doações agregadas e pegar as 5 maiores
      const topFiveDonations = aggregatedDonations
        .sort((a, b) => b.recurring_amount - a.recurring_amount)
        .slice(0, 5)

      const labels = topFiveDonations.map(donation => `${capitalizeName(donation.billing_first_name)} ${capitalizeName(donation.billing_last_name)}`)
      const data = topFiveDonations.map(donation => parseFloat(donation.recurring_amount))

      if (topFiveDonorsChartIntance) {
        topFiveDonorsChartIntance.destroy()
      }

      const ctx = document.getElementById('top-five-donations-chart').getContext('2d')
      topFiveDonorsChartIntance = new LknChart(ctx, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [
            {
              data,
              backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
              hoverBackgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top'
            },
            tooltip: {
              callbacks: {
                label: function (tooltipItem) {
                  const value = tooltipItem.raw
                  return formatTotal.format(value)
                }
              }
            }
          },
          layout: {
            padding: {
              left: 10,
              right: 10,
              top: 10,
              bottom: 10
            }
          }
        }
      })

      // Set max height and width for the chart
      document.getElementById('top-five-donations-chart').style.maxHeight = '400px'
      document.getElementById('top-five-donations-chart').style.maxWidth = '400px'
    }

    function getInitials(firstName, lastName) {
      return `${firstName.charAt(0).toUpperCase()}${lastName.charAt(0).toUpperCase()}`
    }

    function renderLastFiveDonationsList(responseData) {
      const reversedDonations = responseData.data.donations.reverse()

      // Inicializar um array para agrupar as doações por customer_id
      const groupedDonations = []

      // Inicializar um contador para o número de doações únicas
      let uniqueDonationsCount = 0

      // Iterar sobre o array de doações invertido
      for (const donation of reversedDonations) {
        const customerId = donation.customer_id

        // Verificar se já existe uma doação com o mesmo customer_id
        const existingDonationIndex = groupedDonations.findIndex(d => d.customer_id === customerId)

        if (existingDonationIndex === -1) {
          // Se ainda não temos 5 doações únicas, adicionar a doação ao grupo
          if (uniqueDonationsCount < 5) {
            groupedDonations.push({
              ...donation,
              recurring_amount: parseFloat(donation.recurring_amount)
            })
            uniqueDonationsCount++
          }
        } else {
          // Acumular o valor da doação existente
          groupedDonations[existingDonationIndex].recurring_amount += parseFloat(donation.recurring_amount)
        }
      }

      const listContainer = document.getElementById('lkn-top-last-donations-list')
      listContainer.innerHTML = '' // Clear existing content

      const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']

      groupedDonations.forEach((donation, index) => {
        const listItem = document.createElement('div')
        listItem.className = 'lkn-donation-item'

        const initials = getInitials(donation.billing_first_name, donation.billing_last_name)
        const formattedAmount = parseFloat(donation.recurring_amount).toFixed(2)

        listItem.innerHTML = `
              <div class="lkn-donation-avatar" style="background-color: ${colors[index % colors.length]};">${initials}</div>
              <div class="lkn-donation-info">
                  <span class="lkn-donation-name">${capitalizeName(donation.billing_first_name)} ${capitalizeName(donation.billing_last_name)}</span>
                  <span class="donation-amount">R$ ${formattedAmount}</span>
              </div>
          `

        listContainer.appendChild(listItem)
      })
    }

    function createToggleMenu() {
      const menu = $('<div>', {
        id: 'toggleMenu',
        css: {
          position: 'fixed',
          right: '0',
          top: '0',
          height: '100%',
          width: '0',
          overflow: 'hidden',
          borderLeft: '1px solid #ccc',
          background: '#fff',
          boxShadow: '-2px 0 5px rgba(0,0,0,0.5)',
          zIndex: 9999999,
          transition: 'padding 0.3s'
        }
      })

      const closeButton = $('<button>', {
        id: 'closeMenuButton',
        text: 'X',
        css: {
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          width: '30px',
          height: '30px',
          border: 'none',
          borderRadius: '100%',
          backgroundColor: '#fb6b6b',
          fontWeight: 'bold',
          color: '#fff',
          position: 'absolute',
          top: '10px',
          right: '10px',
          cursor: 'pointer'
        }
      })

      const $selectContainer = $('<div>', {
        css: {
          display: 'flex',
          flexDirection: 'column',
          width: '100%',
          gap: '10px',
          marginTop: '50px'
        }
      })

      // Adiciona os selects
      const currentDate = new Date()
      const currentMonth = String(currentDate.getMonth() + 1).padStart(2, '0')
      const currentYear = currentDate.getFullYear()

      const select1 = $('<div>', { class: 'lkn-select-input' }).append(`
        <label for="month-select">${lknRecurrencyTexts.month_label}</label>
        <select id="month-select">
          <option value="01" ${currentMonth === '01' ? 'selected' : ''}>${lknRecurrencyTexts.january}</option>
          <option value="02" ${currentMonth === '02' ? 'selected' : ''}>${lknRecurrencyTexts.february}</option>
          <option value="03" ${currentMonth === '03' ? 'selected' : ''}>${lknRecurrencyTexts.march}</option>
          <option value="04" ${currentMonth === '04' ? 'selected' : ''}>${lknRecurrencyTexts.april}</option>
          <option value="05" ${currentMonth === '05' ? 'selected' : ''}>${lknRecurrencyTexts.may}</option>
          <option value="06" ${currentMonth === '06' ? 'selected' : ''}>${lknRecurrencyTexts.june}</option>
          <option value="07" ${currentMonth === '07' ? 'selected' : ''}>${lknRecurrencyTexts.july}</option>
          <option value="08" ${currentMonth === '08' ? 'selected' : ''}>${lknRecurrencyTexts.august}</option>
          <option value="09" ${currentMonth === '09' ? 'selected' : ''}>${lknRecurrencyTexts.september}</option>
          <option value="10" ${currentMonth === '10' ? 'selected' : ''}>${lknRecurrencyTexts.october}</option>
          <option value="11" ${currentMonth === '11' ? 'selected' : ''}>${lknRecurrencyTexts.november}</option>
          <option value="12" ${currentMonth === '12' ? 'selected' : ''}>${lknRecurrencyTexts.december}</option>
        </select>
      `)

      const select2 = $('<div>', { class: 'lkn-select-input' }).append(`
        <label for="year-select">${lknRecurrencyTexts.year_label}</label>
        <select id="year-select">
          ${Array.from({ length: 11 }, (_, i) => {
        const year = 2020 + i
        return `<option value="${year}" ${year === currentYear ? 'selected' : ''}>${year}</option>`
      }).join('')}
        </select>
      `)

      const select3 = $('<div>', { class: 'lkn-select-input' }).append(`
        <label for="currency-select">${lknRecurrencyTexts.currency_label}</label>
        <select id="currency-select">
          <option value="BRL">${lknRecurrencyTexts.currency_brl}</option>
        </select>
      `)

      const select4 = $('<div>', { class: 'lkn-select-input' }).append(`
        <label for="mode-select">${lknRecurrencyTexts.payment_mode_label}</label>
        <select id="mode-select">
          <option value="test">${lknRecurrencyTexts.payment_mode_test}</option>
          <option value="live" selected>${lknRecurrencyTexts.payment_mode_production}</option>
        </select>
      `)

      $selectContainer.append(select1, select2, select3, select4)
      menu.append(closeButton, $selectContainer)
      $('body').append(menu)

      // Botão para abrir o menu com animação
      $('#toggleMenuButton').on('click', function () {
        menu.animate(
          {
            width: '300px'
          },
          400
        )
      })

      // Botão para fechar o menu com animação
      closeButton.on('click', function () {
        menu.animate(
          {
            width: '0'
          },
          400,
          function () {
            menu.css('overflow', 'hidden')
          }
        )
      })

      // Fecha o menu ao clicar fora dele
      $(document).on('click', function (event) {
        if (!$(event.target).closest('#toggleMenu, #toggleMenuButton').length) {
          if (parseInt(menu.css('width'), 10) > 0) {
            menu.animate({ width: '0' }, 400, function () {
              menu.css('overflow', 'hidden')
            })
          }
        }
      })
    }

    // Common function to make an AJAX request
    function makeRequest(url, button) {
      $.ajax({
        url,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        data: JSON.stringify({}),
        success: function (response) {
          $('#lkn-update-data').css('display', 'flex')

          if (response.status) {
            $('#lkn-update-data p').text(response.message)

            $('#lkn-update-data').css({
              'border-left': '4px solid rgba(0, 128, 0, 0.7)'
            })

            setTimeout(function () {
              location.reload()
            }, 3000)
          } else {
            $('#lkn-update-data p').text(response.message)

            $('#lkn-update-data').css('border-left', '4px solid rgba(0, 0, 255, 0.7)')

            setTimeout(function () {
              $('#lkn-update-data').remove()
            }, 5000)
          }
        },
        error: function () {
          $('#lkn-update-data').css('display', 'flex')

          $('#lkn-update-data p').text(lknRecurrencyTexts.error_message_update)

          $('#lkn-update-data').css('border-left', '4px solid rgba(255, 0, 0, 0.7)')

          setTimeout(function () {
            $('#lkn-update-data').remove()
          }, 5000)
        }
      })
    }

    // Function to verify the plugin status
    function verifyPluginStatus() {
      $.ajax({
        url: '/wp-json/lkn-recurrency/v1/verify', // New endpoint for verification
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        data: JSON.stringify({}),
        success: function (response) {
          if (response.status) {
            // If status is true, show the update section
            $('#lkn-update-data').css('display', 'flex')

            $('#update-cielo-btn').off('click')

            // Update Cielo data
            $('#update-cielo-btn').on('click', function (e) {
              e.preventDefault()

              const button = $(this)

              // Show confirmation message to the user using the global text
              const confirmBackup = confirm(lknRecurrencyTexts.confirm_backup_message)

              if (confirmBackup) {
                // Disable the button and show loading text
                button.prop('disabled', true).data('original-text', button.text()).text(lknRecurrencyTexts.updating).css({
                  color: '#d3d3d3',
                  cursor: 'not-allowed'
                })

                // Make AJAX request to update Cielo data
                makeRequest('/wp-json/lkn-recurrency/v1/update', button)
              }
            })
          } else {
            // If status is false, hide the update section
            $('#lkn-update-data').remove()
          }
        },
        error: function () {
          // Handle any errors here (optional)
        }
      })
    }
    // Carrega os dados iniciais
    getTab()
  })
})(jQuery)
