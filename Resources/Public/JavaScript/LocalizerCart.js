/**
 * Module: TYPO3/CMS/Localizer/LocalizerCart
 */
define(['jquery', 'bootstrap'], function ($) {
    'use strict';

    /**
     * The localizer cart object
     *
     * @type {{}}
     * @exports TYPO3/CMS/Localizer/LocalizerCart
     */
    var LocalizerCart = {
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
    };

    /**
     * Initialize
     */
    LocalizerCart.initialize = function () {
        var list = $('#recordlist-tx_localizer_cart');
        var localizerRecords = $.parseJSON(localizerRecordInfo);

        $('body').append($('#t3-modal-importscheduled'));

        $('.pagination-wrap', list).closest('tr').remove();
        $('.icon-actions-edit-hide', list).closest('a').remove();
        $('.icon-actions-edit-delete', list).closest('a').remove();
        $('.icon-actions-document-history-open', list).closest('a').remove();
        $('.icon-empty-empty', list).closest('.btn').remove();
        $('tr', list).each(function () {
            LocalizerCart.initializeTableRows($(this), Number($(this).attr('data-uid')), localizerRecords);
        });
        LocalizerCart.initializeImportButtons();
        $('[data-toggle="tooltip"]', '.localizerCarts, .btn-group-import, .btn-group-preview, .btn-group-scheduled').tooltip('show').tooltip('hide');
        LocalizerCart.initializeButtonClicks();
    };

    LocalizerCart.initializeTableRows = function (row, uid, localizerRecords) {
        if (uid) {
            if (localizerRecords[uid]) {
                var record = localizerRecords[uid];
                var firstCell = row.find('td:first');
                if (!firstCell.hasClass('col-title')) {
                    row.find('td:first').remove();
                    firstCell = row.find('td:first');
                }
                var controlCell = row.find('.col-control');
                firstCell.attr('colspan', 1).addClass('localizerCarts').wrapInner('<button type="button" ' +
                    'class="btn btn-' + record.cssClass + '" data-toggle="tooltip" data-placement="top" ' +
                    'data-status="status" ' +
                    'data-title="Toggle all exports">');
                if (LocalizerCart.addExportCounters(record, firstCell)) {
                    LocalizerCart.addImportButtonToCell(controlCell);
                }
                controlCell.wrapInner('<li></li>');
                if ($(record.exportData).length) {
                    $.each(record.exportData, function (exportId, values) {
                        LocalizerCart.addButtonsForExportData(controlCell, exportId, values);
                    });
                }
                controlCell.wrapInner('<ul></ul>');
            } else {
                row.remove();
            }
        }
    };

    LocalizerCart.addExportCounters = function (record, firstCell) {
        var exportCounters = record.exportCounters;
        var showImportButton = false;
        if ($(exportCounters).length) {
            $.each(exportCounters, function (exportStatus, values) {
                if (exportStatus === '70' && values.action === '0') {
                    showImportButton = true;
                }
                firstCell.append(' <button type="button" ' +
                    'class="btn btn-' + values.cssClass + '" data-toggle="tooltip" data-placement="top" ' +
                    'data-status="' + exportStatus + '" ' +
                    'data-title="Toggle ' + values.counter + ' x ' + values.label + '">' + values.counter + '</button>');
            });
        }
        firstCell.wrapInner('<li></li>');
        LocalizerCart.addExportData(record, firstCell);
        return showImportButton;
    };

    LocalizerCart.addExportData = function (record, firstCell) {
        $.each(record.exportData, function (exportId, values) {
            firstCell.append('<li class="toggle-status toggle-' + values.status + '"><button type="button" ' +
                'class="btn btn-' + values.cssClass + '" data-toggle="tooltip" data-placement="top" ' +
                'data-uid="' + exportId + '" ' +
                'data-title="' + values.label + '">' + values.filename + ' [' + values.label + ']</button></li>');
        });
        firstCell.wrapInner('<ul></ul>');
    };

    LocalizerCart.addImportButtonToCell = function (cell) {
        cell.prepend('<div class="btn-group btn-group-import" role="group">' +
            '<a href="#" class="btn btn-warning" ' +
            'data-toggle="tooltip" data-placement="right" ' +
            'data-title="Import all returned files"' +
            'onclick=""' +
            'data-uid="">' +
            '<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-upload" data-identifier="actions-upload">' +
            '<span class="icon-markup">' +
            '<img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-upload.svg" width="16" height="16">' +
            '</span></span></a>' +
            '</div> ');
    };

    LocalizerCart.addButtonsForExportData = function (cell, id, values) {
        var editOnClick = 'window.location.href=\'' + top.TYPO3.settings.FormEngine.moduleUrl + '&edit[tx_localizer_settings_l10n_exportdata_mm][' + id + ']=edit&returnUrl=\'+T3_THIS_LOCATION; return false;';
        editOnClick = editOnClick.replace(/\//g, "\\/");
        editOnClick = editOnClick.replace(/&/g, "\\u0026");
        var previewOnClick = '';
        var previewTooltip = '';
        if (values.status === '70') {
            //previewOnClick = "window.open('/uploads/tx_l10nmgr/jobs/in/" + values.locale + "/" + values.filename + "', 'Import File', 'width=1024,height=768'); window.open('/uploads/tx_l10nmgr/jobs/out/" + values.filename + "', 'Export File', 'width=1024,height=768'); return false;";
            previewOnClick = "window.open('/uploads/tx_l10nmgr/jobs/in/" + values.filename + "', 'Import File', 'width=1024,height=768'); window.open('/uploads/tx_l10nmgr/jobs/out/" + values.filename + "', 'Export File', 'width=1024,height=768'); return false;";
            previewTooltip = 'Click twice to preview both files';
        } else {
            previewOnClick = "window.open('/uploads/tx_l10nmgr/jobs/out/" + values.filename + "', 'Export File', 'width=1024,height=768'); return false;";
            previewTooltip = 'Preview this file';
        }
        cell.append('<li class="toggle-status toggle-' + values.status + '">' +
            (values.status === '70' && values.action === '0' ? (
                '<div class="btn-group btn-group-import" role="group">' +
                '<a href="#" class="btn btn-warning" ' +
                'data-toggle="tooltip" data-placement="top" ' +
                'data-title="Import this file"' +
                'data-uid="' + id + '">' +
                '<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-upload" data-identifier="actions-upload">' +
                '<span class="icon-markup">' +
                '<img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-upload.svg" width="16" height="16">' +
                '</span></span></a>'
            ) : '') +
            (values.status !== '80' && values.action !== '70' ? (
                '</div> <div class="btn-group btn-group-preview" role="group">' +
                '<a href="#" class="btn btn-info" ' +
                'onclick="' + previewOnClick + '"' +
                'data-toggle="tooltip" data-placement="top" ' +
                'data-title="' + previewTooltip + '"' +
                'data-uid="' + id + '">' +
                '<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-document-view" data-identifier="actions-document-view">' +
                '<span class="icon-markup">' +
                '<img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-document-view.svg" width="16" height="16">' +
                '</span></span></a>' +
                '</div> <div class="btn-group btn-group-edit" role="group">' +
                '<a href="#" class="btn btn-default" ' +
                'onclick="' + editOnClick + '"' +
                'data-uid="' + id + '">' +
                '<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-open" data-identifier="actions-open">' +
                '<span class="icon-markup">' +
                '<img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-open.svg" width="16" height="16">' +
                '</span></span></a>'
            ) : '') +
            (values.action === '70' ? (
                '</div> <div class="btn-group btn-group-scheduled" role="group">' +
                '<a href="#" class="btn btn-success" ' +
                'data-toggle="tooltip" data-placement="top" ' +
                'data-title="Scheduled for import"' +
                'data-uid="' + id + '">' +
                '<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-document-history-open" data-identifier="actions-document-history-open">' +
                '<span class="icon-markup">' +
                '<img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-document-history-open.svg" width="16" height="16">' +
                '</span></span></a>'
            ) : '') +
            '</div> <div class="btn-group" role="group">' +
            '<a href="#" class="btn btn-default" ' +
            'onclick="top.TYPO3.InfoWindow.showItem(\'tx_localizer_settings_l10n_exportdata_mm\', ' + id + '); return false;"' +
            'data-uid="' + id + '">' +
            '<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-document-info" data-identifier="actions-document-info">' +
            '<span class="icon-markup">' +
            '<img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-document-info.svg" width="16" height="16">' +
            '</span></span></a>' +
            '</div>' +
            '</li>');
    };

    LocalizerCart.initializeImportButtons = function () {
        $('.btn-group-import a').click(function () {
            var uid = $(this).data('uid');
            if (uid) {
                LocalizerCart.setSingleRecordImportAction(uid, $(this));
            } else {
                $(this).closest('td').find('.btn-group-import a').each(function () {
                    var singleUid = $(this).data('uid');
                    if (singleUid) {
                        LocalizerCart.setSingleRecordImportAction(singleUid, $(this));
                    }
                });
            }
            $('#t3-modal-importscheduled').modal();
        });
    };

    LocalizerCart.setSingleRecordImportAction = function (uid, importButton) {
        var importOnClick = top.TYPO3.settings.FormEngine.moduleUrl + '&edit[tx_localizer_settings_l10n_exportdata_mm][' + uid + ']=edit&data[tx_localizer_settings_l10n_exportdata_mm][' + uid + '][action]=70&doSave=1&closeDoc=-1';
        $.get(importOnClick, function () {
            var li = importButton.closest('li');
            li.find('.btn-group-import, .btn-group-preview, .btn-group-edit').remove();
            li.prepend('<div class="btn-group btn-group-scheduled" role="group">' +
                '<a href="#" class="btn btn-success" ' +
                'data-toggle="tooltip" data-placement="right" ' +
                'data-title="Scheduled for import">' +
                '<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-document-history-open" data-identifier="actions-document-history-open">' +
                '<span class="icon-markup">' +
                '<img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-document-history-open.svg" width="16" height="16">' +
                '</span></span></a>' +
                '</div> ');
            var ul = li.closest('ul');
            if (!ul.find('.toggle-status .btn-group-import').length) {
                ul.find('.btn-group-import').remove();
            }
        });
    };

    LocalizerCart.initializeButtonClicks = function () {
        $('.localizerCarts button').click(function () {
            $('[data-toggle="tooltip"]').tooltip('hide');
            var tr = $(this).closest('tr');
            if ($(this).hasClass('clicked')) {
                tr.find('button').removeClass('clicked');
                tr.removeClass('expanded');
            } else {
                tr.find('button').removeClass('clicked');
                $(this).addClass('clicked');
                tr.addClass('expanded');
            }
            tr.find('.toggle-status').removeClass('explicitely-hide').not('.toggle-' + $(this).data('status')).addClass('explicitely-hide');
        });
    };

    $(function () {
        LocalizerCart.initialize();
    });

    // expose as global object
    TYPO3.LocalizerCart = LocalizerCart;

    return LocalizerCart;
});
