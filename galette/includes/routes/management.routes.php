<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Management routes
 *
 * PHP version 5
 *
 * Copyright © 2014-2020 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Routes
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2014-2020 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     0.8.2dev 2014-11-11
 */

use Galette\Controllers\GaletteController;
use Galette\Controllers\PluginsController;
use Galette\Controllers\HistoryController;
use Galette\Controllers\DynamicTranslationsController;
use Galette\Controllers\Crud;
use Galette\Controllers\PdfController;
use Galette\Controllers\CsvController;
use Galette\Controllers\AdminToolsController;
use Galette\Controllers\TextController;
use Galette\DynamicFields\DynamicField;

//galette's dashboard
$app->get(
    '/dashboard',
    [GaletteController::class, 'dashboard']
)->setName('dashboard')->add($authenticate);

//preferences page
$app->get(
    '/preferences',
    [GaletteController::class, 'preferences']
)->setName('preferences')->add($authenticate);

//preferences procedure
$app->post(
    '/preferences',
    [GaletteController::class, 'storePreferences']
)->setName('store-preferences')->add($authenticate);

$app->get(
    '/test/email',
    [GaletteController::class, 'testEmail']
)->setName('testEmail')->add($authenticate);

//charts
$app->get(
    '/charts',
    [GaletteController::class, 'charts']
)->setName('charts')->add($authenticate);

//plugins
$app->get(
    '/plugins',
    [PluginsController::class, 'showPlugins']
)->setName('plugins')->add($authenticate);

//plugins (de)activation
$app->get(
    '/plugins/{action:activate|deactivate}/{module_id}',
    [PluginsController::class, 'togglePlugin']
)->setName('pluginsActivation')->add($authenticate);

$app->map(
    ['GET', 'POST'],
    '/plugins/initialize-database/{id}',
    [PluginsController::class, 'initPluginDb']
)->setName('pluginInitDb')->add($authenticate);

//galette logs
$app->get(
    '/logs[/{option:page|order}/{value}]',
    function ($request, $response, $args) use ($routeparser) {
        return $response
            ->withStatus(302)
            ->withHeader(
                'Location',
                $routeparser->urlFor('history', $args)
            );
    }
);
$app->get(
    '/history[/{option:page|order}/{value}]',
    [HistoryController::class, 'list']
)->setName('history')->add($authenticate);

$app->post(
    '/history/filter',
    [HistoryController::class, 'historyFilter']
)->setName('history_filter')->add($authenticate);

$app->get(
    '/logs/flush',
    function ($request, $response) use ($routeparser) {
        return $response
            ->withStatus(302)
            ->withHeader(
                'Location',
                $routeparser->urlFor('flushHistory')
            );
    }
);
$app->get(
    '/history/flush',
    [HistoryController::class, 'confirmHistoryFlush']
)->setName('flushHistory')->add($authenticate);

$app->post(
    '/history/flush',
    [HistoryController::class, 'flushHistory']
)->setName('doFlushHistory')->add($authenticate);

//mailings management
$app->get(
    '/mailings[/{option:page|order|reset}/{value}]',
    [Crud\MailingsController::class, 'list']
)->setName('mailings')->add($authenticate);

$app->post(
    '/mailings/filter',
    [Crud\MailingsController::class, 'filter']
)->setName('mailings_filter')->add($authenticate);

$app->get(
    '/mailings/remove' . '/{id:\d+}',
    [Crud\MailingsController::class, 'confirmDelete']
)->setName('removeMailing')->add($authenticate);

$app->post(
    '/mailings/remove/{id:\d+}',
    [Crud\MailingsController::class, 'delete']
)->setName('doRemoveMailing')->add($authenticate);

//galette exports
$app->get(
    '/export',
    [CsvController::class, 'export']
)->setName('export')->add($authenticate);

$app->get(
    '/{type:export|import}/remove/{file}',
    [CsvController::class, 'confirmRemoveFile']
)->setName('removeCsv')->add($authenticate);

$app->post(
    '/{type:export|import}/remove/{file}',
    [CsvController::class, 'removeFile']
)->setName('doRemoveCsv')->add($authenticate);

