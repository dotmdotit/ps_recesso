<div class="card mt-2" id="dotmrecesso-admin-order">
  <div class="card-header">
    <h3 class="card-header-title">Recesso online</h3>
  </div>
  <div class="card-body">
    <p class="mb-2">{$dmr_admin.label|escape:'html':'UTF-8'}</p>
    {if $dmr_admin.requests}
      <ul class="list-unstyled mb-0">
        {foreach $dmr_admin.requests as $r}
          <li class="mb-1">
            <a href="{$r.link|escape:'html':'UTF-8'}">Richiesta #{$r.id|intval}</a>
            · {$r.date|escape:'html':'UTF-8'}
            · <span class="badge badge-info">{$r.status|escape:'html':'UTF-8'}</span>
          </li>
        {/foreach}
      </ul>
    {else}
      <p class="text-muted mb-0">Nessuna dichiarazione di recesso.</p>
    {/if}
  </div>
</div>
