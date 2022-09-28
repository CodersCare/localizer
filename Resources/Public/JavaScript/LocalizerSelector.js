/**
 * Module: TYPO3/CMS/Localizer/LocalizerSelector
 */
define(['jquery', 'bootstrap'], function ($) {
  'use strict'

  /**
   * The localizer selector object
   *
   * @type {{}}
   * @exports TYPO3/CMS/Localizer/LocalizerSelector
   */
  var LocalizerSelector = {
    initialize: function () {
    }
  }

  /**
   * Initialize
   */
  LocalizerSelector.initialize = function () {
    $('body').append($('#t3-modal-finalizecart'))
    $('#finalize-cart-submit').click(function (e) {
      e.preventDefault()
      $(this).off('click').attr('href', 'javascript: void(0);')
      $('#localizer_selector [name="selected_deadline"]').val($('#input-configured-configured_deadline').val())
      $('#configuratorFinalize').val('finalize')
      $('#localizer_selector').submit()
    })
    $('.localizer-matrix-configurator .dropdown-menu li .small').click(function () {
      $(this).find('input').prop('checked', !$(this).find('input').prop('checked'))
      return false
    })
    $('.localizer-matrix-configurator .dropdown-menu li.select-all .small').click(function () {
      $(this).closest('ul').find('input:enabled').prop('checked', $(this).find('input').prop('checked'))
      return false
    })
    $('.localizer-matrix-configurator .dropdown-menu li .small input').click(function (event) {
      event.stopPropagation()
    })
    $('.localizer-selector-matrix .language-header .btn').click(function (event) {
      event.stopPropagation()
      event.preventDefault()
      var children = null
      $(this).toggleClass('active')
      if ($(this).hasClass('active')) {
        children = $('.language-record-marker:nth-child(' + ($(this).closest('.language-header').index() + 1) + ') .btn').not('.active')
        children.click()
        children.find('input').prop('checked', true)
      } else {
        children = $('.language-record-marker:nth-child(' + ($(this).closest('.language-header').index() + 1) + ') .btn.active')
        children.click()
        children.find('input').prop('checked', false)
      }
      $(this).focus().blur()
      $('[data-toggle="tooltip"]').tooltip('hide')
    })
    $('.localizer-selector-matrix .language-header').click(function (event) {
      event.stopPropagation()
      $(this).find('.btn').click()
      $('[data-toggle="tooltip"]').tooltip('hide')
    })
    $('.localizer-selector-matrix .record-header .btn').click(function (event) {
      event.stopPropagation()
      event.preventDefault()
      $(this).toggleClass('active')
      var relatedRecords = $('.parent-' + $(this).data('tableid'))
      var children = null
      if ($(this).hasClass('active')) {
        relatedRecords.find('.record-header .btn').addClass('active')
        children = relatedRecords.find('.language-record-marker .btn').not('.active')
        children.click()
        children.find('input').prop('checked', true)
      } else {
        relatedRecords.find('.record-header .btn').removeClass('active')
        children = relatedRecords.find('.language-record-marker .btn.active')
        children.click()
        children.find('input').prop('checked', false)
      }
      $(this).focus().blur()
      $('[data-toggle="tooltip"]').tooltip('hide')
    })
    var recordHeader = $('.localizer-selector-matrix .record-header')
    recordHeader.click(function (event) {
      event.stopPropagation()
      $(this).find('.btn').click()
      $('[data-toggle="tooltip"]').tooltip('hide')
    })
    $('.localizer-selector-matrix .language-record-marker .btn').mouseup(function (event) {
      event.preventDefault()
      var children = null
      if ($(this).hasClass('active')) {
        children = $('.parent-' + $(this).closest('tr').find('.record-header .btn').data('tableid')).find('.language-record-marker:nth-child(' + ($(this).closest('.language-record-marker').index() + 1) + ') .btn.active').not(this)
        children.click()
        children.find('input').prop('checked', false)
      } else {
        children = $('.parent-' + $(this).closest('tr').find('.record-header .btn').data('tableid')).find('.language-record-marker:nth-child(' + ($(this).closest('.language-record-marker').index() + 1) + ') .btn').not('.active').not(this)
        children.click()
        children.find('input').prop('checked', true)
      }
    })
    $('.localizer-selector-matrix .column-hover').hover(
      function () {
        $('.language-record-marker:nth-child(' + ($(this).index() + 1) + ')').addClass('hover')
      },
      function () {
        $('[data-toggle="tooltip"]').tooltip('hide')
        $('.language-record-marker').removeClass('hover')
      }
    )
    recordHeader.hover(
      function () {
        $('.language-record-marker').removeClass('hover')
      },
      function () {
        $('[data-toggle="tooltip"]').tooltip('hide')
      }
    )
    $('.localizer-selector-matrix, .localizer-selector-matrix table').hover(
      function () {
      },
      function () {
        $('[data-toggle="tooltip"]').tooltip('hide')
        $('.language-record-marker').removeClass('hover')
      }
    )
    if ($('.t3js-clearable').length) {
      require(['TYPO3/CMS/Backend/Input/Clearable'], function () {
        $('.t3js-clearable').clearable()
      })
    }
  }

  $(function () {
    LocalizerSelector.initialize()
  })

  // expose as global object
  TYPO3.LocalizerSelector = LocalizerSelector

  return LocalizerSelector
})
