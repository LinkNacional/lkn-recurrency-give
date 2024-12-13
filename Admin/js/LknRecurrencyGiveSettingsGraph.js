(function ($) {
  'use strict'

  $(document).ready(function () {
    const apiUrlBase = lknRecurrencyVars.apiUrlBase
    const monthSelect = $('#month-select')
    const yearSelect = $('#year-select')
    const currencySelect = $('#currency-select')
    const modeSelect = $('#mode-select')
    let chartInstance = null

    function showLoading() {
      $('#lkn-recurrency-loading').fadeIn()
    }

    // Oculta o loading screen após o carregamento
    function hideLoading() {
      $('#lkn-recurrency-loading').fadeOut()
    }

    function fetchDataAndRenderChart() {
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

          const data = responseData.data[`${selectedYear}-${selectedMonth}`]
          const valueContainer = $('#lkn-value')

          let labels = []
          let groupedTotals = []

          const currentDate = new Date()
          const lastDay = new Date(selectedYear, selectedMonth, 0)

          // Função para formatar a data no formato 'yyyy-MM-dd HH:mm:ss'
          const formatDate = (date) => {
            const year = date.getFullYear()
            const month = String(date.getMonth() + 1).padStart(2, '0')
            const day = String(date.getDate()).padStart(2, '0')
            return `${year}-${month}-${day}`
          }

          // Formatar as datas
          let formattedLastDay = formatDate(lastDay)
          const formattedCurrentDate = formatDate(currentDate)

          let formatTotal = formatCurrency(selectedCurrency)
          if (data) {
            modalSetting(responseData)

            formatTotal = `<span>${formatTotal.format(data.total.toFixed(2))}</span>`
            valueContainer.html(formatTotal)

            const result = data.donations.reduce(
              (acc, item) => {
                // Extrai a data (YYYY-MM-DD) ignorando a hora
                const date = item.completed_date.split(' ')[0]

                // Adiciona a data ao labels, se não existir
                if (!acc.labelsArray.includes(date)) {
                  acc.labelsArray.push(date)
                }

                // Acumula o valor no groupedTotalsObj para o dia correspondente
                if (!acc.groupedTotalsObj[date]) {
                  acc.groupedTotalsObj[date] = 0
                }
                acc.groupedTotalsObj[date] += 1

                return acc
              },
              { labelsArray: [], groupedTotalsObj: {} }
            )

            const { labelsArray, groupedTotalsObj } = result

            groupedTotals = Object.values(groupedTotalsObj)
            labels = labelsArray
          } else {
            formatTotal = `<span>${formatTotal.format(0)}</span>`
            valueContainer.html(formatTotal)
          }

          if (formattedLastDay > formattedCurrentDate) {
            formattedLastDay = formattedCurrentDate
          }

          const labelfull = [] // Lista de todas as datas no mês
          const formattedLastDayDate = new Date(formattedLastDay + 'T23:59:59')

          // Loop para gerar todas as datas do mês até o formattedLastDay
          for (let d = new Date(selectedYear, selectedMonth - 1, 1); d <= formattedLastDayDate; d.setDate(d.getDate() + 1)) {
            labelfull.push(d.toISOString().split('T')[0])
          }

          // Mapear os valores de doações, associando valores a cada data ou null se não houver doação
          const datafull = labelfull.map(label => {
            const index = labels.indexOf(label)
            return index !== -1 ? groupedTotals[index] : 0
          })

          updateChart(labelfull, datafull, responseData)
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
      const selectedCurrency = currencySelect.val()
      let dateFomat = 'yyyy-MM-dd'
      const ctx = document.getElementById('recurrencyChart').getContext('2d')

      if (selectedCurrency === 'BRL') {
        dateFomat = 'dd/MM/yyyy'
      }

      if (chartInstance) {
        chartInstance.destroy()
      }

      chartInstance = new Chart(ctx, {
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
              // Alterar o cursor para pointer quando o mouse está sobre um ponto
              chartCanvas.style.cursor = 'pointer'
            } else {
              // Voltar o cursor para o padrão
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

      // Renderizar os dados no modal
      let content = ''

      for (const date in responseData.data) {
        const donationsByDay = {}
        if (responseData.data[date].donations.length > 0) {
          responseData.data[date].donations.forEach((donation) => {
            // Extrair apenas a data (DD)
            const customday = clickedDate.split(' ')[0].split('-')[2]
            const day = donation.completed_date.split(' ')[0].split('-')[2]

            if (customday === day) {
              if (!donationsByDay[customday]) donationsByDay[customday] = []
              donationsByDay[customday].push(donation)
            }
          })

          if (Object.keys(donationsByDay).length === 0) {
            continue
          }

          content += '<div class="lkn-review-data">'
          content += `<h3>${lknRecurrencyTexts.date_label}: ${date}</h3>`
          for (const customday in donationsByDay) {
            content += `<h4>${lknRecurrencyTexts.date_label}: ${customday}</h4><ul>`
            donationsByDay[customday].forEach((donation, index, array) => {
              content += `
                <li>
                  <strong>${lknRecurrencyTexts.donation_id}</strong> ${donation.donation_id} <br>
                  <strong>${lknRecurrencyTexts.user_id}</strong> ${donation.user_id} <br>
                  <strong>${lknRecurrencyTexts.value}</strong> ${donation.total} <br>
                  <strong>${lknRecurrencyTexts.currency}</strong> ${donation.currency} <br>
                  <strong>${lknRecurrencyTexts.name}</strong> ${donation.first_name} ${donation.last_name} <br>
                  <strong>${lknRecurrencyTexts.email}</strong> ${donation.email} <br>
                  <strong>${lknRecurrencyTexts.payment_mode}</strong> ${donation.payment_mode} <br>
                  <strong>${lknRecurrencyTexts.completion_date}</strong> ${donation.completed_date} <br>
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
          content = `<strong>${lknRecurrencyTexts.no_data_day}</strong>`
          break
        }
      }

      if (content === '') {
        // Não encontrado
        content = `<strong>${lknRecurrencyTexts.no_data_day}</strong>`
      }

      modalContent.html(content)
      modal.fadeIn()
    }

    function modalSetting(responseData) {
      // Abrir modal
      $('#lkn-review-button').on('click', function () {
        const modal = $('#lkn-review-modal')
        const modalContent = $('#lkn-modal-content')
        modal.fadeIn()

        // Renderizar os dados no modal
        let content = ''

        for (const date in responseData.data) {
          const donationsByDay = {}
          if (responseData.data[date].donations.length > 0) {
            responseData.data[date].donations.forEach((donation) => {
              // Extrair apenas a data (DD)
              const day = donation.completed_date.split(' ')[0].split('-')[2]

              if (!donationsByDay[day]) donationsByDay[day] = []
              donationsByDay[day].push(donation)
            })

            content += '<div class="lkn-review-data">'
            content += `<h3>${lknRecurrencyTexts.date_label}: ${date}</h3>`
            for (const day in donationsByDay) {
              content += `<h4>${lknRecurrencyTexts.day_label}: ${day}</h4><ul>`
              donationsByDay[day].forEach((donation, index, array) => {
                content += `
                  <li>
                    <strong>${lknRecurrencyTexts.donation_id}</strong> ${donation.donation_id} <br>
                    <strong>${lknRecurrencyTexts.user_id}</strong> ${donation.user_id} <br>
                    <strong>${lknRecurrencyTexts.value}</strong> ${donation.total} <br>
                    <strong>${lknRecurrencyTexts.currency}</strong> ${donation.currency} <br>
                    <strong>${lknRecurrencyTexts.name}</strong> ${donation.first_name} ${donation.last_name} <br>
                    <strong>${lknRecurrencyTexts.email}</strong> ${donation.email} <br>
                    <strong>${lknRecurrencyTexts.payment_mode}</strong> ${donation.payment_mode} <br>
                    <strong>${lknRecurrencyTexts.completion_date}</strong> ${donation.completed_date} <br>
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
            break
          }
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

    // Event listeners para os selects
    monthSelect.on('change', fetchDataAndRenderChart)
    yearSelect.on('change', fetchDataAndRenderChart)
    currencySelect.on('change', fetchDataAndRenderChart)
    modeSelect.on('change', fetchDataAndRenderChart)

    // Carrega os dados iniciais
    fetchDataAndRenderChart()
  })
})(jQuery)
