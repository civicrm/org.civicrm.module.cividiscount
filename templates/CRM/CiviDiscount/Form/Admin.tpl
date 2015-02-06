{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing discounts  *}
<h3>
  {if $action eq 1}{ts}New Discount{/ts}{elseif $action eq 2}{ts}Edit Discount{/ts}
  {elseif $action eq 16384}{ts}Copy Discount{/ts}
  {else}{ts}Delete Discount{/ts}{/if}
</h3>
<div class="crm-block crm-form-block crm-discount-item-form-block">
  {if $action eq 16384}
    <div class="messages status no-popup">
      <dl>
        <dt>
        <div class="icon inform-icon"></div>
        </dt>
        <dd>
          {ts}Are you sure you want to copy this discount code?{/ts}
        </dd>
      </dl>
    </div>
  {elseif $action eq 8}
    <div class="messages status no-popup">
      <dl>
        <dt>
        <div class="icon inform-icon"></div>
        </dt>
        <dd>
          {ts 1=$discountValue.code}WARNING: Deleting this discount code (%1) will prevent users who have this code to avail of this discount.{/ts} {ts}Do you want to continue?{/ts}
        </dd>
      </dl>
    </div>
  {else}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout-compressed">
      <tr class="crm-discount-item-form-block-label">
        <td class="label">{$form.code.label}</td>
        <td>
          {$form.code.html|crmReplace:class:'crm-form-text big'}
          {if $action eq 1}
            <a class="crm-hover-button" href="#" id="generate-code"><span class="icon ui-icon-shuffle"></span> {ts}Random{/ts}</a>
            <div class="description">{ts}Do not use spaces in the Discount Code.{/ts}</div>
          {/if}
        </td>
      </tr>
      <tr class="crm-discount-item-form-block-description">
        <td class="label">{$form.description.label}</td>
        <td>{$form.description.html}</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>{$form.is_active.html} {$form.is_active.label}</td>
      </tr>
      <tr class="crm-discount-item-form-block-amount">
        <td class="label">{$form.amount.label}</td>
        <td>{$form.amount.html|crmReplace:class:'crm-form-text six'} {$form.amount_type.html}
        </td>
      </tr>
    </table>
    <fieldset class="crm-collapsible">
      <legend class="collapsible-title">{ts}Additional Options{/ts}</legend>
      <div>
        <table class="form-layout-compressed">
          <tr class="crm-discount-item-form-block-count_max">
            <td class="label">{$form.count_max.label} {help id="count_max" title=$form.count_max.label}</td>
            <td>{$form.count_max.html|crmReplace:type:number}</td>
          </tr>
          <tr class="crm-discount-item-form-block-active_on">
            <td class="label">{$form.active_on.label} {help id="active_on" title=$form.active_on.label}</td>
            <td>{include file="CRM/common/jcalendar.tpl" elementName=active_on}</td>
          </tr>
          <tr class="crm-discount-item-form-block-expire_on">
            <td class="label">{$form.expire_on.label} {help id="expire_on" title=$form.expire_on.label}</td>
            <td>{include file="CRM/common/jcalendar.tpl" elementName=expire_on}</td>
          </tr>
          <tr class="crm-discount-item-form-block-organization_id">
            <td class="label">{$form.organization_id.label} {help id="organization" title=$form.organization_id.label}</td>
            <td>{$form.organization_id.html}</td>
          </tr>
          {if $form.pricesets}
            <tr class="crm-discount-item-form-block-price-set">
              <td class="label">{$form.pricesets.label} {help id="pricesets" title=$form.pricesets.label}</td>
              <td>{$form.pricesets.html}</td>
            </tr>
          {/if}
          <tr>
            <td>&nbsp;</td>
            <td>{$form.discount_msg_enabled.html} {$form.discount_msg_enabled.label}</td>
          </tr>
          <tr class="crm-discount-item-form-block-discount-message">
            <td class="label">{help id="discount-message" title=$form.discount_msg.label}</td>
            <td>{$form.discount_msg.html}</td>
          </tr>
        </table>
      </div>
    </fieldset>
    {if $form.events}
      <div class="crm-accordion-wrapper {if $action eq 1}collapsed {/if}crm-discount-form-block-events">
        <div class="crm-accordion-header">
          {ts}Discounts for events{/ts}
        </div>
        <div class="crm-accordion-body">
          <table class="form-layout-compressed">
            <tr class="crm-discount-item-form-block-events">
              <td class="label">{$form.events.label} {help id="events" title=$form.events.label}</td>
              <td>{$form.events.html}<td>
            </tr>
            <tr class="crm-discount-item-form-block-event-types">
              <td class="label">{$form.event_type_id.label} {help id="eventtypes" title=$form.eventstypes.label}</td>
              <td>{$form.event_type_id.html}</td>
            </tr>
          </table>
        </div>
      </div>
    {/if}

    {if $form.memberships}
      <div class="crm-accordion-wrapper {if $action eq 1}collapsed {/if}crm-discount-form-block-memberships">
        <div class="crm-accordion-header">
          {ts}Discounts for memberships{/ts}
        </div>
        <div class="crm-accordion-body">
          <table class="form-layout-compressed">
            <tr class="crm-discount-item-form-block-memberships">
              <td class="label">{$form.memberships.label} {help id="memberships" title=$form.memberships.label}</td>
              <td>{$form.memberships.html}<br/></td>
            </tr>
          </table>
        </div>
      </div>
    {/if}
    {if $autodiscounts}
      <div class="crm-accordion-wrapper collapsed crm-discount-form-block-other-criteria">
        <div class="crm-accordion-header">
          {ts}Automatic Discounts{/ts}
        </div>
        <div class="crm-accordion-body">
          <p class="description">{ts}Discount will be applied automatically if all of the following conditions are met (no code needed).{/ts} {help id="autodiscount" title="Automatic discounts"}</p>
          <table class="form-layout-compressed">
            {foreach from=$autodiscounts item='autodiscount'}
              <tr class="crm-discount-item-form-block-auto-discount">
                <td class="label">{$form.$autodiscount.label}</td>
                <td>{$form.$autodiscount.html}</td>
              </tr>
            {/foreach}
            <tr class="crm-discount-item-form-block-advanced_autodiscount_filter_entity">
              <td class="label">{$form.advanced_autodiscount_filter_entity.label}</td>
              <td>{$form.advanced_autodiscount_filter_entity.html}</td>
            </tr>
            <tr>
              <td class="label">{$form.advanced_autodiscount_filter_string.label}</td>
              <td>{$form.advanced_autodiscount_filter_string.html}</td>
            </tr>
          </table>
        </div>
      </div>
    {/if}
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $('#discount_msg_enabled').change(function() {
        $('.crm-discount-item-form-block-discount-message').toggle($(this).is(':checked'));
      }).change();

      $("#generate-code").click(function (e) {
        $("#code").val(randomString("abcdefghjklmnpqrstwxyz23456789", 8));
        e.preventDefault();
      });

      // Yanked from http://stackoverflow.com/questions/2477862/jquery-password-generator
      function randomString(chars, len) {
        var i = 0, str = "", $max, $num, $temp;
        while (i <= len) {
          $max = chars.length - 1;
          $num = Math.floor(Math.random() * $max);
          $temp = chars.substr($num, 1);
          str += $temp;
          i++;
        }

        return str;
      }
    });

  </script>
{/literal}
