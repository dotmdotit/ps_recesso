<div class="row">
  <div class="col-lg-8">
    <div class="panel">
      <div class="panel-heading">
        <i class="icon-undo"></i> Dichiarazione di recesso #{$dmr_request.id|intval}
      </div>
      <table class="table">
        <tbody>
          <tr><th style="width:220px">Data e ora di invio</th><td><strong>{$dmr_request.datetime|escape:'html':'UTF-8'}</strong></td></tr>
          <tr>
            <th>Ordine</th>
            <td>
              {if $dmr_request.order_link}<a href="{$dmr_request.order_link|escape:'html':'UTF-8'}">{$dmr_request.reference|escape:'html':'UTF-8'}</a>{else}{$dmr_request.reference|escape:'html':'UTF-8'}{/if}
              {if $dmr_request.order_date} · del {$dmr_request.order_date|escape:'html':'UTF-8'}{/if}
              {if !$dmr_request.order_link}
                <div class="alert alert-warning" style="margin:8px 0 0">Nessun ordine trovato con questo numero e questa email: la dichiarazione è stata inviata dal modulo pubblico e va verificata manualmente.</div>
              {/if}
            </td>
          </tr>
          <tr><th>Consumatore</th><td>{$dmr_request.name|escape:'html':'UTF-8'}</td></tr>
          <tr><th>Email per la ricevuta</th><td><a href="mailto:{$dmr_request.email|escape:'html':'UTF-8'}">{$dmr_request.email|escape:'html':'UTF-8'}</a></td></tr>
          <tr>
            <th>Prodotti</th>
            <td>
              {if $dmr_request.whole_order}<span class="label label-info">Intero ordine</span>{/if}
              {if $dmr_request.items}
                <ul style="margin:6px 0 0;padding-left:18px">
                  {foreach $dmr_request.items as $item}
                    <li>{$item|escape:'html':'UTF-8'}</li>
                  {/foreach}
                </ul>
              {/if}
            </td>
          </tr>
          <tr><th>Motivo / informazioni aggiuntive</th><td>{if $dmr_request.reason}{$dmr_request.reason|escape:'html':'UTF-8'|nl2br nofilter}{else}<span class="text-muted">non indicato</span>{/if}</td></tr>
          <tr><th>IP</th><td>{$dmr_request.ip|escape:'html':'UTF-8'}</td></tr>
          <tr><th>Browser</th><td><small>{$dmr_request.user_agent|escape:'html':'UTF-8'}</small></td></tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-4">
    <form class="panel" method="post" action="{$dmr_form_action|escape:'html':'UTF-8'}">
      <div class="panel-heading"><i class="icon-flag"></i> Stato</div>
      <div class="form-group">
        <select name="dmr_status" class="form-control">
          {foreach $dmr_statuses as $key => $label}
            <option value="{$key|escape:'html':'UTF-8'}"{if $key == $dmr_request.status} selected{/if}>{$label|escape:'html':'UTF-8'}</option>
          {/foreach}
        </select>
      </div>
      <input type="hidden" name="id_dotmrecesso_request" value="{$dmr_request.id|intval}">
      <p class="help-block">Ultimo aggiornamento: {$dmr_request.updated|escape:'html':'UTF-8'}</p>
      <div class="panel-footer">
        <button type="submit" name="submitDmrStatus" class="btn btn-default pull-right"><i class="process-icon-save"></i> Salva</button>
        <a href="{$dmr_back|escape:'html':'UTF-8'}" class="btn btn-default"><i class="process-icon-back"></i> Elenco</a>
      </div>
    </form>
    <div class="alert alert-info">
      La ricevuta con contenuto, data e ora è stata inviata automaticamente al cliente. Il rimborso va effettuato entro 14 giorni dalla ricezione della dichiarazione (puoi trattenerlo finché non ricevi i prodotti o la prova della spedizione).
    </div>
  </div>
</div>
