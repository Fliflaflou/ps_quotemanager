{* Template de consultation et d'edition d'un devis *}

{assign var=current_status_name value='Statut inconnu'}
{assign var=current_status_color value='#6c757d'}
{foreach from=$quote_statuses item=status}
    {if $quote->id_quote_status == $status.id_quote_status}
        {assign var=current_status_name value=$status.name}
        {assign var=current_status_color value=$status.color}
    {/if}
{/foreach}

<div class="quote-workspace">
    <div class="panel quote-header">
        <div class="panel-body clearfix">
            <div class="quote-header__identity">
                <span class="quote-header__eyebrow">{l s='Devis' mod='myquotemanager'}</span>
                <h2>#{$quote->reference|escape:'html':'UTF-8'}</h2>
                <span id="quote-status-label" class="quote-status" style="background-color: {$current_status_color|escape:'html':'UTF-8'};">
                    {$current_status_name|escape:'html':'UTF-8'}
                </span>
            </div>
            <div class="quote-header__actions">
                <a href="{$current_index}&token={$token}" class="btn btn-default">
                    <i class="icon-arrow-left"></i> {l s='Retour à la liste' mod='myquotemanager'}
                </a>
                {if $can_view_quote}
                    <a href="{$current_index}&generatequotepdf=1&id_quote={$quote->id_quote|intval}&token={$token}" class="btn btn-default">
                        <i class="icon-file-pdf-o"></i> {l s='Télécharger le PDF' mod='myquotemanager'}
                    </a>
                {/if}
                {if $can_edit_quote}
                    <a href="{$current_index}&sendquoteemail=1&id_quote={$quote->id_quote|intval}&token={$token}" class="btn btn-default" onclick="return confirm('{l s='Envoyer ce devis par e-mail au client ?' mod='myquotemanager' js=1}');">
                        <i class="icon-envelope"></i> {l s='Envoyer par e-mail' mod='myquotemanager'}
                    </a>
                {/if}
                {if $can_add_quote}
                    <a href="{$current_index}&duplicatequote=1&id_source_quote={$quote->id_quote|intval}&token={$token}" class="btn btn-default">
                        <i class="icon-copy"></i> {l s='Dupliquer' mod='myquotemanager'}
                    </a>
                {/if}
                {if $order_admin_link}
                    <a href="{$order_admin_link|escape:'html':'UTF-8'}" class="btn btn-success">
                        <i class="icon-shopping-cart"></i> {l s='Voir la commande' mod='myquotemanager'}
                    </a>
                {elseif $can_edit_quote && $quote_products && count($quote_products) > 0 && !$has_stock_shortage}
                    <button type="button" id="open-convert-quote" class="btn btn-success">
                        <i class="icon-shopping-cart"></i> {l s='Transformer en commande' mod='myquotemanager'}
                    </button>
                {/if}
            </div>
        </div>
    </div>

    {if $can_edit_quote && $quote_products && count($quote_products) > 0 && !$has_stock_shortage}
        <div id="convert-quote-panel" class="panel" style="display:none;">
            <div class="panel-heading"><i class="icon-shopping-cart"></i> {l s='Paramètres de la commande' mod='myquotemanager'}</div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <i class="icon-info-circle"></i>
                    <strong>{l s='Paiement à renseigner dans la commande' mod='myquotemanager'}</strong><br>
                    {l s='La commande sera créée sans paiement validé. Le paiement pourra être ajouté ou modifié uniquement depuis la fiche de la commande après la conversion.' mod='myquotemanager'}
                </div>
                <form method="post" action="{$convert_quote_url|escape:'html':'UTF-8'}" class="form-horizontal">
                    <input type="hidden" name="submitConvertQuote" value="1">
                    <div class="form-group">
                        <label class="control-label col-lg-3" for="convert-order-state">{l s='Statut initial de la commande' mod='myquotemanager'}</label>
                        <div class="col-lg-9">
                            <select id="convert-order-state" name="id_order_state" class="form-control" required>
                                {foreach $order_states as $order_state}
                                    <option value="{$order_state.id_order_state|intval}"{if $order_state.id_order_state == $default_order_state_id} selected="selected"{/if}>
                                        {$order_state.name|escape:'html':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>
                            <p class="help-block">{l s='Tous les statuts actifs de PrestaShop sont disponibles, y compris ceux créés par le vendeur. Un statut marqué comme payé peut enregistrer le paiement automatiquement ; pour un paiement à renseigner plus tard, choisissez un statut d’attente.' mod='myquotemanager'}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-lg-3" for="convert-address-delivery">{l s='Adresse de livraison' mod='myquotemanager'}</label>
                        <div class="col-lg-9">
                            <select id="convert-address-delivery" name="id_address_delivery" class="form-control" required>
                                {foreach $customer_addresses as $address}
                                    <option value="{$address.id_address|intval}"{if $address.id_address == $quote->id_address_delivery} selected="selected"{/if}>
                                        {$address.alias|escape:'html':'UTF-8'} - {$address.address1|escape:'html':'UTF-8'}, {$address.postcode|escape:'html':'UTF-8'} {$address.city|escape:'html':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-lg-3" for="convert-address-invoice">{l s='Adresse de facturation' mod='myquotemanager'}</label>
                        <div class="col-lg-9">
                            <select id="convert-address-invoice" name="id_address_invoice" class="form-control" required>
                                {foreach $customer_addresses as $address}
                                    <option value="{$address.id_address|intval}"{if $address.id_address == $quote->id_address_invoice} selected="selected"{/if}>
                                        {$address.alias|escape:'html':'UTF-8'} - {$address.address1|escape:'html':'UTF-8'}, {$address.postcode|escape:'html':'UTF-8'} {$address.city|escape:'html':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-success"><i class="icon-check"></i> {l s='Confirmer la conversion' mod='myquotemanager'}</button>
                        <button type="button" id="cancel-convert-quote" class="btn btn-default">{l s='Annuler' mod='myquotemanager'}</button>
                    </div>
                </form>
            </div>
        </div>
    {/if}

    <div class="row quote-order-layout">
        <div class="col-md-4">
            <div class="panel quote-side-card">
                <div class="panel-heading">
                    <i class="icon-user"></i> {l s='Client' mod='myquotemanager'}
                    {if $can_edit_quote && !$quote->id_order}
                        <button type="button" id="open-change-customer" class="btn btn-default btn-xs panel-heading-action">
                            <i class="icon-exchange"></i> {l s='Changer de client' mod='myquotemanager'}
                        </button>
                    {/if}
                </div>
                <div class="panel-body">
                    <h4 class="quote-customer-name">{$customer.firstname|escape:'html':'UTF-8'} {$customer.lastname|escape:'html':'UTF-8'}</h4>
                    <p class="quote-customer-email"><a href="mailto:{$customer.email|escape:'html':'UTF-8'}">{$customer.email|escape:'html':'UTF-8'}</a></p>
                    <span class="text-muted">#{$customer.id_customer|intval}</span>

                    {if $can_edit_quote && !$quote->id_order}
                        <form id="change-customer-form" method="post" action="{$change_customer_url|escape:'html':'UTF-8'}" style="display:none; margin-top: 12px;">
                            <input type="hidden" name="submitChangeQuoteCustomer" value="1">
                            <div class="form-group">
                                <label for="new-customer-search">{l s='Nouveau client :' mod='myquotemanager'}</label>
                                <select name="new_id_customer" id="new-customer-search" class="form-control">
                                    <option value="">{l s='Rechercher un client...' mod='myquotemanager'}</option>
                                    {foreach $customer_options as $customer_option}
                                        <option value="{$customer_option.id_customer|intval}"{if $customer_option.id_customer == $customer.id_customer} disabled="disabled"{/if}>
                                            {$customer_option.name|escape:'html':'UTF-8'}
                                        </option>
                                    {/foreach}
                                </select>
                                <p class="help-block">{l s='Les produits et quantités du devis sont conservés ; l’adresse du devis sera réinitialisée sur celle du nouveau client.' mod='myquotemanager'}</p>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="icon-check"></i> {l s='Confirmer' mod='myquotemanager'}</button>
                            <button type="button" id="cancel-change-customer" class="btn btn-default btn-sm">{l s='Annuler' mod='myquotemanager'}</button>
                        </form>
                    {/if}
                </div>
            </div>

            <div class="panel quote-side-card">
                <div class="panel-heading"><i class="icon-info-circle"></i> {l s='Informations du devis' mod='myquotemanager'}</div>
                <div class="panel-body quote-meta-list">
                    <div><span>{l s='Créé le' mod='myquotemanager'}</span><strong>{dateFormat date=$quote->date_add full=true}</strong></div>
                    <div><span>{l s='Devise' mod='myquotemanager'}</span><strong>{$currency->symbol}</strong></div>
                    {if $quote->date_exp}<div><span>{l s='Expire le' mod='myquotemanager'}</span><strong>{dateFormat date=$quote->date_exp full=true}</strong></div>{/if}
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="panel quote-edit-card">
                <div class="panel-heading"><i class="icon-edit"></i> {l s='Édition du devis' mod='myquotemanager'}</div>
                <div class="panel-body">
        <form id="quote-inline-form" method="post" action="{$current_index}&viewquote&id_quote={$quote->id_quote}&token={$token}">
            <input type="hidden" name="id_quote" value="{$quote->id_quote|intval}">
            <div class="row">
                <div class="col-md-4">
                    <label>{l s='Statut' mod='myquotemanager'}</label>
                    <select name="id_quote_status" class="form-control" required>
                        {foreach from=$quote_statuses item=status}
                            <option value="{$status.id_quote_status|intval}"{if $quote->id_quote_status == $status.id_quote_status} selected="selected"{/if}>
                                {$status.name|escape:'html':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-md-4">
                    <label>{l s='Transporteur' mod='myquotemanager'}</label>
                    <select name="id_carrier" class="form-control" required>
                        <option value="0"{if !$selected_carrier_id} selected="selected"{/if}>{l s='Aucun transporteur (à définir dans la commande)' mod='myquotemanager'}</option>
                        {foreach $carriers as $carrier}
                            <option value="{$carrier.id_carrier|intval}"{if $carrier.id_carrier == $selected_carrier_id} selected="selected"{/if}>
                                {$carrier.name|escape:'html':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-md-4">
                    <label>{l s='Date d’expiration' mod='myquotemanager'}</label>
                    <input type="date" name="date_exp" class="form-control" value="{$quote->date_exp|escape:'html':'UTF-8'}">
                    <div class="js-field-error text-danger small" style="display:none; margin-top: 4px;"></div>
                </div>
                <div class="col-md-4">
                    <label>{l s='Notes' mod='myquotemanager'}</label>
                    <textarea name="notes" class="form-control" rows="3">{$quote->notes|escape:'html':'UTF-8'}</textarea>
                </div>
            </div>
            <div style="margin-top: 12px;">
                <button id="save-quote-btn" type="submit" name="submitUpdateQuoteInline" value="1" class="btn btn-primary">
                    <i class="icon-save"></i> {l s='Enregistrer le devis' mod='myquotemanager'}
                </button>
            </div>
        </form>
                </div>
            </div>
        </div>
    </div>

    {if isset($quote->message) && $quote->message}
        <div class="alert alert-info quote-message"><i class="icon-comment"></i> {$quote->message}</div>
    {/if}

<!-- Products Section -->
<div class="panel">
    <div class="panel-heading">
        <i class="icon-shopping-cart"></i>
        {l s='Produits du devis' mod='myquotemanager'}
        <div class="panel-heading-action">
            <button type="button" class="btn btn-success btn-sm" id="add-product-btn">
                <i class="icon-plus"></i> {l s='Ajouter un produit' mod='myquotemanager'}
            </button>
        </div>
    </div>
    
    <div class="panel-body">
        <div id="quote-toast" class="alert" style="display:none; margin-bottom: 15px;"></div>

        <!-- Formulaire d'ajout de produit -->
        <div id="add-product-form" class="well" style="display: none;">
            <h4>{l s='Ajouter un produit au devis' mod='myquotemanager'}</h4>
            <form id="product-add-form" method="post" action="{$current_index}&addproduct&id_quote={$quote->id_quote}&token={$token}">
                <input type="hidden" name="base_price_tax_excl" id="base-price-tax-excl" value="0">
                <input type="hidden" name="base_price_tax_incl" id="base-price-tax-incl" value="0">
                <input type="hidden" name="id_product_attribute" id="id-product-attribute" value="0">
                <div class="row">
                    <div class="col-md-4">
                        <label for="product-search">{l s='Produit :' mod='myquotemanager'}</label>
                        <select name="id_product" id="product-search" class="form-control" required>
                            <option value="">{l s='Sélectionnez un produit...' mod='myquotemanager'}</option>
                            {if isset($products)}
                                {foreach $products as $product}
                                    <option
                                        value="{$product.id_product}"
                                        data-has-combinations="{$product.has_combinations|intval}"
                                        data-price-tax-excl="{$product.base_price_tax_excl|escape:'html':'UTF-8'}"
                                        data-price-tax-incl="{$product.base_price_tax_incl|escape:'html':'UTF-8'}"
                                        data-stock-quantity="{$product.available_quantity|intval}"
                                    >
                                        {$product.name} ({$product.reference})
                                    </option>
                                {/foreach}
                            {/if}
                        </select>
                        <div class="js-field-error text-danger small" style="display:none; margin-top: 4px;"></div>
                    </div>
                    <div class="col-md-4" id="product-attribute-panel" style="display:none;">
                        <label for="product-attribute-select">{l s='Déclinaison :' mod='myquotemanager'}</label>
                        <select name="product_attribute_select" id="product-attribute-select" class="form-control">
                            <option value="">{l s='Sélectionnez une déclinaison...' mod='myquotemanager'}</option>
                            {foreach $products as $product}
                                {foreach $product.combinations as $combination}
                                    {if $combination.id_product_attribute > 0}
                                        <option
                                            value="{$combination.id_product_attribute|intval}"
                                            data-id-product="{$product.id_product|intval}"
                                            data-price-tax-excl="{$combination.base_price_tax_excl|escape:'html':'UTF-8'}"
                                            data-price-tax-incl="{$combination.base_price_tax_incl|escape:'html':'UTF-8'}"
                                            data-stock-quantity="{$combination.available_quantity|intval}"
                                            data-is-default="{if $combination.is_default}1{else}0{/if}"
                                        >
                                            {$combination.display_name}
                                        </option>
                                    {/if}
                                {/foreach}
                            {/foreach}
                        </select>
                        <div class="js-field-error text-danger small" style="display:none; margin-top: 4px;"></div>
                    </div>
                    <div class="col-md-2">
                        <label>{l s='Quantité :' mod='myquotemanager'}</label>
                        <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        <div class="js-field-error text-danger small" style="display:none; margin-top: 4px;"></div>
                        <div id="available-stock-info" class="alert alert-info small" style="display:none; margin-top: 6px; margin-bottom: 0; padding: 6px 8px;"></div>
                    </div>
                    <div class="col-md-2">
                        <label>{l s='Prix HT :' mod='myquotemanager'}</label>
                        <input type="number" name="price_tax_excl" id="product-price-excl" class="form-control" step="0.01" min="0" required>
                        <div class="js-field-error text-danger small" style="display:none; margin-top: 4px;"></div>
                    </div>
                    <div class="col-md-2">
                        <label>{l s='Prix TTC :' mod='myquotemanager'}</label>
                        <input type="number" name="price_tax_incl" id="product-price-incl" class="form-control" step="0.01" min="0" required>
                        <div class="js-field-error text-danger small" style="display:none; margin-top: 4px;"></div>
                    </div>
                    <div class="col-md-1">
                        <label>{l s='Type d’ajustement' mod='myquotemanager'}</label>
                        <select name="adjustment_type" id="adjustment-type" class="form-control">
                            <option value="percent">%</option>
                            <option value="amount">{if isset($currency->sign)}{$currency->sign}{else}€{/if}</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label>{l s='Valeur d’ajustement' mod='myquotemanager'}</label>
                        <input type="number" name="adjustment_value" id="adjustment-value" class="form-control" step="0.01" value="0">
                        <div class="js-field-error text-danger small" style="display:none; margin-top: 4px;"></div>
                    </div>
                    <div class="col-md-1">
                        <label>{l s='Réduction %' mod='myquotemanager'}</label>
                        <input type="number" name="reduction_percent" id="product-reduction-percent-add" class="form-control" step="0.01" min="0" max="100" value="0">
                        <div class="js-field-error text-danger small" style="display:none; margin-top: 4px;"></div>
                    </div>
                    <div class="col-md-1">
                        <label>{l s='Réduction' mod='myquotemanager'} {if isset($currency->sign)}{$currency->sign}{else}€{/if}</label>
                        <input type="number" name="reduction_amount" id="product-reduction-amount-add" class="form-control" step="0.01" min="0" value="0">
                        <div class="js-field-error text-danger small" style="display:none; margin-top: 4px;"></div>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" name="submitAddquote_product" value="1" class="btn btn-success btn-block">
                                <i class="icon-plus"></i> {l s='Ajouter' mod='myquotemanager'}
                            </button>
                            <button type="button" id="cancel-add" class="btn btn-default btn-block" style="margin-top: 5px;">
                                {l s='Annuler' mod='myquotemanager'}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-12">
                        <div class="alert alert-info" style="margin-bottom: 0;">
                            <strong>{l s='Récapitulatif des prix' mod='myquotemanager'}</strong><br>
                            <span>{l s='Base HT:' mod='myquotemanager'}</span>
                            <strong id="summary-base-excl">0.00</strong>
                            <span style="margin-left: 15px;">{l s='Base TTC:' mod='myquotemanager'}</span>
                            <strong id="summary-base-incl">0.00</strong>
                            <span style="margin-left: 15px;">{l s='Ajustement :' mod='myquotemanager'}</span>
                            <strong id="summary-adjustment">0</strong>
                            <span style="margin-left: 15px;">{l s='Réduction :' mod='myquotemanager'}</span>
                            <strong id="summary-reduction">0.00</strong>
                            <span style="margin-left: 15px;">{l s='Final HT:' mod='myquotemanager'}</span>
                            <strong id="summary-final-excl">0.00</strong>
                            <span style="margin-left: 15px;">{l s='Final TTC:' mod='myquotemanager'}</span>
                            <strong id="summary-final-incl">0.00</strong>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div id="quote-empty-products" class="alert alert-warning"{if $quote_products && count($quote_products) > 0} style="display:none;"{/if}>
            <i class="icon-warning"></i>
            {l s='Aucun produit dans ce devis' mod='myquotemanager'}
        </div>
        <table class="table table-striped" id="quote-products-table"{if !$quote_products || count($quote_products) == 0} style="display:none;"{/if}>
            <thead>
                <tr>
                    <th>{l s='Produit' mod='myquotemanager'}</th>
                    <th>{l s='Déclinaison' mod='myquotemanager'}</th>
                    <th width="145">{l s='Quantité' mod='myquotemanager'}</th>
                    <th>{l s='Prix unitaire HT' mod='myquotemanager'}</th>
                    <th>{l s='Prix unitaire TTC' mod='myquotemanager'}</th>
                    <th>{l s='Total HT' mod='myquotemanager'}</th>
                    <th>{l s='Total TTC' mod='myquotemanager'}</th>
                    <th>{l s='Réduction' mod='myquotemanager'}</th>
                    <th>{l s='Total après réduction HT' mod='myquotemanager'}</th>
                    <th>{l s='Total après réduction TTC' mod='myquotemanager'}</th>
                    <th width="80">{l s='Actions' mod='myquotemanager'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $quote_products as $product}
                {assign var="total_excl" value=$product.total_price_tax_excl}
                {assign var="total_incl" value=$product.total_price_tax_incl}
                <tr
                    {if $product.stock_shortage}class="danger"{/if}
                    data-quote-product-id="{$product.id_quote_product|intval}"
                    data-id-product="{$product.id_product|intval}"
                    data-id-product-attribute="{$product.id_product_attribute|intval}"
                    data-quantity="{$product.quantity|intval}"
                    data-price-tax-excl="{$product.price_tax_excl|floatval}"
                    data-price-tax-incl="{$product.price_tax_incl|floatval}"
                >
                    <td>
                        {if isset($product.name) && $product.name}
                            {$product.name}
                        {elseif isset($product.product_name)}
                            {$product.product_name}
                        {else}
                            Produit #{$product.id_product}
                        {/if}
                    </td>
                    <td>
                        {if isset($product.product_attributes) && $product.product_attributes}
                            {$product.product_attributes|escape:'html':'UTF-8'}
                        {elseif isset($product.reference) && $product.reference}
                            {$product.reference|escape:'html':'UTF-8'}
                        {elseif isset($product.product_reference)}
                            {$product.product_reference|escape:'html':'UTF-8'}
                        {else}
                            -
                        {/if}
                    </td>
                    <td>
                        <div class="quote-quantity-editor">
                            <input type="number" class="form-control input-sm product-quantity-input" value="{$product.quantity|intval}" min="1" max="{$product.maximum_quantity|intval}" data-original-quantity="{$product.quantity|intval}">
                            <button type="button" class="btn btn-primary btn-xs quote-apply-btn update-quantity-btn" title="{l s='Mettre à jour la quantité' mod='myquotemanager'}">
                                <i class="icon-check"></i> {l s='Appliquer' mod='myquotemanager'}
                            </button>
                        </div>
                        {if $product.stock_shortage}
                            <p class="text-danger quote-stock-shortage">
                                <i class="icon-warning"></i>
                                {l s='Stock disponible :' mod='myquotemanager'} {$product.maximum_quantity|intval}
                            </p>
                        {/if}
                    </td>
                    <td>{displayPrice price=$product.price_tax_excl currency=$quote->id_currency}</td>
                    <td>{displayPrice price=$product.price_tax_incl currency=$quote->id_currency}</td>
                    <td class="total-excl">{displayPrice price=$total_excl currency=$quote->id_currency}</td>
                    <td class="total-incl">{displayPrice price=$total_incl currency=$quote->id_currency}</td>
                    <td>
                        <div class="quote-reduction-editor">
                            <div class="quote-input-suffix">
                                <input type="number" class="form-control input-sm product-reduction-percent"
                                       value="{$product.reduction_percent|floatval}"
                                       min="0" max="100" step="0.01"
                                       data-original-reduction-percent="{$product.reduction_percent|floatval}"
                                       title="{l s='Réduction en pourcentage (0-100%)' mod='myquotemanager'}">
                                <span class="quote-input-suffix__unit">%</span>
                            </div>
                            <input type="number" class="form-control input-sm product-reduction-amount"
                                   value="{$product.reduction_amount|floatval}"
                                   min="0" step="0.01"
                                   placeholder="€"
                                   data-original-reduction-amount="{$product.reduction_amount|floatval}"
                                   title="{l s='Réduction montant fixe' mod='myquotemanager'}">
                            <button type="button" class="btn btn-primary btn-xs quote-apply-btn update-reduction-btn"
                                    title="{l s='Appliquer la réduction' mod='myquotemanager'}">
                                <i class="icon-check"></i> {l s='Appliquer' mod='myquotemanager'}
                            </button>
                        </div>
                    </td>
                    {assign var="reduction_tax_excl" value=0}
                    {assign var="reduction_tax_incl" value=0}
                    {if $product.reduction_percent > 0 || $product.reduction_amount > 0}
                        {assign var="reduction_tax_excl" value=($total_excl * $product.reduction_percent / 100) + $product.reduction_amount}
                        {assign var="reduction_tax_incl" value=($total_incl * $product.reduction_percent / 100) + ($product.reduction_amount * (1 + $product.tax_rate / 100))}
                        {assign var="total_excl_after" value=$total_excl - $reduction_tax_excl}
                        {assign var="total_incl_after" value=$total_incl - $reduction_tax_incl}
                    {else}
                        {assign var="total_excl_after" value=$total_excl}
                        {assign var="total_incl_after" value=$total_incl}
                    {/if}
                    <td class="total-excl-after">{displayPrice price=$total_excl_after currency=$quote->id_currency}</td>
                    <td class="total-incl-after">{displayPrice price=$total_incl_after currency=$quote->id_currency}</td>
                    <td>
                        <button
                            type="button"
                            class="btn btn-danger btn-sm remove-product-btn"
                            data-quote-product-id="{$product.id_quote_product|intval}"
                            data-id-quote="{$quote->id_quote|intval}"
                            title="{l s='Supprimer le produit' mod='myquotemanager'}"
                        >
                            <i class="icon-trash"></i>
                        </button>
                    </td>
                </tr>
                {/foreach}
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5">{l s='Transporteur' mod='myquotemanager'} : {$selected_carrier_name|escape:'html':'UTF-8'}</th>
                    <th colspan="3">{displayPrice price=$quote->total_shipping currency=$quote->id_currency} / {displayPrice price=$quote->total_shipping_wt currency=$quote->id_currency}</th>
                    <th colspan="3"></th>
                </tr>
                <tr>
                    <th colspan="5">{l s='Total des produits' mod='myquotemanager'}</th>
                    <th id="total-products-excl">{displayPrice price=$quote->total_products currency=$quote->id_currency}</th>
                    <th id="total-products-incl">{displayPrice price=$quote->total_products_wt currency=$quote->id_currency}</th>
                    <th colspan="3"></th>
                </tr>
                {if $quote->total_discount > 0 || $quote->total_discount_wt > 0}
                <tr style="background-color: #fff3cd;">
                    <th colspan="5">{l s='Réductions produits' mod='myquotemanager'}</th>
                    <th id="total-discount-excl" style="color: #d9534f;">-{displayPrice price=$quote->total_discount|default:0|floatval currency=$quote->id_currency}</th>
                    <th id="total-discount-incl" style="color: #d9534f;">-{displayPrice price=$quote->total_discount_wt|default:0|floatval currency=$quote->id_currency}</th>
                    <th colspan="3"></th>
                </tr>
                {/if}
                <tr class="quote-global-discount-row" style="background-color: #e8f4f8; border-top: 2px solid #0088cc; border-bottom: 2px solid #0088cc;">
                    <th colspan="5">
                        <i class="icon-tag"></i> {l s='Réduction globale du devis' mod='myquotemanager'}
                    </th>
                    <td style="padding: 8px;">
                        <div class="quote-reduction-editor">
                            <div class="quote-input-suffix">
                                <input type="number" id="global-reduction-percent" class="form-control input-sm"
                                       value="{$quote->global_reduction_percent|floatval}"
                                       min="0" max="100" step="0.01"
                                       data-original-value="{$quote->global_reduction_percent|floatval}"
                                       title="{l s='Réduction en pourcentage sur l’ensemble du devis (0-100%)' mod='myquotemanager'}">
                                <span class="quote-input-suffix__unit">%</span>
                            </div>
                            <input type="number" id="global-reduction-amount" class="form-control input-sm"
                                   value="{$quote->global_reduction_amount|floatval}"
                                   min="0" step="0.01"
                                   placeholder="€"
                                   data-original-value="{$quote->global_reduction_amount|floatval}"
                                   title="{l s='Réduction montant fixe sur l’ensemble du devis' mod='myquotemanager'}">
                            <button type="button" id="update-global-reduction-btn" class="btn btn-primary btn-xs quote-apply-btn"
                                    title="{l s='Appliquer la réduction globale' mod='myquotemanager'}">
                                <i class="icon-check"></i> {l s='Appliquer' mod='myquotemanager'}
                            </button>
                        </div>
                    </td>
                    <td id="global-discount-excl" style="color: #0088cc; font-weight: bold;">
                        -{displayPrice price=$quote->total_global_discount|default:0|floatval currency=$quote->id_currency}
                    </td>
                    <td id="global-discount-incl" style="color: #0088cc; font-weight: bold;">
                        -{displayPrice price=$quote->total_global_discount_wt|default:0|floatval currency=$quote->id_currency}
                    </td>
                    <td></td>
                </tr>
                <tr class="success">
                    <th colspan="5">{l s='Total du devis' mod='myquotemanager'}</th>
                    <th id="total-quote-excl">{displayPrice price=$quote->total_paid_tax_excl|default:0|floatval currency=$quote->id_currency}</th>
                    <th id="total-quote-incl">{displayPrice price=$quote->total_paid|default:0|floatval currency=$quote->id_currency}</th>
                    <th colspan="3"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div id="quote-sticky-summary" class="quote-sticky-summary">
    <div class="quote-sticky-summary__totals">
        <strong>{l s='Récapitulatif :' mod='myquotemanager'}</strong>
        <span>{l s='Lignes :' mod='myquotemanager'} <strong id="sticky-product-count">{if $quote_products}{count($quote_products)}{else}0{/if}</strong></span>
        <span>{l s='Transport :' mod='myquotemanager'} <strong id="sticky-total-shipping">{displayPrice price=$quote->total_shipping_wt currency=$quote->id_currency}</strong></span>
        <span>{l s='Total HT:' mod='myquotemanager'} <strong id="sticky-total-excl">{displayPrice price=$quote->total_paid_tax_excl currency=$quote->id_currency}</strong></span>
        <span>{l s='Total TTC:' mod='myquotemanager'} <strong id="sticky-total-incl">{displayPrice price=$quote->total_paid currency=$quote->id_currency}</strong></span>
    </div>
    <div class="quote-sticky-summary__actions">
        <a href="{$current_index}&token={$token}" class="btn btn-default btn-sm">
            <i class="icon-arrow-left"></i> {l s='Retour à la liste' mod='myquotemanager'}
        </a>
    </div>
</div>

</div>

<style>
.quote-workspace {
    max-width: 1440px;
    margin: 0 auto;
}
.quote-header {
    border-top: 3px solid #25b9d7;
}
.quote-header__identity {
    float: left;
}
.quote-header__identity h2 {
    display: inline-block;
    margin: 2px 10px 0 0;
    font-size: 24px;
}
.quote-header__eyebrow {
    display: block;
    color: #6c868e;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}
.quote-header__actions {
    float: right;
    padding-top: 9px;
}
.quote-status {
    display: inline-block;
    padding: 4px 9px;
    border-radius: 3px;
    color: #fff;
    font-size: 12px;
    font-weight: bold;
}
.quote-order-layout .panel,
.quote-workspace > .panel {
    border-radius: 4px;
}
.quote-side-card .panel-heading,
.quote-edit-card .panel-heading {
    font-weight: 600;
}
.quote-customer-name {
    margin: 0 0 5px;
}
.quote-customer-email {
    margin-bottom: 4px;
    overflow-wrap: anywhere;
}
.quote-meta-list > div {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}
.quote-meta-list > div:last-child {
    border-bottom: 0;
}
.quote-meta-list span {
    color: #6c868e;
}
.quote-message {
    margin-top: 0;
}
.quote-quantity-editor {
    min-width: 90px;
}
.quote-quantity-editor .product-quantity-input {
    text-align: right;
}
.quote-apply-btn {
    display: none;
    width: 100%;
    margin-top: 5px;
}
.quote-apply-btn.is-visible {
    display: block;
}
.quote-apply-btn:disabled {
    cursor: wait;
}
.quote-reduction-editor {
    min-width: 100px;
}
.quote-reduction-editor > .form-control,
.quote-reduction-editor > .quote-input-suffix {
    margin-bottom: 5px;
}
.quote-reduction-editor .form-control {
    text-align: right;
}
.quote-input-suffix {
    position: relative;
}
.quote-input-suffix .form-control {
    padding-right: 22px;
    -moz-appearance: textfield;
}
.quote-input-suffix .form-control::-webkit-outer-spin-button,
.quote-input-suffix .form-control::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.quote-input-suffix__unit {
    position: absolute;
    top: 50%;
    right: 8px;
    transform: translateY(-50%);
    color: #6c868e;
    pointer-events: none;
}
.quote-reduction-editor input.input-error {
    border-color: #d9534f;
    background-color: #fff8f8;
}
.quote-global-discount-row {
    transition: background-color 0.3s ease;
}
.quote-global-discount-row th,
.quote-global-discount-row td {
    vertical-align: middle !important;
}
.quote-global-discount-row th {
    color: #004080;
    font-weight: bold;
}
.quote-global-discount-row .input-group-sm {
    width: 100%;
}
.quote-global-discount-row input.form-control {
    background-color: #ffffff;
    border-color: #0088cc;
}
.quote-global-discount-row input.form-control:focus {
    border-color: #0066aa;
    box-shadow: 0 0 8px rgba(0, 136, 204, 0.3);
    background-color: #f0f8ff;
}
.quote-global-discount-row #update-global-reduction-btn {
    background-color: #0088cc;
    border-color: #0066aa;
    color: white;
}
.quote-global-discount-row #update-global-reduction-btn:hover {
    background-color: #0066aa;
    border-color: #004080;
}
.table th, .table td {
    vertical-align: middle !important;
}
.panel-heading-action {
    float: right;
    margin-top: -5px;
}
.quote-sticky-summary {
    position: sticky;
    bottom: 0;
    z-index: 20;
    margin-top: 15px;
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08);
}
.quote-sticky-summary__totals span {
    margin-left: 14px;
}
.quote-sticky-summary__actions .btn {
    margin-left: 6px;
}
.form-control.input-error {
    border-color: #d9534f;
}
@media (max-width: 991px) {
    .quote-header__identity,
    .quote-header__actions {
        float: none;
    }
    .quote-header__actions {
        margin-top: 12px;
    }
    .quote-sticky-summary {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    .quote-sticky-summary__totals span {
        margin-left: 0;
        margin-right: 10px;
        display: inline-block;
    }
    .quote-sticky-summary__actions .btn {
        margin-left: 0;
        margin-right: 6px;
        margin-top: 4px;
    }
}
</style>

<script>
$(document).ready(function() {
    var ajaxBaseUrl = '{$current_index|escape:'javascript':'UTF-8'}&token={$token|escape:'javascript':'UTF-8'}';
    var quoteId = {$quote->id_quote|intval};
    var currencySymbol = '{if isset($currency->sign)}{$currency->sign|escape:'javascript':'UTF-8'}{elseif isset($currency->symbol)}{$currency->symbol|escape:'javascript':'UTF-8'}{else}€{/if}';
    var lastRemovedProduct = null;
    var undoTimer = null;

    function normalizeSearchValue(value) {
        return String(value || '')
            .toLocaleLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    if ($.fn.select2) {
        $('#product-search').select2({
            width: '100%',
            placeholder: '{l s='Rechercher un produit par nom ou référence...' mod='myquotemanager' js=1}',
            allowClear: true,
            matcher: function(params, data) {
                if (!$.trim(params.term)) {
                    return data;
                }

                var searchTerms = normalizeSearchValue(params.term).split(/\s+/).filter(Boolean);
                var searchableText = normalizeSearchValue(data.text);
                var matches = searchTerms.every(function(term) {
                    return searchableText.indexOf(term) !== -1;
                });

                return matches ? data : null;
            }
        });

        $('#new-customer-search').select2({
            width: '100%',
            placeholder: '{l s='Rechercher un client par nom ou e-mail...' mod='myquotemanager' js=1}',
            allowClear: true,
            matcher: function(params, data) {
                if (!$.trim(params.term)) {
                    return data;
                }

                var searchTerms = normalizeSearchValue(params.term).split(/\s+/).filter(Boolean);
                var searchableText = normalizeSearchValue(data.text);
                var matches = searchTerms.every(function(term) {
                    return searchableText.indexOf(term) !== -1;
                });

                return matches ? data : null;
            }
        });
    }

    // Afficher/masquer le formulaire d'ajout
    $('#add-product-btn').on('click', function() {
        $('#add-product-form').toggle();
    });

    // Annuler l'ajout
    $('#cancel-add').on('click', function() {
        $('#add-product-form').hide();
        $('#product-add-form')[0].reset();
        $('#product-search').val('').trigger('change');
        $('#product-attribute-panel').hide();
        $('#product-attribute-select').val('');
        $('#id-product-attribute').val('0');
        $('#summary-base-excl, #summary-base-incl, #summary-final-excl, #summary-final-incl').text('0.00');
        $('#summary-adjustment').text('0');
        updateAvailableStock();
    });

    $('#open-convert-quote').on('click', function() {
        $('#convert-quote-panel').slideDown(150);
        $('html, body').animate({ldelim}scrollTop: $('#convert-quote-panel').offset().top - 20{rdelim}, 200);
    });

    $('#cancel-convert-quote').on('click', function() {
        $('#convert-quote-panel').slideUp(150);
    });

    $('#open-change-customer').on('click', function() {
        $('#change-customer-form').slideDown(150);
    });

    $('#cancel-change-customer').on('click', function() {
        $('#change-customer-form').slideUp(150);
        $('#new-customer-search').val('').trigger('change');
    });

    $('#change-customer-form').on('submit', function(e) {
        if (!$('#new-customer-search').val()) {
            e.preventDefault();
            showToast('{l s='Sélectionnez un client avant de confirmer.' mod='myquotemanager' js=1}', 'error');
            return;
        }
        if (!confirm('{l s='Rattacher ce devis à ce nouveau client ? L’adresse du devis sera réinitialisée.' mod='myquotemanager' js=1}')) {
            e.preventDefault();
        }
    });

    function computeAdjustedPrice(basePrice, adjustmentType, adjustmentValue) {
        if (adjustmentType === 'percent') {
            return basePrice * (1 + (adjustmentValue / 100));
        }

        return basePrice + adjustmentValue;
    }

    function applyPriceAdjustment() {
        var baseExcl = parseFloat($('#base-price-tax-excl').val() || 0);
        var baseIncl = parseFloat($('#base-price-tax-incl').val() || 0);
        var adjustmentType = $('#adjustment-type').val();
        var adjustmentValue = parseFloat($('#adjustment-value').val() || 0);

        if (baseExcl <= 0 && baseIncl <= 0) {
            return;
        }

        var nextExcl;
        var nextIncl;
        if (adjustmentType === 'percent') {
            var multiplier = 1 + (adjustmentValue / 100);
            nextExcl = baseExcl * multiplier;
            nextIncl = baseIncl * multiplier;
        } else {
            nextIncl = baseIncl + adjustmentValue;
            nextExcl = baseIncl > 0 ? nextIncl * (baseExcl / baseIncl) : 0;
        }

        $('#product-price-excl').val(Math.max(0, nextExcl).toFixed(2));
        $('#product-price-incl').val(Math.max(0, nextIncl).toFixed(2));

        var quantity = parseInt($('#product-add-form [name="quantity"]').val(), 10) || 0;
        var reductionPercent = parseFloat($('#product-reduction-percent-add').val()) || 0;
        var reductionAmount = parseFloat($('#product-reduction-amount-add').val()) || 0;
        var lineTotalExcl = Math.max(0, nextExcl) * quantity;
        var reductionTotal = reductionAmount + (lineTotalExcl * reductionPercent / 100);

        $('#summary-base-excl').text(baseExcl.toFixed(2));
        $('#summary-base-incl').text(baseIncl.toFixed(2));
        $('#summary-adjustment').text(adjustmentType === 'percent' ? adjustmentValue.toFixed(2) + '%' : adjustmentValue.toFixed(2));
        $('#summary-reduction').text(reductionTotal.toFixed(2));
        $('#summary-final-excl').text(Math.max(0, nextExcl).toFixed(2));
        $('#summary-final-incl').text(Math.max(0, nextIncl).toFixed(2));
    }

    function syncProductSelection() {
        var $product = $('#product-search');
        var $productOption = $product.find('option:selected');
        var hasCombinations = $productOption.data('has-combinations') === 1 || $productOption.data('has-combinations') === '1';
        var $attributePanel = $('#product-attribute-panel');
        var $attributeSelect = $('#product-attribute-select');

        $attributeSelect.find('option[data-id-product]').each(function() {
            $(this).toggle(String($(this).data('id-product')) === String($product.val()));
        });

        if (hasCombinations) {
            $attributePanel.show();

            // Pre-select the combination flagged as default in PrestaShop so the
            // user only needs to act when they want a different one.
            var $defaultOption = $attributeSelect.find('option[data-is-default="1"]').filter(function() {
                return String($(this).attr('data-id-product')) === String($product.val());
            }).first();
            if ($defaultOption.length) {
                $attributeSelect.val($defaultOption.val());
                $('#id-product-attribute').val($defaultOption.val());
                $('#base-price-tax-excl').val(parseFloat($defaultOption.data('price-tax-excl') || 0).toFixed(6));
                $('#base-price-tax-incl').val(parseFloat($defaultOption.data('price-tax-incl') || 0).toFixed(6));
                applyPriceAdjustment();
                return;
            }

            $attributeSelect.val('');
            $('#id-product-attribute').val('0');
            $('#base-price-tax-excl, #base-price-tax-incl').val('0');
            $('#product-price-excl, #product-price-incl').val('');
            return;
        }

        $attributePanel.hide();
        $attributeSelect.val('');
        $('#id-product-attribute').val('0');
        $('#base-price-tax-excl').val(parseFloat($productOption.data('price-tax-excl') || 0).toFixed(6));
        $('#base-price-tax-incl').val(parseFloat($productOption.data('price-tax-incl') || 0).toFixed(6));
        applyPriceAdjustment();
    }

    $('#product-attribute-select').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        $('#id-product-attribute').val(selectedOption.val() || '0');
        $('#base-price-tax-excl').val(parseFloat(selectedOption.data('price-tax-excl') || 0).toFixed(6));
        $('#base-price-tax-incl').val(parseFloat(selectedOption.data('price-tax-incl') || 0).toFixed(6));
        applyPriceAdjustment();
        updateAvailableStock();
        validateAddProductForm();
    });

    // Auto-remplir les prix quand on sélectionne un produit
    $('#product-search').on('change', function() {
        syncProductSelection();
        updateAvailableStock();
        validateAddProductForm();
    });

    $('#adjustment-type, #adjustment-value').on('change input', function() {
        applyPriceAdjustment();
    });

    function setFieldError($field, message) {
        var $error = $field.parent().find('.js-field-error').first();

        if (!message) {
            $field.removeClass('input-error');
            $error.hide().text('');
            return;
        }

        $field.addClass('input-error');
        $error.text(message).show();
    }

    function updateAvailableStock() {
        var $product = $('#product-search');
        var $quantity = $('#product-add-form [name="quantity"]');
        var $stockInfo = $('#available-stock-info');
        var $selectedOption = $('#product-attribute-panel').is(':visible')
            ? $('#product-attribute-select option:selected')
            : $product.find('option:selected');
        var stockQuantity = parseInt($selectedOption.attr('data-stock-quantity'), 10);

        if (!isFinite(stockQuantity) || !$product.val()) {
            $quantity.removeAttr('max');
            $stockInfo.hide().removeClass('alert-info alert-warning alert-danger');
            return null;
        }

        $quantity.attr('max', stockQuantity);
        if (stockQuantity > 0) {
            $stockInfo.removeClass('alert-warning alert-danger').addClass('alert-info')
                .text('{l s='Stock restant :' mod='myquotemanager' js=1} ' + stockQuantity)
                .show();
        } else {
            $stockInfo.removeClass('alert-info alert-warning').addClass('alert-danger')
                .text('{l s='Produit indisponible : stock épuisé.' mod='myquotemanager' js=1}')
                .show();
        }

        return stockQuantity;
    }

    function validateQuoteForm() {
        var $dateExp = $('#quote-inline-form [name="date_exp"]');
        var dateValue = ($dateExp.val() || '').trim();
        var isValid = true;

        if (dateValue !== '') {
            var today = new Date();
            var yyyy = today.getFullYear();
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var dd = String(today.getDate()).padStart(2, '0');
            var minDate = yyyy + '-' + mm + '-' + dd;

            if (dateValue < minDate) {
                setFieldError($dateExp, '{l s='La date d’expiration ne peut pas être passée.' mod='myquotemanager' js=1}');
                isValid = false;
            } else {
                setFieldError($dateExp, '');
            }
        } else {
            setFieldError($dateExp, '');
        }

        $('#save-quote-btn').prop('disabled', !isValid);
        return isValid;
    }

    function validateAddProductForm() {
        var isValid = true;
        var $product = $('#product-search');
        var $quantity = $('#product-add-form [name="quantity"]');
        var $priceExcl = $('#product-price-excl');
        var $priceIncl = $('#product-price-incl');
        var $adjustment = $('#adjustment-value');
        var $adjustmentType = $('#adjustment-type');

        if (!parseInt($product.val(), 10)) {
            setFieldError($product, '{l s='Sélectionnez un produit.' mod='myquotemanager' js=1}');
            isValid = false;
        } else {
            setFieldError($product, '');
        }

        var $attribute = $('#product-attribute-select');
        if ($('#product-attribute-panel').is(':visible') && !parseInt($attribute.val(), 10)) {
            setFieldError($attribute, '{l s='Sélectionnez une déclinaison.' mod='myquotemanager' js=1}');
            isValid = false;
        } else {
            setFieldError($attribute, '');
        }

        if (!parseInt($quantity.val(), 10) || parseInt($quantity.val(), 10) <= 0) {
            setFieldError($quantity, '{l s='La quantité doit être supérieure à 0.' mod='myquotemanager' js=1}');
            isValid = false;
        } else {
            var availableQuantity = updateAvailableStock();
            if (availableQuantity !== null && parseInt($quantity.val(), 10) > availableQuantity) {
                setFieldError($quantity, '{l s='La quantité ne peut pas dépasser le stock disponible.' mod='myquotemanager' js=1}');
                isValid = false;
            } else {
                setFieldError($quantity, '');
            }
        }

        if (isNaN(parseFloat($priceExcl.val())) || parseFloat($priceExcl.val()) < 0) {
            setFieldError($priceExcl, '{l s='Le prix HT doit être un nombre positif.' mod='myquotemanager' js=1}');
            isValid = false;
        } else {
            setFieldError($priceExcl, '');
        }

        if (isNaN(parseFloat($priceIncl.val())) || parseFloat($priceIncl.val()) < 0) {
            setFieldError($priceIncl, '{l s='Le prix TTC doit être un nombre positif.' mod='myquotemanager' js=1}');
            isValid = false;
        } else {
            setFieldError($priceIncl, '');
        }

        if (isNaN(parseFloat($adjustment.val()))) {
            setFieldError($adjustment, '{l s='La valeur d’ajustement doit être numérique.' mod='myquotemanager' js=1}');
            isValid = false;
        } else if ($adjustmentType.val() === 'percent' && parseFloat($adjustment.val()) <= -100) {
            setFieldError($adjustment, '{l s='L’ajustement en pourcentage doit être supérieur à -100.' mod='myquotemanager' js=1}');
            isValid = false;
        } else {
            setFieldError($adjustment, '');
        }

        var $reductionPercent = $('#product-reduction-percent-add');
        var $reductionAmount = $('#product-reduction-amount-add');
        var reductionPercentValue = parseFloat($reductionPercent.val());
        var reductionAmountValue = parseFloat($reductionAmount.val());

        if (isNaN(reductionPercentValue) || reductionPercentValue < 0 || reductionPercentValue > 100) {
            setFieldError($reductionPercent, '{l s='Le pourcentage de réduction doit être entre 0 et 100.' mod='myquotemanager' js=1}');
            isValid = false;
        } else {
            setFieldError($reductionPercent, '');
        }

        if (isNaN(reductionAmountValue) || reductionAmountValue < 0) {
            setFieldError($reductionAmount, '{l s='La réduction montant doit être positive.' mod='myquotemanager' js=1}');
            isValid = false;
        } else {
            setFieldError($reductionAmount, '');
        }

        var lineTotalExcl = (parseFloat($priceExcl.val()) || 0) * (parseInt($quantity.val(), 10) || 0);
        var reductionTotal = (reductionAmountValue || 0) + (lineTotalExcl * (reductionPercentValue || 0) / 100);
        if (lineTotalExcl > 0 && reductionTotal > lineTotalExcl) {
            setFieldError($reductionAmount, '{l s='La réduction ne peut pas dépasser 100% du prix du produit.' mod='myquotemanager' js=1}');
            isValid = false;
        }

        return isValid;
    }

    $('#quote-inline-form [name="date_exp"]').on('change input', function() {
        validateQuoteForm();
    });

    $('#product-search, #product-add-form [name="quantity"], #product-price-excl, #product-price-incl, #adjustment-value, #adjustment-type, #product-reduction-percent-add, #product-reduction-amount-add').on('change input', function() {
        applyPriceAdjustment();
        validateAddProductForm();
    });

    function showToast(message, type, options) {
        options = options || {};
        var $toast = $('#quote-toast');
        var className = 'alert-success';

        if (type === 'error') {
            className = 'alert-danger';
        } else if (type === 'warning') {
            className = 'alert-warning';
        }

        $toast.removeClass('alert-success alert-danger alert-info alert-warning')
            .addClass(className)
            .stop(true, true)
            .show();

        if (options.actionLabel && typeof options.onAction === 'function') {
            var html = '' +
                '<span class="toast-message"></span>' +
                '<button type="button" class="btn btn-default btn-xs pull-right toast-action-btn"></button>';
            $toast.html(html);
            $toast.find('.toast-message').text(message);
            $toast.find('.toast-action-btn')
                .text(options.actionLabel)
                .off('click')
                .on('click', function() {
                    options.onAction();
                    $toast.fadeOut(150);
                });
        } else {
            $toast.text(message);
        }

        var duration = typeof options.duration === 'number' ? options.duration : 2500;
        if (duration > 0) {
            setTimeout(function() {
                $toast.fadeOut(300);
            }, duration);
        }
    }

    function formatMoney(value) {
        var numeric = parseFloat(value || 0);
        return numeric.toFixed(2) + ' ' + currencySymbol;
    }

    function updateProductCount() {
        var count = $('#quote-products-table tbody tr').length;
        $('#sticky-product-count').text(count);
    }

    function updateTotals(totals) {
        if (!totals) {
            return;
        }

        var totalExcl = parseFloat(totals.total_paid_tax_excl || 0);
        var totalIncl = parseFloat(totals.total_paid || 0);

        if (totals.total_products !== undefined) {
            $('#total-products-excl').text(formatMoney(totals.total_products));
            $('#total-products-incl').text(formatMoney(totals.total_products_wt));
        }
        if (totals.total_discount !== undefined && $('#total-discount-excl').length) {
            $('#total-discount-excl').text('-' + formatMoney(totals.total_discount));
            $('#total-discount-incl').text('-' + formatMoney(totals.total_discount_wt));
        }
        if (totals.total_global_discount !== undefined) {
            $('#global-discount-excl').text('-' + formatMoney(totals.total_global_discount));
            $('#global-discount-incl').text('-' + formatMoney(totals.total_global_discount_wt));
        }

        $('#total-quote-excl').text(formatMoney(totalExcl));
        $('#total-quote-incl').text(formatMoney(totalIncl));
        $('#sticky-total-shipping').text(formatMoney(totals.total_shipping_wt || 0));
        $('#sticky-total-excl').text(formatMoney(totalExcl));
        $('#sticky-total-incl').text(formatMoney(totalIncl));
        updateProductCount();
    }

    function appendProductRow(product) {
        if (!product) {
            return;
        }

        var applyButtonHtml = '<i class="icon-check"></i> {l s='Appliquer' mod='myquotemanager' js=1}';
        var totalExcl = parseFloat(product.total_excl || 0).toFixed(2);
        var totalIncl = parseFloat(product.total_incl || 0).toFixed(2);
        var reductionPercent = parseFloat(product.reduction_percent || 0);
        var reductionAmount = parseFloat(product.reduction_amount || 0);
        var totalExclAfter = parseFloat(product.line_total_excl_after != null ? product.line_total_excl_after : totalExcl).toFixed(2);
        var totalInclAfter = parseFloat(product.line_total_incl_after != null ? product.line_total_incl_after : totalIncl).toFixed(2);
        var rowHtml = '' +
            '<tr data-quote-product-id="' + product.id_quote_product + '" data-id-product="' + product.id_product + '" data-id-product-attribute="' + (product.id_product_attribute || 0) + '" data-quantity="' + product.quantity + '" data-price-tax-excl="' + parseFloat(product.price_tax_excl || 0).toFixed(6) + '" data-price-tax-incl="' + parseFloat(product.price_tax_incl || 0).toFixed(6) + '">' +
                '<td>' + (product.name || ('Produit #' + product.id_product)) + (product.attributes ? ' <small class="text-muted">(' + product.attributes + ')</small>' : '') + '</td>' +
                '<td>' + (product.reference || 'N/A') + '</td>' +
                '<td><div class="quote-quantity-editor"><input type="number" class="form-control input-sm product-quantity-input" value="' + product.quantity + '" min="1" max="' + (product.maximum_quantity || product.quantity) + '" data-original-quantity="' + product.quantity + '"><button type="button" class="btn btn-primary btn-xs quote-apply-btn update-quantity-btn">' + applyButtonHtml + '</button></div></td>' +
                '<td>' + formatMoney(product.price_tax_excl || 0) + '</td>' +
                '<td>' + formatMoney(product.price_tax_incl || 0) + '</td>' +
                '<td class="total-excl">' + formatMoney(totalExcl) + '</td>' +
                '<td class="total-incl">' + formatMoney(totalIncl) + '</td>' +
                '<td>' +
                    '<div class="quote-reduction-editor">' +
                        '<div class="quote-input-suffix">' +
                            '<input type="number" class="form-control input-sm product-reduction-percent" value="' + reductionPercent + '" min="0" max="100" step="0.01" data-original-reduction-percent="' + reductionPercent + '">' +
                            '<span class="quote-input-suffix__unit">%</span>' +
                        '</div>' +
                        '<input type="number" class="form-control input-sm product-reduction-amount" value="' + reductionAmount + '" min="0" step="0.01" placeholder="' + currencySymbol + '" data-original-reduction-amount="' + reductionAmount + '">' +
                        '<button type="button" class="btn btn-primary btn-xs quote-apply-btn update-reduction-btn">' + applyButtonHtml + '</button>' +
                    '</div>' +
                '</td>' +
                '<td class="total-excl-after">' + formatMoney(totalExclAfter) + '</td>' +
                '<td class="total-incl-after">' + formatMoney(totalInclAfter) + '</td>' +
                '<td>' +
                    '<button type="button" class="btn btn-danger btn-sm remove-product-btn" data-quote-product-id="' + product.id_quote_product + '" data-id-quote="' + quoteId + '">' +
                        '<i class="icon-trash"></i>' +
                    '</button>' +
                '</td>' +
            '</tr>';

        var $tbody = $('#quote-products-table tbody');
        if ($tbody.length === 0) {
            window.location.reload();
            return;
        }

        $tbody.append(rowHtml);
        $('#quote-empty-products').hide();
        $('#quote-products-table').show();
        updateProductCount();
    }

    function snapshotProductFromRow($row) {
        return {
            id_product: parseInt($row.data('id-product'), 10),
            id_product_attribute: parseInt($row.data('id-product-attribute'), 10) || 0,
            quantity: parseInt($row.data('quantity'), 10),
            price_tax_excl: parseFloat($row.data('price-tax-excl') || 0),
            price_tax_incl: parseFloat($row.data('price-tax-incl') || 0),
            name: $.trim($row.children('td').eq(0).text()),
            reference: $.trim($row.children('td').eq(1).text())
        };
    }

    function undoRemoveProduct(snapshot) {
        if (!snapshot || !snapshot.id_product || !snapshot.quantity) {
            showToast('{l s='Impossible de restaurer le produit supprimé.' mod='myquotemanager' js=1}', 'error');
            return;
        }

        $.ajax({
            url: ajaxBaseUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'addProductInline',
                id_quote: quoteId,
                id_product: snapshot.id_product,
                id_product_attribute: snapshot.id_product_attribute,
                quantity: snapshot.quantity,
                price_tax_excl: snapshot.price_tax_excl,
                price_tax_incl: snapshot.price_tax_incl,
                adjustment_type: 'amount',
                adjustment_value: 0,
                base_price_tax_excl: snapshot.price_tax_excl,
                base_price_tax_incl: snapshot.price_tax_incl
            },
            success: function(response) {
                if (!response || !response.success) {
                    showToast(response && response.message ? response.message : '{l s='Impossible de restaurer le produit.' mod='myquotemanager' js=1}', 'error');
                    return;
                }

                appendProductRow(response.product);
                updateTotals(response.totals);
                showToast('{l s='Suppression annulée.' mod='myquotemanager' js=1}', 'success');
            },
            error: function() {
                showToast('{l s='Erreur technique lors de la restauration du produit.' mod='myquotemanager' js=1}', 'error');
            }
        });
    }

    $('#product-add-form').on('submit', function(e) {
        e.preventDefault();

        if (!validateAddProductForm()) {
            showToast('{l s='Corrigez les champs signalés avant d’ajouter le produit.' mod='myquotemanager' js=1}', 'error');
            return;
        }

        var $submitBtn = $(this).find('button[type="submit"]');
        var initialBtnHtml = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="icon-refresh icon-spin"></i> {l s='Ajout en cours...' mod='myquotemanager'}');

        var payload = $(this).serializeArray();
        payload.push({ldelim}name: 'ajax', value: 1{rdelim});
        payload.push({ldelim}name: 'action', value: 'addProductInline'{rdelim});
        payload.push({ldelim}name: 'id_quote', value: quoteId{rdelim});

        $.ajax({
            url: ajaxBaseUrl,
            type: 'POST',
            dataType: 'json',
            data: $.param(payload),
            success: function(response) {
                if (!response || !response.success) {
                    showToast(response && response.message ? response.message : 'Erreur lors de l\'ajout', 'error');
                    return;
                }

                appendProductRow(response.product);
                updateTotals(response.totals);
                showToast(response.message || 'Produit ajouté', 'success');
                $('#product-search option:selected').attr('data-stock-quantity', response.stock_quantity);
                $('#product-add-form [name="quantity"]').val('1');
                $('#adjustment-value').val('0');
                $('#product-reduction-percent-add').val('0');
                $('#product-reduction-amount-add').val('0');
                applyPriceAdjustment();
                updateAvailableStock();
            },
            error: function(xhr) {
                var errorMsg = 'Erreur technique lors de l\'ajout produit';

                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr && xhr.responseText) {
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed && parsed.message) {
                            errorMsg = parsed.message;
                        }
                    } catch (e) {
                        // Keep generic message when response is not JSON.
                    }
                }

                showToast(errorMsg, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(initialBtnHtml);
            }
        });
    });

    var applyButtonLabel = '<i class="icon-check"></i> {l s='Appliquer' mod='myquotemanager' js=1}';

    function valueChanged($input, originalAttr) {
        var current = parseFloat($input.val());
        var original = parseFloat($input.attr(originalAttr)) || 0;

        if (isNaN(current)) {
            return original !== 0 || $.trim($input.val()) !== '';
        }

        return Math.abs(current - original) > 0.000001;
    }

    function refreshQuantityApply($row) {
        $row.find('.update-quantity-btn').toggleClass('is-visible', valueChanged($row.find('.product-quantity-input'), 'data-original-quantity'));
    }

    function refreshReductionApply($row) {
        var changed = valueChanged($row.find('.product-reduction-percent'), 'data-original-reduction-percent')
            || valueChanged($row.find('.product-reduction-amount'), 'data-original-reduction-amount');
        $row.find('.update-reduction-btn').toggleClass('is-visible', changed);
    }

    function refreshGlobalApply() {
        var changed = valueChanged($('#global-reduction-percent'), 'data-original-value')
            || valueChanged($('#global-reduction-amount'), 'data-original-value');
        $('#update-global-reduction-btn').toggleClass('is-visible', changed);
    }

    function updateProductQuantity($row) {
        var $input = $row.find('.product-quantity-input');
        var $button = $row.find('.update-quantity-btn');
        var quantity = parseInt($input.val(), 10);
        var maximumQuantity = parseInt($input.attr('max'), 10);
        var quoteProductId = parseInt($row.data('quote-product-id'), 10);

        if (!quantity || quantity < 1) {
            $input.addClass('input-error').focus();
            showToast('{l s='La quantité doit être supérieure à 0.' mod='myquotemanager' js=1}', 'error');
            return;
        }

        if (maximumQuantity && quantity > maximumQuantity) {
            $input.addClass('input-error').focus();
            showToast('{l s='La quantité ne peut pas dépasser le stock disponible.' mod='myquotemanager' js=1}', 'error');
            return;
        }

        $input.removeClass('input-error');
        $button.prop('disabled', true).html('<i class="icon-refresh icon-spin"></i>');

        $.ajax({
            url: ajaxBaseUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'updateProductQuantityInline',
                id_quote: quoteId,
                id_quote_product: quoteProductId,
                quantity: quantity
            },
            success: function(response) {
                if (!response || !response.success) {
                    if (response && response.maximum_quantity) {
                        $input.attr('max', response.maximum_quantity);
                    }
                    $input.addClass('input-error');
                    showToast(response && response.message ? response.message : '{l s='Impossible de mettre à jour la quantité.' mod='myquotemanager' js=1}', 'error');
                    return;
                }

                $row.attr('data-quantity', response.quantity);
                $input.val(response.quantity)
                    .attr('data-original-quantity', response.quantity)
                    .attr('max', response.maximum_quantity);
                $row.find('.total-excl').text(formatMoney(response.line_total_excl));
                $row.find('.total-incl').text(formatMoney(response.line_total_incl));
                updateTotals(response.totals);
                showToast(response.message, 'success');
            },
            error: function() {
                showToast('{l s='Erreur technique lors de la mise à jour de la quantité.' mod='myquotemanager' js=1}', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html(applyButtonLabel);
                refreshQuantityApply($row);
            }
        });
    }

    function updateProductReduction($row) {
        var $percentInput = $row.find('.product-reduction-percent');
        var $amountInput = $row.find('.product-reduction-amount');
        var $button = $row.find('.update-reduction-btn');
        var reductionPercent = parseFloat($percentInput.val()) || 0;
        var reductionAmount = parseFloat($amountInput.val()) || 0;
        var quoteProductId = parseInt($row.data('quote-product-id'), 10);

        // Validate inputs
        if (reductionPercent < 0 || reductionPercent > 100) {
            $percentInput.addClass('input-error').focus();
            showToast('{l s='Le pourcentage de réduction doit être entre 0 et 100.' mod='myquotemanager' js=1}', 'error');
            return;
        }

        if (reductionAmount < 0) {
            $amountInput.addClass('input-error').focus();
            showToast('{l s='La réduction montant doit être positive.' mod='myquotemanager' js=1}', 'error');
            return;
        }

        $percentInput.removeClass('input-error');
        $amountInput.removeClass('input-error');
        $button.prop('disabled', true).html('<i class="icon-refresh icon-spin"></i>');

        $.ajax({
            url: ajaxBaseUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'updateProductReductionInline',
                id_quote: quoteId,
                id_quote_product: quoteProductId,
                reduction_percent: reductionPercent,
                reduction_amount: reductionAmount
            },
            success: function(response) {
                if (!response || !response.success) {
                    $percentInput.addClass('input-error');
                    $amountInput.addClass('input-error');
                    showToast(response && response.message ? response.message : '{l s='Impossible de mettre à jour la réduction.' mod='myquotemanager' js=1}', 'error');
                    return;
                }

                // Update input values
                $percentInput.val(response.reduction_percent).attr('data-original-reduction-percent', response.reduction_percent);
                $amountInput.val(response.reduction_amount).attr('data-original-reduction-amount', response.reduction_amount);

                // Update display of totals after reduction
                $row.find('.total-excl-after').text(formatMoney(response.line_total_excl_after));
                $row.find('.total-incl-after').text(formatMoney(response.line_total_incl_after));

                updateTotals(response.totals);
                showToast(response.message, 'success');
            },
            error: function() {
                showToast('{l s='Erreur technique lors de la mise à jour de la réduction.' mod='myquotemanager' js=1}', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html(applyButtonLabel);
                refreshReductionApply($row);
            }
        });
    }

    function updateGlobalReduction() {
        var $percentInput = $('#global-reduction-percent');
        var $amountInput = $('#global-reduction-amount');
        var $button = $('#update-global-reduction-btn');
        var reductionPercent = parseFloat($percentInput.val()) || 0;
        var reductionAmount = parseFloat($amountInput.val()) || 0;

        // Validate inputs
        if (reductionPercent < 0 || reductionPercent > 100) {
            $percentInput.addClass('input-error').focus();
            showToast('{l s='Le pourcentage de réduction doit être entre 0 et 100.' mod='myquotemanager' js=1}', 'error');
            return;
        }

        if (reductionAmount < 0) {
            $amountInput.addClass('input-error').focus();
            showToast('{l s='La réduction montant doit être positive.' mod='myquotemanager' js=1}', 'error');
            return;
        }

        $percentInput.removeClass('input-error');
        $amountInput.removeClass('input-error');
        $button.prop('disabled', true).html('<i class="icon-refresh icon-spin"></i> {l s='Traitement...' mod='myquotemanager' js=1}');

        $.ajax({
            url: ajaxBaseUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'updateGlobalReductionInline',
                id_quote: quoteId,
                global_reduction_percent: reductionPercent,
                global_reduction_amount: reductionAmount
            },
            success: function(response) {
                if (!response || !response.success) {
                    $percentInput.addClass('input-error');
                    $amountInput.addClass('input-error');
                    showToast(response && response.message ? response.message : '{l s='Impossible de mettre à jour la réduction globale.' mod='myquotemanager' js=1}', 'error');
                    return;
                }

                // Update input values
                $percentInput.val(response.global_reduction_percent).attr('data-original-value', response.global_reduction_percent);
                $amountInput.val(response.global_reduction_amount).attr('data-original-value', response.global_reduction_amount);

                // Update global discount display
                $('#global-discount-excl').text('-' + formatMoney(response.global_discount_excl));
                $('#global-discount-incl').text('-' + formatMoney(response.global_discount_incl));

                // Update global totals
                $('#total-quote-excl').text(formatMoney(response.totals.total_paid_tax_excl));
                $('#total-quote-incl').text(formatMoney(response.totals.total_paid));

                updateTotals(response.totals);
                showToast(response.message, 'success');
            },
            error: function() {
                showToast('{l s='Erreur technique lors de la mise à jour de la réduction globale.' mod='myquotemanager' js=1}', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html(applyButtonLabel);
                refreshGlobalApply();
            }
        });
    }

    $(document).on('input change', '.product-quantity-input', function() {
        refreshQuantityApply($(this).closest('tr'));
    });

    $(document).on('input change', '.product-reduction-percent, .product-reduction-amount', function() {
        refreshReductionApply($(this).closest('tr'));
    });

    $(document).on('input change', '#global-reduction-percent, #global-reduction-amount', function() {
        refreshGlobalApply();
    });

    $(document).on('click', '.update-quantity-btn', function() {
        updateProductQuantity($(this).closest('tr'));
    });

    $(document).on('keydown', '.product-quantity-input', function(event) {
        if (event.which === 13) {
            event.preventDefault();
            updateProductQuantity($(this).closest('tr'));
        }
    });

    $(document).on('click', '.update-reduction-btn', function() {
        updateProductReduction($(this).closest('tr'));
    });

    $(document).on('keydown', '.product-reduction-percent, .product-reduction-amount', function(event) {
        if (event.which === 13) {
            event.preventDefault();
            updateProductReduction($(this).closest('tr'));
        }
    });

    $(document).on('click', '#update-global-reduction-btn', function() {
        updateGlobalReduction();
    });

    $(document).on('keydown', '#global-reduction-percent, #global-reduction-amount', function(event) {
        if (event.which === 13) {
            event.preventDefault();
            updateGlobalReduction();
        }
    });

    $(document).on('click', '.remove-product-btn', function() {
        if (!confirm('{l s='Supprimer ce produit du devis ?' mod='myquotemanager' js=1}')) {
            return;
        }

        var $btn = $(this);
        var quoteProductId = parseInt($btn.data('quote-product-id'), 10);
        var $row = $btn.closest('tr');
        var removedSnapshot = snapshotProductFromRow($row);
        $btn.prop('disabled', true).html('<i class="icon-refresh icon-spin"></i>');

        $.ajax({
            url: ajaxBaseUrl,
            type: 'POST',
            dataType: 'json',
            data: {ldelim}
                ajax: 1,
                action: 'removeProductInline',
                id_quote: quoteId,
                id_quote_product: quoteProductId
            {rdelim},
            success: function(response) {
                if (!response || !response.success) {
                    showToast(response && response.message ? response.message : 'Erreur lors de la suppression', 'error');
                    return;
                }

                $row.remove();
                updateTotals(response.totals);
                if (!$('#quote-products-table tbody tr').length) {
                    $('#quote-products-table').hide();
                    $('#quote-empty-products').show();
                }
                lastRemovedProduct = removedSnapshot;

                if (undoTimer) {
                    clearTimeout(undoTimer);
                }

                showToast(response.message || 'Produit supprimé', 'warning', {
                    actionLabel: '{l s='Undo' mod='myquotemanager' js=1}',
                    duration: 8000,
                    onAction: function() {
                        var snapshot = lastRemovedProduct;
                        lastRemovedProduct = null;
                        undoRemoveProduct(snapshot);
                    }
                });

                undoTimer = setTimeout(function() {
                    lastRemovedProduct = null;
                }, 8000);
            },
            error: function(xhr) {
                var errorMsg = 'Erreur technique lors de la suppression produit';

                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr && xhr.responseText) {
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed && parsed.message) {
                            errorMsg = parsed.message;
                        }
                    } catch (e) {
                        // Keep generic message when response is not JSON.
                    }
                }

                showToast(errorMsg, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="icon-trash"></i>');
            }
        });
    });

    $('#quote-inline-form').on('submit', function(e) {
        if (!validateQuoteForm()) {
            e.preventDefault();
            showToast('{l s='Corrigez les champs signalés avant d’enregistrer le devis.' mod='myquotemanager' js=1}', 'error');
        }
    });

    validateQuoteForm();
    validateAddProductForm();
    updateProductCount();
});
</script>
