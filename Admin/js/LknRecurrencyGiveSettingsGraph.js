(function ($) {
  'use strict'

  $(document).ready(function () {
    const apiUrlBase = lknRecurrencyVars.apiUrlBase
    let chartInstance = null

    function getTab() {
      // Cria o botão e o adiciona à navegação
      const customButton = $('<button>', {
        text: 'Recurrency',
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
                  title="General Review"
                  id="lkn-review-button"
              >
                  <p>General Review</p>
                  <img
                      src="${lknRecurrencyVars.urlWebsite}Includes/assets/icons/review.svg"
                      alt="Review icon"
                  >
              </button>
            `

            $('.givewp-filters').css('justify-content', 'flex-end').empty().append(buttonHtml)

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
          })
        },
        error: function (error) {
          console.log('Erro ao obter o conteúdo:', error.responseJSON.message || error)

          // Cria a nova div de erro, caso não exista
          const errorDiv = $('<div>', {
            class: 'lkn-error-message' // Classe para estilização
          })

          // Adiciona a mensagem de erro dentro de um <p> dentro da div
          const errorMessage = $('<p>').text(error.responseJSON.message || error)
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

      $.getJSON(`${apiUrlBase}&month=${selectedMonth}&year=${selectedYear}&currency=${selectedCurrency}&mode=${selectedMode}`)
        .done(function (responseData) {
          $('#recurrencyChart').show()
          $('#lkn-error-message').hide()

          if (!responseData.success) {
            $('#recurrencyChart').hide()
            $('#lkn-error-message').show().html(responseData.data.message || lknRecurrencyTexts.error_message)
            return
          }

          const data = responseData.data

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
            console.log(data)
            modalSetting(responseData)
            populateTable(responseData)
            renderTopFiveDonorsChart(responseData)
            renderLastFiveDonationsList(responseData)

            const formatMonthlyTotal = `<p>${formatTotal.format(data.total.toFixed(2))}</p>`
            monthlyValue.html(formatMonthlyTotal)

            const formatNextMonthTotal = `<p>${formatTotal.format(data.next_month_total.toFixed(2))}</p>`
            nextMonthValue.html(formatNextMonthTotal)

            const formatAnnualTotal = `<p> ${formatTotal.format(data.annual_total.toFixed(2))}</p>`
            annualValue.html(formatAnnualTotal)

            const donationSummary = data.donations.reduce(
              (accumulator, donation) => {
                // Extrai a data (YYYY-MM-DD) ignorando a hora
                let donationDate = donation.created.split(' ')[0]
                const donationMonth = new Date(donationDate).getMonth() + 1

                if (donationMonth !== parseInt(selectedMonth)) {
                  donationDate = donation.expiration.split(' ')[0]
                }

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

          updateChart(daysOfMonth, numberOfDonationsPerDay, responseData)
        })
        .fail(function (error) {
          $('#recurrencyChart').hide()
          $('#lkn-error-message').show().text(error.message || lknRecurrencyTexts.error_message)
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
            borderColor: 'rgba(52, 59, 69, 1)',
            backgroundColor: 'rgba(211, 216, 0, 0.2)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgba(52, 59, 69, 1)',
            pointBorderColor: 'rgba(52, 59, 69, 1)',
            pointRadius: 6,
            pointHoverRadius: 8
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
      const donationsByDay = responseData.data.donations.filter((donation) => donation.day === customDay)
      content += `<h3>${lknRecurrencyTexts.date_label}: ${responseData.data.date}</h3>`

      if (donationsByDay.length === 0) {
        content = `<strong>${lknRecurrencyTexts.no_data_day}</strong>`
      } else {
        content += `<h4>${lknRecurrencyTexts.day_label}: ${customDay}</h4><ul>`

        donationsByDay.forEach((donation, index, array) => {
          content += `
                  <li>
                      <strong>${lknRecurrencyTexts.donation_id}</strong> ${donation.donation_id} <br>
                      <strong>${lknRecurrencyTexts.user_id}</strong> ${donation.customer_id} <br>
                      <strong>${lknRecurrencyTexts.value}</strong> ${formatTotal.format(donation.recurring_amount)} <br>
                      <strong>${lknRecurrencyTexts.currency}</strong> ${donation.payment_currency} <br>
                      <strong>${lknRecurrencyTexts.name}</strong> ${donation.billing_first_name} ${donation.billing_last_name} <br>
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

        content += '</ul>'
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

          // Loop pelos dias e exibição dos dados
          for (const day in donationsByDay) {
            content += '<div class="lkn-review-data">'
            content += `<h4>${lknRecurrencyTexts.day_label}: ${day}</h4><ul>`
            donationsByDay[day].forEach((donation, index, array) => {
              content += `
                <li>
                  <strong>${lknRecurrencyTexts.donation_id}</strong> ${donation.donation_id} <br>
                  <strong>${lknRecurrencyTexts.user_id}</strong> ${donation.customer_id} <br>
                  <strong>${lknRecurrencyTexts.value}</strong> ${formatTotal.format(donation.recurring_amount)} <br>
                  <strong>${lknRecurrencyTexts.currency}</strong> ${donation.payment_currency} <br>
                  <strong>${lknRecurrencyTexts.name}</strong> ${donation.billing_first_name} ${donation.billing_last_name} <br>
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
                  <strong>${lknRecurrencyTexts.name}</strong> ${donation.billing_first_name} ${donation.billing_last_name} <br>
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
      const topFiveDonations = donations.sort((a, b) => parseFloat(b.recurring_amount) - parseFloat(a.recurring_amount)).slice(0, 5)

      const labels = topFiveDonations.map(donation => `${capitalizeName(donation.billing_first_name)} ${capitalizeName(donation.billing_last_name)}`)
      const data = topFiveDonations.map(donation => parseFloat(donation.recurring_amount))

      const ctx = document.getElementById('top-five-donations-chart').getContext('2d')
      const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{
            data,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
            hoverBackgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          legend: {
            position: 'top'
          },
          title: {
            display: false,
            text: 'Top 5 Donors'
          },
          layout: {
            padding: {
              left: 10,
              right: 10,
              top: 10,
              bottom: 10
            }
          },
          tooltips: {
            callbacks: {
              label: function (tooltipItem, data) {
                const value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index]
                return formatTotal.format(value)
              }
            }
          },
          scales: {
            xAxes: [{
              display: false,
              ticks: {
                max: 400,
                min: 0
              }
            }],
            yAxes: [{
              display: false,
              ticks: {
                max: 400,
                min: 0
              }
            }]
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
      const donations = responseData.data.donations.slice(-5).reverse() // Get the last 5 donations and reverse the order
      const listContainer = document.getElementById('lkn-top-last-donations-list')
      listContainer.innerHTML = '' // Clear existing content

      const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']

      donations.forEach((donation, index) => {
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

    // Carrega os dados iniciais
    getTab()
  })
})(jQuery)
