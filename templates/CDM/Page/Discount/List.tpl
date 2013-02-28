{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
<div id="help">
    {ts}Discount codes can be applied against events, memberships and price sets.{/ts}
</div>

{if $rows}
<div id="dcode">
    {strip}
    {* handle enable/disable actions*}
    {include file="CRM/common/enableDisable.tpl"}
    {include file="CRM/common/jsortable.tpl"}
    <table id="options" class="display">
    <thead>
    <tr>
        <th id="sortable">{ts}Name / Description{/ts}</th>
        <th>{ts}Amount{/ts}</th>
        <th>{ts}Usage{/ts}</th>
        <th>{ts}Start Date{/ts}</th>
        <th>{ts}End Date{/ts}</th>
        <th></th>
    </tr>
    </thead>
    {foreach from=$rows item=row}
    <tr id="row_{$row.id}" class="{if NOT $row.is_active} disabled{/if}{cycle values="odd-row,even-row"} {$row.class}">
        <td class="crm-discount-code">{$row.code} <br /> {$row.description}</td>
        <td class="right">{if $row.amount_type eq '1'}{$row.amount} %{else}{$row.amount|crmMoney}{/if}</td>
        <td class="right"><a href="/civicrm/cividiscount/report?id={$row.id}&reset=1">{$row.count_use}</a> / {if $row.count_max eq 0}{ts}Unlimited{/ts}{else}{$row.count_max}{/if}</td>
        <td>{if $row.active_on neq '0000-00-00 00:00:00'}{$row.active_on|truncate:10:''|crmDate}{/if}</td>
        <td>{if $row.expire_on neq '0000-00-00 00:00:00'}{$row.expire_on|truncate:10:''|crmDate}{/if}</td>
        <td>{$row.action|replace:'xx':$row.id}</td>
    </tr>
    {/foreach}
    </table>
    {/strip}

    <div class="action-link">
        <a href="{crmURL p='civicrm/cividiscount/discount/add q="reset=1"}" id="newDiscountCode" class="button"><span>&raquo; {ts}New Discount Code{/ts}</span></a>
    </div>
</div>
{else}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {capture assign=crmURL}{crmURL p='civicrm/cividiscount/discount/add' q="reset=1"}{/capture}
      {ts 1=$crmURL}There are no discount codes. You can <a href='%1'>add one</a>.{/ts}
    </div>
{/if}
