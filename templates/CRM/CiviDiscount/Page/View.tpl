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
<div class="crm-block crm-content-block crm-discount-view-form-block">

  <h3>{ts}View Discount{/ts}</h3>

  <div class="action-link">
    <div class="crm-submit-buttons">
      {if call_user_func(array('CRM_Core_Permission','check'), 'administer CiviCRM')}
        <a class="button" href='{crmURL p='civicrm/cividiscount/discount/edit' q="reset=1&id=$id"}' accesskey="e">
          <span><span class="icon ui-icon-pencil"></span>{ts}Edit{/ts}</span>
        </a>
      {/if}
      {if call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviContribute')}
        <a class="button" href='{crmURL p='civicrm/cividiscount/discount/delete' q="reset=1&id=$id"}'>
          <span><span class="icon delete-icon"></span>{ts}Delete{/ts}</span>
        </a>
      {/if}
    </div>
  </div>

  <table class="crm-info-panel">
    <tr>
      <td class="label">{ts}Code{/ts}</td>
      <td>{$code}</td>
    </tr>
    <tr>
      <td class="label">{ts}Description{/ts}</td>
      <td>{$description}</td>
    </tr>
    <tr>
      <td class="label">{ts}Discount{/ts}</td>
      <td>{if $amount_type eq '1'}{$amount} %{else}{$amount|crmMoney}{/if}</td>
    </tr>
    <tr>
      <td class="label">{ts}Usage{/ts}</td>
      {assign var='urlParams' value="id=`$code_id`&reset=1"}
      <td><a href="{crmURL p='civicrm/cividiscount/report' q=$urlParams}">{$count_use}</a> / {if $count_max eq 0}{ts}Unlimited{/ts}{else}{$count_max}{/if}</td>
    </tr>
    <tr>
      <td class="label">{ts}Start Date{/ts}</td>
      <td>{if $active_on neq '0000-00-00 00:00:00'}{$active_on|crmDate}{else}{ts}(ongoing){/ts}{/if}</td>
    </tr>
    <tr>
      <td class="label">{ts}Expiration Date{/ts}</td>
      <td>{if $expire_on neq '0000-00-00 00:00:00'}{$expire_on|crmDate}{else}{ts}(ongoing){/ts}{/if}</td>
    </tr>
    <tr>
      <td class="label">{ts}Organization{/ts}</td>
      <td>{$organization}</td>
    </tr>
    <tr>
      <td class="label">{ts}Automatic Discount{/ts}</td>
      <td>
        {foreach from=$autodiscount key=k item=v}
          {$v} <br />
        {/foreach}
      </td>
    </tr>
    <tr>
      <td class="label">{ts}Events{/ts}</td>
      <td>
        {foreach from=$events key=k item=v}
          {$v} <br />
        {/foreach}
      </td>
    </tr>
    <tr>
      <td class="label">{ts}Memberships{/ts}</td>
      <td>
        {foreach from=$memberships key=k item=v}
          {$v} <br />
        {/foreach}
      </td>
    </tr>
    <tr>
      <td class="label">{ts}Price Sets{/ts}</td>
      <td>
        {foreach from=$pricesets key=k item=v}
          {$v} <br />
        {/foreach}
      </td>
    </tr>
    <tr>
      <td class="label">{ts}Enabled?{/ts}</td>
      <td>{if $is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
    </tr>

    {foreach from=$note item="rec"}
      {if $rec }
        <tr>
          <td class="label">{ts}Note{/ts}</td><td>{$rec}</td>
        </tr>
      {/if}
    {/foreach}
  </table>

  <div class="action-link">
    <div class="crm-submit-buttons">
      {assign var='urlParams' value="reset=1&id=$id"}
      {if call_user_func(array('CRM_Core_Permission','check'), 'administer CiviCRM')}
        <a class="button" href="{crmURL p='civicrm/cividiscount/discount/edit' q=$urlParams}" accesskey="e">
          <span><span class="icon ui-icon-pencil"></span>{ts}Edit{/ts}</span></a>
      {/if}
      {if call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviContribute')}
        <a class="button" href="{crmURL p='civicrm/cividiscount/discount/delete' q=$urlParams}">
          <span><span class="icon delete-icon"></span>{ts}Delete{/ts}</span></a>
      {/if}
      <a class="button cancel" href="{crmURL p='civicrm/cividiscount/discount/list' q='reset=1'}">
        <span class="icon ui-icon-close"></span><span class="crm-button_discount-view_cancel">{ts}Done{/ts}</span>
      </a>
    </div>
  </div>
</div>
