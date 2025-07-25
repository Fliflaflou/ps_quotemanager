<div class="panel">
    <div class="panel-heading clearfix">
        <div class="pull-left">
            <h3>
                <i class="icon-file-text"></i> 
                Devis #{$quote_data.quote->reference}
                {assign var="status" value=QuoteStatus::getQuoteStatusesById($quote_data.quote->id_quote_status, $language.id)}
                <span class="label" style="background-color: {if $status.color}{$status.color}{else}#ccc{/if};">
                    {$status.name}
                </span>
            </h3>
        </div>
        
        <div class="pull-right">
            <select class="form-control" id="quick-status-change" style="display: inline-block; width: auto;">
                {foreach $quote_statuses as $status}
                    <option value="{$status.id_quote_status}" 
                            {if $status.id_quote_status == $quote_data.quote->id_quote_status}selected{/if}>
                        {$status.name}
                    </option>
                {/foreach}
            </select>
            
            <button class="btn btn-default" onclick="duplicateQuote({$quote_data.quote->id})">
                <i class="icon-copy"></i> Dupliquer
            </button>
            
            {if $quote_data.can_convert}
            <button class="btn btn-success" onclick="convertToOrder({$quote_data.quote->id})">
                <i class="icon-shopping-cart"></i> → Commande
            </button>
            {/if}
        </div>
    </div>
    
    <div class="panel-body">
        
        {if $quote_data.is_expired}
        <div class="alert alert-danger">
            <i class="icon-time"></i> <strong>DEVIS EXPIRÉ</strong>
        </div>
        {/if}
        
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4><i class="icon-user"></i> Client</h4>
                    </div>
                    <div class="panel-body">
                        {if $quote_data.quote->id_customer}
                            {assign var="customer" value=new Customer($quote_data.quote->id_customer)}
                            <p>
                                <strong>{$customer->firstname} {$customer->lastname}</strong><br>
                                📧 {$customer->email}
                            </p>
                        {else}
                            <p>Client anonyme</p>
                        {/if}
                        
                        {if $quote_data.customer_addresses.delivery}
                        <h5>📍 Livraison:</h5>
                        <address>
                            {$quote_data.customer_addresses.delivery->firstname} {$quote_data.customer_addresses.delivery->lastname}<br>
                            {$quote_data.customer_addresses.delivery->address1}<br>
                            {$quote_data.customer_addresses.delivery->postcode} {$quote_data.customer_addresses.delivery->city}
                        </address>
                        {/if}
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4><i class="icon-calculator"></i> Totaux</h4>
                    </div>
                    <div class="panel-body">
                        <table class="table table-condensed">
                            <tr>
                                <td>Sous-total HT:</td>
                                <td class="text-right">{Tools::displayPrice($quote_data.quote->total_products)}</td>
                            </tr>
                            <tr>
                                <td>Sous-total TTC:</td>
                                <td class="text-right">{Tools::displayPrice($quote_data.quote->total_products_wt)}</td>
                            </tr>
                            {if $quote_data.quote->total_discount > 0}
                            <tr>
                                <td>Remise:</td>
                                <td class="text-right text-danger">-{Tools::displayPrice($quote_data.quote->total_discount_wt)}</td>
                            </tr>
                            {/if}
                            <tr class="info">
                                <th>TOTAL TTC:</th>
                                <th class="text-right h4">{Tools::displayPrice($quote_data.quote->total_paid)}</th>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4><i class="icon-calendar"></i> Dates</h4>
                    </div>
                    <div class="panel-body">
                        <p>
                            <strong>Créé:</strong> {dateFormat date=$quote_data.quote->date_add full=1}<br>
                            <strong>Expire:</strong> 
                            <span class="{if $quote_data.is_expired}text-danger{/if}">
                                {dateFormat date=$quote_data.quote->valid_until full=1}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="icon-list"></i> Produits ({count($quote_data.products)})</h4>
            </div>
            <div class="panel-body">
                {if $quote_data.products}
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-right">Prix unit.</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $quote_data.products as $product}
                        <tr>
                            <td>
                                <strong>{$product.name}</strong>
                                {if $product.reference}<br><small>Réf: {$product.reference}</small>{/if}
                            </td>
                            <td class="text-center">{$product.quantity}</td>
                            <td class="text-right">{Tools::displayPrice($product.unit_price_tax_incl)}</td>
                            <td class="text-right"><strong>{Tools::displayPrice($product.total_price_tax_incl)}</strong></td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
                {else}
                <p class="alert alert-info">Aucun produit dans ce devis</p>
                {/if}
            </div>
        </div>
        
        {* 📝 NOTES *}
        {if $quote_data.quote->notes}
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="icon-comment"></i> Notes</h4>
            </div>
            <div class="panel-body">
                <div class="well">{$quote_data.quote->notes|nl2br}</div>
            </div>
        </div>
        {/if}
    </div>
    
    <div class="panel-footer">
        <a href="{$current_index}&token={$admin_token}" class="btn btn-default">
            <i class="icon-arrow-left"></i> Retour
        </a>
        
        <div class="pull-right">
            <a href="{$current_index}&id_quote={$quote_data.quote->id}&updatequote&token={$admin_token}" 
               class="btn btn-primary">
                <i class="icon-edit"></i> Modifier
            </a>
        </div>
    </div>
</div>
