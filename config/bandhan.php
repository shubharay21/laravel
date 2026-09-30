<?php

return [
    'TriggeredByUserId' => env('BANDHAN_TRIGGERED_BY_USER_ID', '256136'),
    'ApplicationId'     => env('BANDHAN_APPLICATION_ID', '101'),
    'EncryptionKey'     => env('BANDHAN_ENCRYPTION_KEY', 'b8832e5779ba07289d9e384ea82f774d'),
    'IvData'            => env('BANDHAN_IV_DATA', 'LBNIC@Bandhan826'),
    'ApiEndpoint'       => env('BANDHAN_API_ENDPOINT', null),

    // ─────────────────────────────────────────────────────────────────
    // CURRENT ACTION IDs (Annapurna Scheme - Active)
    // ─────────────────────────────────────────────────────────────────
    'current_actions' => [
        'LotValidationUploadActionId'     => env('BANDHAN_CURRENT_LOT_VALIDATION_UPLOAD_ACTION_ID', '3713'),
        'LotValidationInfoActionId'       => env('BANDHAN_CURRENT_LOT_VALIDATION_INFO_ACTION_ID', '3715'),
        'LotValidationResponseActionId'   => env('BANDHAN_CURRENT_LOT_VALIDATION_RESPONSE_ACTION_ID', '3716'),
        'LotTransactionUploadActionId'    => env('BANDHAN_CURRENT_LOT_TRANSACTION_UPLOAD_ACTION_ID', '3717'),
        'LotTransactionInfoActionId'      => env('BANDHAN_CURRENT_LOT_TRANSACTION_INFO_ACTION_ID', '3718'),
        'LotTransactionResponseActionId'  => env('BANDHAN_CURRENT_LOT_TRANSACTION_RESPONSE_ACTION_ID', '3719'),
    ],

    // Active Action IDs used across the application (uses Current)
    'LotValidationUploadActionId'    => env('BANDHAN_LOT_VALIDATION_UPLOAD_ACTION_ID', '3713'),
    'LotValidationActionId'          => env('BANDHAN_LOT_VALIDATION_ACTION_ID', '3713'),
    'LotValidationInfoActionId'      => env('BANDHAN_LOT_VALIDATION_INFO_ACTION_ID', '3715'),
    'LotValidationDetActionId'       => env('BANDHAN_LOT_VALIDATION_DET_ACTION_ID', '3716'),
    'LotValidationResponseActionId'  => env('BANDHAN_LOT_VALIDATION_RESPONSE_ACTION_ID', '3716'),
    'LotTransactionUploadActionId'   => env('BANDHAN_LOT_TRANSACTION_UPLOAD_ACTION_ID', '3717'),
    'LotTransactionActionId'         => env('BANDHAN_LOT_TRANSACTION_ACTION_ID', '3717'),
    'LotTransactionInfoActionId'     => env('BANDHAN_LOT_TRANSACTION_INFO_ACTION_ID', '3718'),
    'LotTransactionDetActionId'      => env('BANDHAN_LOT_TRANSACTION_DET_ACTION_ID', '3719'),
    'LotTransactionResponseActionId' => env('BANDHAN_LOT_TRANSACTION_RESPONSE_ACTION_ID', '3719'),

    // ─────────────────────────────────────────────────────────────────
    // OLD / PREVIOUS ACTION IDs (Lakshmi Bhandar Scheme)
    // ─────────────────────────────────────────────────────────────────
    'old_actions' => [
        'OldLotValidationUploadActionId'    => env('BANDHAN_OLD_LOT_VALIDATION_UPLOAD_ACTION_ID', '1060'),
        'OldLotValidationInfoActionId'      => env('BANDHAN_OLD_LOT_VALIDATION_INFO_ACTION_ID', '1068'),
        'OldLotValidationResponseActionId'  => env('BANDHAN_OLD_LOT_VALIDATION_RESPONSE_ACTION_ID', '1069'),
        'OldLotTransactionUploadActionId'   => env('BANDHAN_OLD_LOT_TRANSACTION_UPLOAD_ACTION_ID', '1072'),
        'OldLotTransactionInfoActionId'     => env('BANDHAN_OLD_LOT_TRANSACTION_INFO_ACTION_ID', '1073'),
        'OldLotTransactionResponseActionId' => env('BANDHAN_OLD_LOT_TRANSACTION_RESPONSE_ACTION_ID', '1074'),
    ],

    'OldLotValidationUploadActionId'    => env('BANDHAN_OLD_LOT_VALIDATION_UPLOAD_ACTION_ID', '1060'),
    'OldLotValidationInfoActionId'      => env('BANDHAN_OLD_LOT_VALIDATION_INFO_ACTION_ID', '1068'),
    'OldLotValidationDetActionId'       => env('BANDHAN_OLD_LOT_VALIDATION_DET_ACTION_ID', '1069'),
    'OldLotValidationResponseActionId'  => env('BANDHAN_OLD_LOT_VALIDATION_RESPONSE_ACTION_ID', '1069'),
    'OldLotTransactionUploadActionId'   => env('BANDHAN_OLD_LOT_TRANSACTION_UPLOAD_ACTION_ID', '1072'),
    'OldLotTransactionInfoActionId'     => env('BANDHAN_OLD_LOT_TRANSACTION_INFO_ACTION_ID', '1073'),
    'OldLotTransactionDetActionId'      => env('BANDHAN_OLD_LOT_TRANSACTION_DET_ACTION_ID', '1074'),
    'OldLotTransactionResponseActionId' => env('BANDHAN_OLD_LOT_TRANSACTION_RESPONSE_ACTION_ID', '1074'),

    // ─────────────────────────────────────────────────────────────────
    // LEGACY ACTION IDs
    // ─────────────────────────────────────────────────────────────────
    'legacy_actions' => [
        'LegacyLotValidationUploadActionId' => env('BANDHAN_LEGACY_LOT_VALIDATION_UPLOAD_ACTION_ID', '2036'),
        'LegacyLotValidationInfoActionId'   => env('BANDHAN_LEGACY_LOT_VALIDATION_INFO_ACTION_ID', '2037'),
        'LegacyLotValidationDetActionId'    => env('BANDHAN_LEGACY_LOT_VALIDATION_DET_ACTION_ID', '2038'),
    ],

    'LegacyLotValidationUploadActionId' => env('BANDHAN_LEGACY_LOT_VALIDATION_UPLOAD_ACTION_ID', '2036'),
    'LegacyLotValidationInfoActionId'   => env('BANDHAN_LEGACY_LOT_VALIDATION_INFO_ACTION_ID', '2037'),
    'LegacyLotValidationDetActionId'    => env('BANDHAN_LEGACY_LOT_VALIDATION_DET_ACTION_ID', '2038'),
];
