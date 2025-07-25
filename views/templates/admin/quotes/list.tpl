{*
* Template liste des devis - MyQuoteManager 
* @author Votre équipe
* @version 1.0.0
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-file-text"></i>
        {l s='Gestion des devis' mod='myquotemanager'}
        <span class="badge">{if isset($total_quotes)}{$total_quotes|intval}{else}0{/if}</span>
        
        <div class="panel-heading-action">
            <a href="{$current_url|escape:'html':'UTF-8'}&addquote" class="btn btn-default">
                <i class="icon-plus"></i>
                {l s='Nouveau devis' mod='myquotemanager'}
            </a>
        </div>
    </div>
    
    {if isset($quotes) && $quotes|count > 0}
        <div class="table-responsive-row clearfix">
            <table class="table">
                <thead>
                    <tr class="nodrag nodrop">
                        <th class="center">
                            <span class="title_box">
                                {l s='ID' mod='myquotemanager'}
                            </span>
                        </th>
                        <th class="">
                            <span class="title_box">
                                {l s='Référence' mod='myquotemanager'}
                            </span>
                        </th>
                        <th class="">
                            <span class="title_box">
                                {l s='Client' mod='myquotemanager'}
                            </span>
                        </th>
                        <th class="center">
                            <span class="title_box">
                                {l s='Statut' mod='myquotemanager'}
                            </span>
                        </th>
                        <th class="text-right">
                            <span class="title_box">
                                {l s='Total TTC' mod='myquotemanager'}
                            </span>
                        </th>
                        <th class="center">
                            <span class="title_box">
                                {l s='Date création' mod='myquotemanager'}
                            </span>
                        </th>
                        <th class="center">
                            <span class="title_box">
                                {l s='Date expiration' mod='myquotemanager'}
                            </span>
                        </th>
                        <th class="text-right">
                            <span class="title_box">
                                {l s='Actions' mod='myquotemanager'}
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$quotes item=quote}
                    <tr>
                        <td class="center">
                            <strong>{$quote.id_quote|intval}</strong>
                        </td>
                        <td class="">
                            <a href="{if isset($quote.edit_link)}{$quote.edit_link|escape:'html':'UTF-8'}{else}#{/if}" 
                               class="text-decoration-none">
                                <strong>{if isset($quote.reference)}{$quote.reference|escape:'html':'UTF-8'}{else}N/A{/if}</strong>
                            </a>
                        </td>
                        <td class="">
                            {if isset($quote.customer_name)}
                                {$quote.customer_name|escape:'html':'UTF-8'}
                            {else}
                                <em class="text-muted">{l s='Client inconnu' mod='myquotemanager'}</em>
                            {/if}
                        </td>
                        <td class="center">
                            <span class="badge badge-{if isset($quote.status_color)}{$quote.status_color|escape:'html':'UTF-8'}{else}default{/if}">
                                {if isset($quote.status_name)}
                                    {$quote.status_name|escape:'html':'UTF-8'}
                                {else}
                                    {l s='Statut inconnu' mod='myquotemanager'}
                                {/if}
                            </span>
                        </td>
                        <td class="text-right">
                            <strong>
                                {if isset($quote.total_paid_tax_incl)}
                                    {$quote.total_paid_tax_incl|number_format:2:',':' '} €
                                {else}
                                    0,00 €
                                {/if}
                            </strong>
                        </td>
                        <td class="center">
                            {if isset($quote.date_add)}
                                {$quote.date_add|date_format:'%d/%m/%Y %H:%M'}
                            {else}
                                N/A
                            {/if}
                        </td>
                        <td class="center">
                            {if isset($quote.date_expired) && $quote.date_expired != '0000-00-00 00:00:00'}
                                <span class="{if isset($quote.is_expired) && $quote.is_expired}text-danger{else}text-success{/if}">
                                    {$quote.date_expired|date_format:'%d/%m/%Y'}
                                </span>
                            {else}
                                <em class="text-muted">{l s='Aucune' mod='myquotemanager'}</em>
                            {/if}
                        </td>
                        <td class="text-right">
                            <div class="btn-group">
                                <a href="{if isset($quote.view_link)}{$quote.view_link|escape:'html':'UTF-8'}{else}#{/if}" 
                                   class="btn btn-default" 
                                   title="{l s='Voir le devis' mod='myquotemanager'}">
                                    <i class="icon-eye"></i>
                                </a>
                                <a href="{if isset($quote.edit_link)}{$quote.edit_link|escape:'html':'UTF-8'}{else}#{/if}" 
                                   class="btn btn-default" 
                                   title="{l s='Modifier le devis' mod='myquotemanager'}">
                                    <i class="icon-edit"></i>
                                </a>
                                {if isset($quote.can_convert) && $quote.can_convert}
                                <a href="{if isset($quote.convert_link)}{$quote.convert_link|escape:'html':'UTF-8'}{else}#{/if}" 
                                   class="btn btn-success" 
                                   title="{l s='Convertir en commande' mod='myquotemanager'}"
                                   onclick="return confirm('{l s='Confirmez-vous la conversion de ce devis en commande ?' mod='myquotemanager'}');">
                                    <i class="icon-shopping-cart"></i>
                                </a>
                                {/if}
                            </div>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        
        {* Statistiques en bas *}
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-info">
                    <i class="icon-info-circle"></i>
                    {l s='Total des devis afichés' mod='myquotemanager'} : <strong>{$quotes|count}</strong>
                    {if isset($total_amount)}
                        | {l s='Montant total' mod='myquotemanager'} : <strong>{$total_amount|number_format:2:',':' '} €</strong>
                    {/if}
                </div>
            </div>
        </div>
        
    {else}
        <div class="alert alert-warning">
            <i class="icon-warning"></i>
            {l s='Aucun devis trouvé.' mod='myquotemanager'}
            <br><br>
            <a href="{$current_url|escape:'html':'UTF-8'}&addquote" class="btn btn-primary">
                <i class="icon-plus"></i>
                {l s='Créer le premier devis' mod='myquotemanager'}
            </a>
        </div>
    {/if}
</div>

{* Styles CSS spécifiques *}
<style>
.badge-draft { background-color: #6c757d; }
.badge-pending { background-color: #ffc107; color: #000; }
.badge-approved { background-color: #28a745; }
.badge-rejected { background-color: #dc3545; }
.badge-expired { background-color: #e83e8c; }
.badge-converted { background-color: #17a2b8; }

.text-decoration-none { text-decoration: none; }
.text-decoration-none:hover { text-decoration: underline; }
</style>
