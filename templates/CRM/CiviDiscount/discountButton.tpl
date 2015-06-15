<table class="form-layout-compressed">
  {foreach from=$discountElements item=discountElement}
    <tr><td class="label nowrap">{$form.$discountElement.label}</td><td>{$form.$discountElement.html}</td></tr>
  {/foreach}
</table>
