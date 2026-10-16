<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'update' && !empty($_REQUEST['company_id'])) {
    $company_id = (int) $_REQUEST['company_id'];

    Tygh::$app['view']->assign(
        'is2or_document_verifications',
        fn_is2or_ai_integration_get_company_document_verifications($company_id)
    );
}