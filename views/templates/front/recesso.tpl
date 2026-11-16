{extends file=$dmr_layout}

{block name='page_title'}
  {$dmr.t.page_title|escape:'html':'UTF-8'}
{/block}

{block name='page_content'}
<div class="dmr" id="dotmrecesso"{if $dmr.css_vars} style="{$dmr.css_vars|escape:'html':'UTF-8'}"{/if}>

  {if $dmr.step != 'done'}
    <div class="dmr-intro">
      <span class="dmr-kicker">{$dmr.t.page_kicker|escape:'html':'UTF-8'}</span>
      <p>{$dmr.intro|escape:'html':'UTF-8'}</p>
    </div>
  {/if}

  {if $dmr.errors}
    <div class="dmr-alert dmr-alert-error" role="alert">
      {foreach $dmr.errors as $err}<p>{$err|escape:'html':'UTF-8'}</p>{/foreach}
    </div>
  {/if}

  {* ---------- clienti registrati: elenco ordini ---------- *}
  {if $dmr.step == 'list'}
    <div class="dmr-card">
      <h2 class="dmr-title">{$dmr.t.orders_title|escape:'html':'UTF-8'}</h2>
      {if $dmr.orders}
        <ul class="dmr-orders">
          {foreach $dmr.orders as $o}
            <li class="dmr-order{if !$o.eligible} is-closed{/if}">
              <div class="dmr-order-info">
                <strong>{$o.title|escape:'html':'UTF-8'}</strong>
                <span>{$o.placed|escape:'html':'UTF-8'} · {$o.total|escape:'html':'UTF-8'}</span>
                <span class="dmr-order-state">{$o.label|escape:'html':'UTF-8'}</span>
                {if $o.requested}<span class="dmr-order-requested">{$o.requested|escape:'html':'UTF-8'}</span>{/if}
              </div>
              {if $o.eligible}
                <a class="dmr-btn" href="{$o.url|escape:'html':'UTF-8'}">{$dmr.t.btn_withdraw|escape:'html':'UTF-8'}</a>
              {/if}
            </li>
          {/foreach}
        </ul>
      {else}
        <p>{$dmr.t.orders_empty|escape:'html':'UTF-8'}</p>
      {/if}
    </div>
  {/if}

  {* ---------- modulo pubblico (chiunque non abbia fatto l'accesso come cliente registrato) ---------- *}
  {if $dmr.step == 'public'}
    <form method="post" action="{$dmr.url|escape:'html':'UTF-8'}" class="dmr-card dmr-form" id="dmr-public" novalidate>
      <input type="hidden" name="token" value="{$dmr.token|escape:'html':'UTF-8'}">
      <div class="dmr-hp" aria-hidden="true">
        <label>Website <input type="text" name="dmr_website" tabindex="-1" autocomplete="off"></label>
      </div>

      <h2 class="dmr-title">{$dmr.t.public_title|escape:'html':'UTF-8'}</h2>
      <p class="dmr-statement">{$dmr.t.public_statement|escape:'html':'UTF-8'}</p>

      <fieldset class="dmr-fieldset">
        <legend>{$dmr.t.form_consumer|escape:'html':'UTF-8'}</legend>
        <div class="dmr-grid">
          <label class="dmr-field">
            <span>{$dmr.t.field_firstname|escape:'html':'UTF-8'}</span>
            <input type="text" name="dmr_firstname" required maxlength="100" autocomplete="given-name" value="{$dmr.values.firstname|escape:'html':'UTF-8'}">
            <small class="dmr-muted">{$dmr.t.field_firstname_help|escape:'html':'UTF-8'}</small>
          </label>
          <label class="dmr-field">
            <span>{$dmr.t.field_lastname|escape:'html':'UTF-8'}</span>
            <input type="text" name="dmr_lastname" required maxlength="100" autocomplete="family-name" value="{$dmr.values.lastname|escape:'html':'UTF-8'}">
            <small class="dmr-muted">{$dmr.t.field_lastname_help|escape:'html':'UTF-8'}</small>
          </label>
        </div>
        <label class="dmr-field">
          <span>{$dmr.t.field_order|escape:'html':'UTF-8'}</span>
          <input type="text" name="dmr_order" required maxlength="64" autocomplete="off" value="{$dmr.values.order|escape:'html':'UTF-8'}">
          <small class="dmr-muted">{$dmr.t.field_order_help|escape:'html':'UTF-8'}</small>
        </label>
        <label class="dmr-field">
          <span>{$dmr.t.field_email_receipt|escape:'html':'UTF-8'}</span>
          <input type="email" name="dmr_email" required maxlength="255" autocomplete="email" value="{$dmr.values.email|escape:'html':'UTF-8'}">
          <small class="dmr-muted">{$dmr.t.field_email_receipt_help|escape:'html':'UTF-8'}</small>
        </label>
      </fieldset>

      <fieldset class="dmr-fieldset">
        <legend>{$dmr.t.field_scope|escape:'html':'UTF-8'}</legend>
        <label class="dmr-check">
          <input type="radio" name="dmr_scope" value="whole"{if $dmr.values.scope == 'whole'} checked{/if}>
          <span>{$dmr.t.scope_whole|escape:'html':'UTF-8'}</span>
        </label>
        <label class="dmr-check">
          <input type="radio" name="dmr_scope" value="partial"{if $dmr.values.scope == 'partial'} checked{/if}>
          <span>{$dmr.t.scope_partial|escape:'html':'UTF-8'}</span>
        </label>
        <p class="dmr-muted dmr-small">{$dmr.t.scope_help|escape:'html':'UTF-8'}</p>

        <div class="dmr-rows" id="dmr-rows">
          <p class="dmr-muted dmr-small">{$dmr.t.items_help|escape:'html':'UTF-8'}</p>
          {foreach $dmr.values.rows as $row}
            <div class="dmr-row">
              <label class="dmr-field">
                <span>{$dmr.t.item_name|escape:'html':'UTF-8'}</span>
                <input type="text" name="dmr_item_name[]" maxlength="255" value="{$row.name|escape:'html':'UTF-8'}">
              </label>
              <label class="dmr-field">
                <span>{$dmr.t.item_reference|escape:'html':'UTF-8'}</span>
                <input type="text" name="dmr_item_ref[]" maxlength="64" value="{$row.reference|escape:'html':'UTF-8'}">
              </label>
              <label class="dmr-field dmr-field-qty">
                <span>{$dmr.t.col_qty|escape:'html':'UTF-8'}</span>
                <input type="number" name="dmr_item_qty[]" min="1" step="1" value="{$row.qty|escape:'html':'UTF-8'}">
              </label>
              <button type="button" class="dmr-link dmr-row-remove" hidden>{$dmr.t.item_remove|escape:'html':'UTF-8'}</button>
            </div>
          {/foreach}
          <button type="button" class="dmr-link dmr-row-add" hidden>{$dmr.t.item_add|escape:'html':'UTF-8'}</button>
        </div>
      </fieldset>

      <label class="dmr-field">
        <span>{$dmr.t.field_info|escape:'html':'UTF-8'}</span>
        <textarea name="dmr_info" rows="3" maxlength="2000">{$dmr.values.info|escape:'html':'UTF-8'}</textarea>
        <small class="dmr-muted">{$dmr.t.field_info_help|escape:'html':'UTF-8'}</small>
      </label>

      <p class="dmr-muted dmr-small">{$dmr.t.form_note|escape:'html':'UTF-8'}</p>

      <div class="dmr-actions">
        <button type="submit" name="dmr_public" value="1" class="dmr-btn dmr-btn-confirm">{$dmr.t.btn_confirm|escape:'html':'UTF-8'}</button>
      </div>
      <p class="dmr-muted dmr-small">{$dmr.public_login nofilter}</p>
    </form>
  {/if}

  {* ---------- ordine non più recedibile ---------- *}
  {if $dmr.step == 'notallowed'}
    <div class="dmr-card">
      <h2 class="dmr-title">{$dmr.order.title|escape:'html':'UTF-8'}</h2>
      <p>{$dmr.order.status_label|escape:'html':'UTF-8'}</p>
      <a class="dmr-link" href="{$dmr.url|escape:'html':'UTF-8'}">← {$dmr.t.back_to_orders|escape:'html':'UTF-8'}</a>
    </div>
  {/if}

  {* ---------- dichiarazione di recesso ---------- *}
  {if $dmr.step == 'form'}
    <form method="post" action="{$dmr.url|escape:'html':'UTF-8'}" class="dmr-card dmr-form" id="dmr-form">
      <input type="hidden" name="token" value="{$dmr.token|escape:'html':'UTF-8'}">
      <input type="hidden" name="id_order" value="{$dmr.order.id|intval}">

      <h2 class="dmr-title">{$dmr.t.form_title|escape:'html':'UTF-8'}</h2>
      <p class="dmr-statement">{$dmr.order.statement|escape:'html':'UTF-8'}</p>
      <p class="dmr-deadline">{$dmr.order.deadline|escape:'html':'UTF-8'}</p>

      <fieldset class="dmr-fieldset">
        <legend>{$dmr.t.form_consumer|escape:'html':'UTF-8'}</legend>
        <dl class="dmr-data">
          <dt>{$dmr.t.field_name|escape:'html':'UTF-8'}</dt>
          <dd>{$dmr.values.name|escape:'html':'UTF-8'}</dd>
          <dt>{$dmr.t.field_confirm_email|escape:'html':'UTF-8'}</dt>
          <dd>{$dmr.order.email|escape:'html':'UTF-8'}</dd>
        </dl>
      </fieldset>

      <fieldset class="dmr-fieldset">
        <legend>{$dmr.t.form_products|escape:'html':'UTF-8'}</legend>
        <p class="dmr-muted dmr-small">{$dmr.t.form_products_help|escape:'html':'UTF-8'}</p>
        <ul class="dmr-items">
          {foreach $dmr.order.items as $item}
            {assign var='iv' value=$dmr.values.items[$item.id_order_detail]}
            <li class="dmr-item">
              <label class="dmr-check">
                <input type="checkbox" name="dmr_items[{$item.id_order_detail|intval}]" value="1"{if $iv.checked} checked{/if}>
                <span>
                  {$item.name|escape:'html':'UTF-8'}
                  {if $item.reference}<small>{$item.reference|escape:'html':'UTF-8'}</small>{/if}
                </span>
              </label>
              <label class="dmr-qty">
                <span class="sr-only">{$dmr.t.col_qty|escape:'html':'UTF-8'}</span>
                <input type="number" name="dmr_qty[{$item.id_order_detail|intval}]" min="1" max="{$item.qty|intval}" value="{$iv.qty|intval}">
                <span>{$item.of|escape:'html':'UTF-8'}</span>
              </label>
            </li>
          {/foreach}
        </ul>
      </fieldset>

      <label class="dmr-field">
        <span>{$dmr.t.field_reason|escape:'html':'UTF-8'}</span>
        <textarea name="dmr_reason" rows="3" maxlength="2000">{$dmr.values.reason|escape:'html':'UTF-8'}</textarea>
        <small class="dmr-muted">{$dmr.t.field_reason_help|escape:'html':'UTF-8'}</small>
      </label>

      <p class="dmr-muted dmr-small">{$dmr.t.form_note|escape:'html':'UTF-8'}</p>

      <div class="dmr-actions">
        <button type="submit" name="dmr_confirm" value="1" class="dmr-btn dmr-btn-confirm">{$dmr.t.btn_confirm|escape:'html':'UTF-8'}</button>
        <a class="dmr-link" href="{$dmr.url|escape:'html':'UTF-8'}">{$dmr.t.back_to_orders|escape:'html':'UTF-8'}</a>
      </div>
    </form>
  {/if}

  {* ---------- ricevuta ---------- *}
  {if $dmr.step == 'done'}
    <div class="dmr-card dmr-receipt">
      <span class="dmr-done-icon" aria-hidden="true"></span>
      <h2 class="dmr-title">{$dmr.t.done_title|escape:'html':'UTF-8'}</h2>
      <p>{$dmr.receipt.text|escape:'html':'UTF-8'}</p>
      <dl class="dmr-data">
        <dt>{$dmr.t.receipt_number|escape:'html':'UTF-8'}</dt><dd>#{$dmr.receipt.id|intval}</dd>
        <dt>{$dmr.t.receipt_datetime|escape:'html':'UTF-8'}</dt><dd>{$dmr.receipt.datetime|escape:'html':'UTF-8'}</dd>
        <dt>{$dmr.t.receipt_order|escape:'html':'UTF-8'}</dt><dd>{$dmr.receipt.reference|escape:'html':'UTF-8'}{if $dmr.receipt.order_date} · {$dmr.receipt.order_date|escape:'html':'UTF-8'}{/if}</dd>
        <dt>{$dmr.t.receipt_name|escape:'html':'UTF-8'}</dt><dd>{$dmr.receipt.name|escape:'html':'UTF-8'}</dd>
        <dt>{$dmr.t.receipt_email|escape:'html':'UTF-8'}</dt><dd>{$dmr.receipt.email|escape:'html':'UTF-8'}</dd>
        <dt>{$dmr.t.receipt_products|escape:'html':'UTF-8'}</dt>
        <dd>
          {if $dmr.receipt.whole_order}<strong>{$dmr.t.whole_order|escape:'html':'UTF-8'}</strong>{/if}
          {if $dmr.receipt.items}
            <ul>
              {foreach $dmr.receipt.items as $item}
                <li>{$item|escape:'html':'UTF-8'}</li>
              {/foreach}
            </ul>
          {/if}
        </dd>
        {if $dmr.receipt.reason}
          <dt>{$dmr.t.receipt_info|escape:'html':'UTF-8'}</dt><dd>{$dmr.receipt.reason|escape:'html':'UTF-8'|nl2br nofilter}</dd>
        {/if}
      </dl>
      <div class="dmr-actions">
        <button type="button" class="dmr-btn dmr-btn-ghost" onclick="window.print()">{$dmr.t.receipt_print|escape:'html':'UTF-8'}</button>
        {if $dmr.is_registered}
          <a class="dmr-link" href="{$dmr.url|escape:'html':'UTF-8'}">{$dmr.t.back_to_orders|escape:'html':'UTF-8'}</a>
        {/if}
      </div>
    </div>
  {/if}

</div>
{/block}
