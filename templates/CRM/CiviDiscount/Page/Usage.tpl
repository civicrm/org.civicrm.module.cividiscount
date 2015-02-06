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

  <table class="crm-info-panel">
    <tr>
      {if $hide_contact === NULL}
        <th class="label">{ts}Contact{/ts}</th>
      {/if}
      <th class="label">{ts}Event{/ts}</th>
      <th class="label">{ts}Membership{/ts}</th>
      <th class="label">{ts}Date{/ts}</th>
      <th class="label">{ts}Code{/ts}</th>
    </tr>
    {foreach from=$rows item=row}
      {if $row}
        <tr>
          {if $hide_contact === NULL}
            {assign var='urlParams' value="cid=`$row.contact_id`&reset=1"}
            <td>
              <a href='{crmURL p="civicrm/contact/view" q="cid=`$row.contact_id`&reset=1"}'>{$row.display_name}</a>&nbsp;&nbsp;(ID:{$row.contact_id})
            </td>
          {/if}
          <td>{$row.event_title}</td>
          <td>{$row.membership_title}</td>
          <td>{$row.used_date|crmDate}</td>
          <td>{$row.code}</td>
        </tr>
      {/if}
    {/foreach}
    </tr>
  </table>
</div>
