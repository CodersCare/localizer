/**
 * Module: TYPO3/CMS/Localizer/LocalizerCart
 */
define([
  'jquery',
  'TYPO3/CMS/Backend/AjaxDataHandler',
  'TYPO3/CMS/Backend/Icons',
  'bootstrap'
], function ($, AjaxDataHandler, Icons) {
  'use strict'

  /**
   * The localizer cart object
   *
   * @type {{}}
   * @exports TYPO3/CMS/Localizer/LocalizerCart
   */
  var LocalizerCart = {
    icons: {},
    initialize: function () {
    },
    initializeTableRows: function () {
    },
    addExportCounters: function () {
    },
    addExportData: function () {
    },
    addImportButtonToCell: function () {
    },
    addButtonsForExportData: function () {
    },
    initializeImportButtons: function () {
    },
    setSingleRecordImportAction: function () {
    },
    initializeButtonClicks: function () {
    }
  }

  /**
   * Initialize
   */
  LocalizerCart.initialize = function () {
    const iconPromises = [];
    iconPromises.push(Icons.getIcon('actions-upload', Icons.sizes.small));
    iconPromises.push(Icons.getIcon('actions-document-view', Icons.sizes.small));
    iconPromises.push(Icons.getIcon('actions-open', Icons.sizes.small));
    iconPromises.push(Icons.getIcon('actions-history', Icons.sizes.small));
    iconPromises.push(Icons.getIcon('actions-info', Icons.sizes.small));
    Promise.all(iconPromises).then((icons) => {
      this.icons['actions-upload'] = icons[0];
      this.icons['actions-document-view'] = icons[1];
      this.icons['actions-open'] = icons[2];
      this.icons['actions-history'] = icons[3];
      this.icons['actions-info'] = icons[4];

      var list = $('#recordlist-tx_localizer_cart');
      var localizerRecords = JSON.parse(document.querySelector('#dataRecordInfo').dataset.recordInfoJson);

      $('body').append($('#t3-modal-importscheduled'))

      $('.pagination-wrap', list).closest('tr').remove()
      $('.icon-actions-edit-hide', list).closest('a').remove()
      $('.icon-actions-edit-delete', list).closest('a').remove()
      $('.icon-actions-document-history-open', list).closest('a').remove()
      $('.icon-empty-empty', list).closest('.btn').remove()
      $('tr', list).each(function () {
        LocalizerCart.initializeTableRows($(this), Number($(this).attr('data-uid')), localizerRecords)
      })
      LocalizerCart.initializeImportButtons()
      $('[data-toggle="tooltip"]', '.localizerCarts, .btn-group-import, .btn-group-preview, .btn-group-scheduled').tooltip('show').tooltip('hide')
      LocalizerCart.initializeButtonClicks()
    });
  }

  LocalizerCart.initializeTableRows = function (row, uid, localizerRecords) {
    if (uid) {
      if (localizerRecords[uid]) {
        var record = localizerRecords[uid]
        var firstCell = row.find('td:first')
        if (!firstCell.hasClass('col-title')) {
          row.find('td:first').remove()
          firstCell = row.find('td:first')
        }
        var controlCell = row.find('.col-control')
        firstCell.attr('colspan', 1).addClass('localizerCarts').wrapInner('<button type="button" ' +
          'class="btn btn-' + record.cssClass + '" data-bs-toggle="tooltip"  data-toggle="tooltip" data-placement="top" ' +
          'data-status="status" ' +
          ' title="Toggle all exports">')
        if (LocalizerCart.addExportCounters(record, firstCell)) {
          LocalizerCart.addImportButtonToCell(controlCell)
        }
        controlCell.wrapInner('<li></li>')
        if ($(record.exportData).length) {
          $.each(record.exportData, function (exportId, values) {
            LocalizerCart.addButtonsForExportData(controlCell, exportId, values)
          })
        }
        controlCell.wrapInner('<ul></ul>')
      } else {
        row.remove()
      }
    }
  }

  LocalizerCart.addExportCounters = function (record, firstCell) {
    var exportCounters = record.exportCounters
    var showImportButton = false
    if ($(exportCounters).length) {
      $.each(exportCounters, function (exportStatus, values) {
        if (exportStatus === '70' && values.action === 0) {
          showImportButton = true
        }
        firstCell.append(' <button type="button" ' +
          'class="btn btn-' + values.cssClass + '" data-bs-toggle="tooltip"  data-toggle="tooltip" data-placement="top" ' +
          'data-status="' + exportStatus + '" ' +
          ' title="Toggle ' + values.counter + ' x ' + values.label + '">' + values.counter + '</button>')
      })
    }
    firstCell.wrapInner('<li></li>')
    LocalizerCart.addExportData(record, firstCell)
    return showImportButton
  }

  LocalizerCart.addExportData = function (record, firstCell) {
    $.each(record.exportData, function (exportId, values) {
      firstCell.append('<li class="toggle-status toggle-' + values.status + ' action1-' + values.action + '"><button type="button" ' +
        'class="btn btn-' + values.cssClass + '" data-bs-toggle="tooltip"  data-toggle="tooltip" data-placement="top" ' +
        'data-uid="' + exportId + '" ' +
        ' title="' + values.label + '">' + values.filename + ' [' + values.label + ']</button></li>')
    })
    firstCell.wrapInner('<ul></ul>')
  }

  LocalizerCart.addImportButtonToCell = function (cell) {
    cell.prepend('' +
      '<div class="btn-group btn-group-import" role="group">' +
        '<a href="#" class="btn btn-warning" data-toggle="tooltip" data-placement="right" title="Import all returned files" onclick="" data-uid="">' + this.icons['actions-upload'] + '</a>' +
      '</div>');
  }

  LocalizerCart.addButtonsForExportData = function (cell, id, values) {
    var editOnClick = 'window.location.href=\'' + top.TYPO3.settings.FormEngine.moduleUrl + '&edit[tx_localizer_settings_l10n_exportdata_mm][' + id + ']=edit&returnUrl=\'+T3_THIS_LOCATION; return false;'
    editOnClick = editOnClick.replace(/\//g, '\\/')
    editOnClick = editOnClick.replace(/&/g, '\\u0026')
    var previewOnClick = ''
    var previewTooltip = ''
    if (values.status === 70) {
      previewOnClick = 'window.open(\'/uploads/tx_l10nmgr/jobs/in/' + values.locale + '/' + values.filename + '\', \'Import File\', \'width=1024,height=768\'); window.open(\'/uploads/tx_l10nmgr/jobs/out/' + values.filename + '\', \'Export File\', \'width=1024,height=768\'); return false;'
      previewTooltip = 'Click twice to preview both files'
    } else {
      previewOnClick = 'window.open(\'/uploads/tx_l10nmgr/jobs/out/' + values.filename + '\', \'Export File\', \'width=1024,height=768\'); return false;'
      previewTooltip = 'Preview this file'
    }
    let appendString = '<li class="toggle-status toggle-' + values.status + ' action2-' + values.action + '">';
    if (values.status === 70 && values.action === 0) {
      appendString +=
        '<div class="btn-group btn-group-import" role="group">' +
          '<a href="#" class="btn btn-warning" data-toggle="tooltip" data-placement="top" title="Import this file" data-uid="' + id + '">' +
          this.icons['actions-upload'] +
          '</a>' +
        '</div>';
    }
    if (values.status !== 90 && values.status !== 80 && values.action !== 70) {
      appendString +=
        '<div class="btn-group btn-group-preview" role="group">' +
          '<a href="#" class="btn btn-info" onclick="' + previewOnClick + '" data-toggle="tooltip" data-placement="top" title="' + previewTooltip + '" data-uid="' + id + '">' +
            this.icons['actions-document-view'] +
          '</a>' +
        '</div>' +
        '<div class="btn-group btn-group-edit" role="group">' +
          '<a href="#" class="btn btn-default" onclick="' + editOnClick + '"' + ' data-uid="' + id + '">' +
            this.icons['actions-open'] +
          '</a>' +
        '</div>';
    }

    if (values.action === 70) {
      appendString +=
        '<div class="btn-group btn-group-scheduled" role="group">' +
          '<a href="#" class="btn btn-success" data-toggle="tooltip" data-placement="top" title="Scheduled for import" data-uid="' + id + '">' +
            this.icons['actions-history'] +
          '</a>' +
        '</div>';
    }

    appendString +=
      '<div class="btn-group" role="group">' +
        '<a href="#" class="btn btn-default" onclick="top.TYPO3.InfoWindow.showItem(\'tx_localizer_settings_l10n_exportdata_mm\', ' + id + '); return false;" data-uid="' + id + '">' +
          this.icons['actions-info'] +
        '</a>' +
      '</div>';

    appendString += '</li>';
    cell.append(appendString);
  }

  LocalizerCart.initializeImportButtons = function () {
    $('.btn-group-import a').click(function () {
      var uid = $(this).data('uid')
      if (uid) {
        LocalizerCart.setSingleRecordImportAction(uid, $(this))
      } else {
        $(this).closest('td').find('.btn-group-import a').each(function () {
          var singleUid = $(this).data('uid')
          if (singleUid) {
            LocalizerCart.setSingleRecordImportAction(singleUid, $(this))
          }
        })
      }
      $('#t3-modal-importscheduled').modal()
    })
  }

  LocalizerCart.setSingleRecordImportAction = function (uid, importButton) {
    AjaxDataHandler.process('data[tx_localizer_settings_l10n_exportdata_mm][' + uid + '][action]=70').then(() => {
      var li = importButton.closest('li')
      li.find('.btn-group-import, .btn-group-preview, .btn-group-edit').remove()
      li.prepend('<div class="btn-group btn-group-scheduled" role="group">' +
        '<a href="#" class="btn btn-success" ' +
        'data-toggle="tooltip" data-placement="right" ' +
        ' title="Scheduled for import">' +
        '<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-document-history-open" data-identifier="actions-document-history-open">' +
        '<span class="icon-markup"><svg class="icon-color" role="img">' +
        '<use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-history"></use>' +
        '</svg></span>' +
        '</span></a>' +
        '</div> ')
      var ul = li.closest('ul')
      if (!ul.find('.toggle-status .btn-group-import').length) {
        ul.find('.btn-group-import').remove()
      }
    });
  }

  LocalizerCart.initializeButtonClicks = function () {
    $('.localizerCarts button').click(function () {
      $('[data-toggle="tooltip"]').tooltip('hide')
      var tr = $(this).closest('tr')
      if ($(this).hasClass('clicked')) {
        tr.find('button').removeClass('clicked')
        tr.removeClass('expanded')
      } else {
        tr.find('button').removeClass('clicked')
        $(this).addClass('clicked')
        tr.addClass('expanded')
      }
      tr.find('.toggle-status').removeClass('explicitely-hide').not('.toggle-' + $(this).data('status')).addClass('explicitely-hide')
    })
  }

  $(function () {
    LocalizerCart.initialize()
  })

  // expose as global object
  TYPO3.LocalizerCart = LocalizerCart

  return LocalizerCart
})