$app->post(
    '/export',
    [CsvController::class, 'doExport']
)->setName('doExport')->add($authenticate);

$app->get(
    '/{type:export|import}/get/{file}',
    [CsvController::class, 'getFile']
)->setName('getCsv')->add($authenticate);

$app->get(
    '/import',
    [CsvController::class, 'import']
)->setName('import')->add($authenticate);

$app->post(
    '/import',
    [CsvController::class, 'doImports']
)->setName('doImport')->add($authenticate);

$app->post(
    '/import/upload',
    [CsvController::class, 'uploadImportFile']
)->setname('uploadImportFile')->add($authenticate);

$app->get(
    '/import/model',
    [CsvController::class, 'importModel']
)->setName('importModel')->add($authenticate);

$app->get(
    '/import/model/get',
    [CsvController::class, 'getImportModel']
)->setName('getImportModel')->add($authenticate);

$app->post(
    '/import/model/store',
    [CsvController::class, 'storeModel']
)->setName('storeImportModel')->add($authenticate);

$app->get(
    '/models/pdf[/{id:\d+}]',
    [PdfController::class, 'models']
)->setName('pdfModels')->add($authenticate);

$app->post(
    '/models/pdf',
    [PdfController::class, 'storeModels']
)->setName('pdfModels')->add($authenticate);

$app->get(
    '/titles',
    [Crud\TitlesController::class, 'list']
)->setName('titles')->add($authenticate);

$app->post(
    '/titles',
    [Crud\TitlesController::class, 'doAdd']
)->setName('titles')->add($authenticate);

$app->get(
    '/titles/remove/{id:\d+}',
    [Crud\TitlesController::class, 'confirmDelete']
)->setName('removeTitle')->add($authenticate);

$app->post(
    '/titles/remove/{id:\d+}',
    [Crud\TitlesController::class, 'delete']
)->setName('doRemoveTitle')->add($authenticate);

$app->get(
    '/titles/edit/{id:\d+}',
    [Crud\TitlesController::class, 'edit']
)->setname('editTitle')->add($authenticate);

$app->post(
    '/titles/edit/{id:\d+}',
    [Crud\TitlesController::class, 'doEdit']
)->setname('editTitle')->add($authenticate);

$app->get(
    '/texts[/{lang}/{ref}]',
    [TextController::class, 'list']
)->setName('texts')->add($authenticate);

$app->post(
    '/texts/change',
    [TextController::class, 'change']
)->setName('changeText')->add($authenticate);

$app->post(
    '/texts',
    [TextController::class, 'edit']
)->setName('texts')->add($authenticate);

$app->get(
    '/{class:contributions-types|status}',
    [Crud\EntitledsController::class, 'list']
)->setName('entitleds')->add($authenticate);

$app->get(
    '/{class:contributions-types|status}/edit/{id:\d+}',
    [Crud\EntitledsController::class, 'edit']
)->setName('editEntitled')->add($authenticate);

$app->get(
    '/{class:contributions-types|status}/add',
    [Crud\EntitledsController::class, 'add']
)->setName('addEntitled')->add($authenticate);

$app->post(
    '/{class:contributions-types|status}/edit/{id:\d+}',
    [Crud\EntitledsController::class, 'doEdit']
)->setName('doEditEntitled')->add($authenticate);

$app->post(
    '/{class:contributions-types|status}/add',
    [Crud\EntitledsController::class, 'doAdd']
)->setName('doAddEntitled')->add($authenticate);

$app->get(
    '/{class:contributions-types|status}/remove/{id:\d+}',
    [Crud\EntitledsController::class, 'confirmDelete']
)->setName('removeEntitled')->add($authenticate);

$app->post(
    '/{class:contributions-types|status}/remove/{id:\d+}',
    [Crud\EntitledsController::class, 'delete']
)->setName('doRemoveEntitled')->add($authenticate);

$app->get(
    '/dynamic-translations[/{text_orig}]',
    [DynamicTranslationsController::class, 'dynamicTranslations']
)->setName('dynamicTranslations')->add($authenticate);

$app->post(
    '/dynamic-translations',
    [DynamicTranslationsController::class, 'doDynamicTranslations']
)->setName('editDynamicTranslation')->add($authenticate);

