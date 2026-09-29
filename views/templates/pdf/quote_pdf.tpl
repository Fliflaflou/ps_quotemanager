<style>
body { color: #363a41; font-size: 9pt; }
table { width: 100%; border-collapse: collapse; }
.text-right { text-align: right; }
.muted { color: #6c868e; }
.document-title { color: #25b9d7; font-size: 22pt; font-weight: bold; }
.section-title { color: #363a41; font-size: 11pt; font-weight: bold; }
.document-meta td { padding: 2px 0; }
.products th { background-color: #363a41; color: #ffffff; font-size: 8pt; font-weight: bold; }
.products th, .products td { border: 1px solid #d9e1e4; padding: 6px 3px; }
.products .product-name { font-weight: bold; }
.totals { width: 40%; margin-left: 60%; }
.totals td { border-bottom: 1px solid #d9e1e4; padding: 6px; }
.totals .grand-total td { background-color: #25b9d7; color: #ffffff; border: 0; font-size: 11pt; font-weight: bold; }
.notes { border-left: 3px solid #25b9d7; color: #555; padding: 7px 10px; }
.shop-logo { max-width: 180px; max-height: 80px; margin-bottom: 8px; }
.shipping-line td { color: #555; }
.party-block { vertical-align: top; }
.payment-info { margin-top: 10px; font-size: 8pt; color: #555; }
.legal-notice { margin-top: 12px; font-size: 8pt; text-align: center; }
</style>

<table class="document-meta">
    <tr>
        <td width="58%">
            {if $logo_path}<img class="shop-logo" src="{$logo_path|escape:'html':'UTF-8'}" width="{$logo_width|intval}" height="{$logo_height|intval}" alt="{$shop_name|escape:'html':'UTF-8'}"><br>{/if}
        </td>
        <td width="42%" class="text-right">
            <span class="document-title">{l s='Devis' mod='myquotemanager'}</span><br>
            <strong>#{$quote->reference|escape:'html':'UTF-8'}</strong><br>
            <span class="muted">{l s='Créé le' mod='myquotemanager'}: {$quote->date_add|escape:'html':'UTF-8'}</span><br>
            {if $quote->date_exp}<span class="muted">{l s='Valable jusqu’au' mod='myquotemanager'}: {$quote->date_exp|escape:'html':'UTF-8'}</span>{/if}
        </td>
    </tr>
</table>

<br><br>

<table class="document-meta">
    <tr>
        <td width="50%" class="party-block">
            <span class="section-title">{l s='Émetteur' mod='myquotemanager'}</span><br>
            {if $shop_company && $shop_company != $shop_name}{$shop_company|escape:'html':'UTF-8'}<br>{/if}
            <span>{$shop_name|escape:'html':'UTF-8'}</span><br>
            {if $shop_address}{$shop_address|escape:'html':'UTF-8'}<br>{/if}
            {if $shop_phone}{$shop_phone|escape:'html':'UTF-8'}<br>{/if}
            {if $shop_email}{$shop_email|escape:'html':'UTF-8'}{/if}
        </td>
        <td width="50%" class="text-right party-block">
            <span class="section-title">{l s='Client' mod='myquotemanager'}</span><br>
            {if $customer_company}{$customer_company|escape:'html':'UTF-8'}<br>{/if}
            {$customer->firstname|escape:'html':'UTF-8'} {$customer->lastname|escape:'html':'UTF-8'}<br>
            {if $customer_address}
                {$customer_address->address1|escape:'html':'UTF-8'}
                {if $customer_address->address2}<br>{$customer_address->address2|escape:'html':'UTF-8'}{/if}
                <br>{$customer_address->postcode|escape:'html':'UTF-8'} {$customer_address->city|escape:'html':'UTF-8'}
            {/if}
            {if $customer_phone}<br>{$customer_phone|escape:'html':'UTF-8'}{/if}
            {if $customer->email}<br>{$customer->email|escape:'html':'UTF-8'}{/if}
        </td>
    </tr>
</table>

<br><br>

{if $price_display == 'both'}
    {assign var=w_name value='30%'}{assign var=w_attr value='18%'}{assign var=w_price value='11.5%'}
{else}
    {assign var=w_name value='42%'}{assign var=w_attr value='22%'}{assign var=w_price value='15%'}
{/if}

<table class="products">
    <thead>
        <tr>
            <th width="{$w_name}">{l s='Produit' mod='myquotemanager'}</th>
            <th width="{$w_attr}">{l s='Déclinaison' mod='myquotemanager'}</th>
            <th width="6%" class="text-right">{l s='Qté' mod='myquotemanager'}</th>
            {if $price_display != 'tax_incl'}<th width="{$w_price}" class="text-right">{l s='Prix unitaire HT' mod='myquotemanager'}</th>{/if}
            {if $price_display != 'tax_excl'}<th width="{$w_price}" class="text-right">{l s='Prix unitaire TTC' mod='myquotemanager'}</th>{/if}
            {if $price_display != 'tax_incl'}<th width="{$w_price}" class="text-right">{l s='Total remisé HT' mod='myquotemanager'}</th>{/if}
            {if $price_display != 'tax_excl'}<th width="{$w_price}" class="text-right">{l s='Total remisé TTC' mod='myquotemanager'}</th>{/if}
        </tr>
    </thead>
    <tbody>
        {foreach from=$products item=product}
        <tr>
            <td width="{$w_name}" class="product-name">{$product.product_name|escape:'html':'UTF-8'}</td>
            <td width="{$w_attr}">{if $product.product_attributes}{$product.product_attributes|escape:'html':'UTF-8'}{else}{$product.product_reference|escape:'html':'UTF-8'}{/if}</td>
            <td width="6%" class="text-right">{$product.quantity|intval}</td>
            {if $price_display != 'tax_incl'}<td width="{$w_price}" class="text-right">{$product.price_tax_excl|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'}</td>{/if}
            {if $price_display != 'tax_excl'}<td width="{$w_price}" class="text-right">{$product.price_tax_incl|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'}</td>{/if}
            {if $price_display != 'tax_incl'}<td width="{$w_price}" class="text-right">{$product.net_tax_excl|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'}</td>{/if}
            {if $price_display != 'tax_excl'}<td width="{$w_price}" class="text-right">{$product.net_tax_incl|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'}</td>{/if}
        </tr>
        {/foreach}
    </tbody>
</table>

<br>

<table class="totals">
    {if $quote->total_discount_wt > 0}
    <tr>
        <td>{l s='Remises produits (incluses dans les lignes)' mod='myquotemanager'}</td>
        <td class="text-right">
            {if $price_display != 'tax_incl'}-{$quote->total_discount|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'} HT{/if}
            {if $price_display == 'both'}<br>{/if}
            {if $price_display != 'tax_excl'}-{$quote->total_discount_wt|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'} TTC{/if}
        </td>
    </tr>
    {/if}
    {if $quote->total_global_discount_wt > 0}
    <tr>
        <td>{l s='Remise globale' mod='myquotemanager'}</td>
        <td class="text-right">
            {if $price_display != 'tax_incl'}-{$quote->total_global_discount|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'} HT{/if}
            {if $price_display == 'both'}<br>{/if}
            {if $price_display != 'tax_excl'}-{$quote->total_global_discount_wt|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'} TTC{/if}
        </td>
    </tr>
    {/if}
    <tr>
        <td>{l s='Total HT' mod='myquotemanager'}</td>
        <td class="text-right">{$quote->total_paid_tax_excl|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'}</td>
    </tr>
    {if $total_tax > 0}
    <tr>
        <td>{l s='TVA' mod='myquotemanager'}</td>
        <td class="text-right">{$total_tax|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'}</td>
    </tr>
    {/if}
    <tr class="shipping-line">
        <td>{l s='Transport' mod='myquotemanager'}{if $carrier_name} ({$carrier_name|escape:'html':'UTF-8'}){/if}</td>
        <td class="text-right">
            {if $price_display != 'tax_incl'}{$quote->total_shipping|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'} HT{/if}
            {if $price_display == 'both'}<br>{/if}
            {if $price_display != 'tax_excl'}{$quote->total_shipping_wt|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'} TTC{/if}
        </td>
    </tr>
    <tr class="grand-total">
        <td>{l s='Total TTC' mod='myquotemanager'}</td>
        <td class="text-right">{$quote->total_paid|number_format:2:',':' '} {$currency->sign|escape:'html':'UTF-8'}</td>
    </tr>
</table>

{if $quote->notes}
<br>
<span class="section-title">{l s='Notes' mod='myquotemanager'}</span>
<p class="notes">{$quote->notes|strip_tags|escape:'html':'UTF-8'|nl2br}</p>
{/if}

{if $payment_info}
<p class="payment-info">{$payment_info|escape:'html':'UTF-8'|nl2br}</p>
{/if}

<p class="legal-notice">{$legal_notice|escape:'html':'UTF-8'}</p>