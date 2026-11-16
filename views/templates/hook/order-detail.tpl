<div class="box dmr-box" id="dotmrecesso-order"{if $dmr.css_vars} style="{$dmr.css_vars|escape:'html':'UTF-8'}"{/if}>
  <h3>{$dmr.t.box_title|escape:'html':'UTF-8'}</h3>
  {if $dmr.eligible}
    <p>{$dmr.text|escape:'html':'UTF-8'}</p>
    <a class="dmr-btn" href="{$dmr.url|escape:'html':'UTF-8'}">{$dmr.t.btn_withdraw|escape:'html':'UTF-8'}</a>
  {/if}
  {if $dmr.requests}
    <p class="dmr-box-sub">{$dmr.t.box_requests|escape:'html':'UTF-8'}</p>
    <ul class="dmr-box-list">
      {foreach $dmr.requests as $r}
        <li>#{$r.id|intval} · {$r.date|escape:'html':'UTF-8'} · <strong>{$r.status|escape:'html':'UTF-8'}</strong></li>
      {/foreach}
    </ul>
  {/if}
</div>