$app->get(
    '/lists/{table}/configure',
    [GaletteController::class, 'configureListFields']
)->setName('configureListFields')->add($authenticate);

$app->post(
    '/lists/{table}/configure',
    [GaletteController::class, 'storeListFields']
)->setName('storeListFields')->add($authenticate);

$app->get(
    '/fields/core/configure',
    [GaletteController::class, 'configureCoreFields']
)->setName('configureCoreFields')->add($authenticate);

$app->post(
    '/fields/core/configure',
    [GaletteController::class, 'storeCoreFieldsConfig']
)->setName('storeCoreFieldsConfig')->add($authenticate);

$app->get(
    '/fields/dynamic/configure[/{form_name:adh|contrib|trans}]',
    [Crud\DynamicFieldsController::class, 'list']
)->setName('configureDynamicFields')->add($authenticate);

$app->get(
    '/fields/dynamic/move/{form_name:adh|contrib|trans}' .
        '/{direction:' . DynamicField::MOVE_UP . '|' . DynamicField::MOVE_DOWN . '}/{id:\d+}',
    [Crud\DynamicFieldsController::class, 'move']
)->setName('moveDynamicField')->add($authenticate);

$app->get(
    '/fields/dynamic/remove/{form_name:adh|contrib|trans}/{id:\d+}',
    [Crud\DynamicFieldsController::class, 'confirmDelete']
)->setName('removeDynamicField')->add($authenticate);

$app->post(
    '/fields/dynamic/remove/{form_name:adh|contrib|trans}/{id:\d+}',
    [Crud\DynamicFieldsController::class, 'delete']
)->setName('doRemoveDynamicField')->add($authenticate);

$app->get(
    '/fields/dynamic/add/{form_name:adh|contrib|trans}',
    [Crud\DynamicFieldsController::class, 'add']
)->setName('addDynamicField')->add($authenticate);

$app->get(
    '/fields/dynamic/edit/{form_name:adh|contrib|trans}/{id:\d+}',
    [Crud\DynamicFieldsController::class, 'edit']
)->setName('editDynamicField')->add($authenticate);

$app->post(
    '/fields/dynamic/add/{form_name:adh|contrib|trans}',
    [Crud\DynamicFieldsController::class, 'doAdd']
)->setName('doAddDynamicField')->add($authenticate);

$app->post(
    '/fields/dynamic/edit/{form_name:adh|contrib|trans}/{id:\d+}',
    [Crud\DynamicFieldsController::class, 'doEdit']
)->setName('doEditDynamicField')->add($authenticate);

$app->get(
    '/admin-tools',
    [AdminToolsController::class, 'adminTools']
)->setName('adminTools')->add($authenticate);

$app->post(
    '/admin-tools',
    [AdminToolsController::class, 'process']
)->setName('doAdminTools')->add($authenticate);

$app->get(
    '/payment-types',
    [Crud\PaymentTypeController::class, 'list']
)->setName('paymentTypes')->add($authenticate);

$app->post(
    '/payment-types',
    [Crud\PaymentTypeController::class, 'doAdd']
)->setName('paymentTypes')->add($authenticate);

$app->get(
    '/payment-type/remove/{id:\d+}',
    [Crud\PaymentTypeController::class, 'confirmDelete']
)->setName('removePaymentType')->add($authenticate);

$app->post(
    '/payment-type/remove/{id:\d+}',
    [Crud\PaymentTypeController::class, 'delete']
)->setName('doRemovePaymentType')->add($authenticate);

$app->get(
    '/payment-type/edit/{id:\d+}',
    [Crud\PaymentTypeController::class, 'edit']
)->setname('editPaymentType')->add($authenticate);

$app->post(
    '/payment-type/edit/{id:\d+}',
    [Crud\PaymentTypeController::class, 'doEdit']
)->setname('editPaymentType')->add($authenticate);

$app->get(
    '/{form_name:adh|contrib|trans}/{id:\d+}/file/{fid:\d+}/{pos:\d+}/{name}',
    [Crud\DynamicFieldsController::class, 'getDynamicFile']
)->setName('getDynamicFile')->add($authenticate);
