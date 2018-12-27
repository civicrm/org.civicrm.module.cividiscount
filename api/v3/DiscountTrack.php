<?php

/**
 * This API exposes discount tracking records.
 *
 * Discount codes are provided by the CiviDiscount extension.
 *
 * @package CiviDiscount
 */

/**
 * Create or update a discount code.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_discount_track_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation
 *
 * @param array $params
 *   Array of parameters determined by getfields
 */
function _civicrm_api3_discount_track_create_spec(&$params) {
  $params['used_date']['api.default'] = 'now';
  $params['entity_table']['api.required'] = TRUE;
  $params['entity_id']['api.required'] = TRUE;
}

/**
 * Get discount tracking records.
 *
 * @param array $params
 *   If $params is empty, all items will be returned
 *
 * @return array
 *   Discount Tracking records.
 */
function civicrm_api3_discount_track_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an existing discount tracking record.
 *
 * @param array $params
 *   Array containing id of the item to be deleted.
 *
 * @return array
 *   API result Array
 */
function civicrm_api3_discount_track_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Because this api doesn't follow the usual naming pattern we have to explicitly declare dao name.
 *
 * @return string
 *   BAO name.
 */
function _civicrm_api3_discount_track_DAO() {
  return 'CRM_CiviDiscount_BAO_Track';
}
