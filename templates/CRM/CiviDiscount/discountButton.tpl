<div class="crm-public-form-item crm-section cividiscount-section cividiscount">
  {foreach from=$discountElements item=discountElement}
    <div class="label">{$form.$discountElement.label}</div>
      <div class="content">{$form.$discountElement.html}</div>
  {/foreach}
  <div class="clear"></div>
</div>
