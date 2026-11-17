{**
 * @author    Axelweb <contact@axelweb.fr>
 * @copyright 2026 Axelweb
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Axelweb
 *}
<div class="col">
  <div class="card">
    <h3 class="card-header">
      <i class="material-icons">login</i>
      {$awloginascustomer.label|escape:'html':'UTF-8'}
    </h3>
    <div class="card-body">
      {if $awloginascustomer.is_guest}
        <p class="mb-0">{l s='This is a guest account: convert it to a customer account first.' d='Modules.Awloginascustomer.Admin'}</p>
      {elseif !$awloginascustomer.is_active}
        <p class="mb-0">{l s='This customer account is disabled.' d='Modules.Awloginascustomer.Admin'}</p>
      {else}
        <p>{l s='Opens the shop in a new tab, logged in as this customer, to place an order on their behalf.' d='Modules.Awloginascustomer.Admin'}</p>
        <a href="{$awloginascustomer.url|escape:'html':'UTF-8'}" class="btn btn-primary" target="_blank" rel="noopener">
          <i class="material-icons">login</i>
          {$awloginascustomer.label|escape:'html':'UTF-8'}
        </a>
      {/if}
    </div>
  </div>
</div>
